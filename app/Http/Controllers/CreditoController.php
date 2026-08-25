<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Credito;
use App\Models\AbonoCredito;
use App\Models\CreditoInteres;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Local;
use App\Models\DetalleVenta;
use App\Models\AutorizacionPin;
use App\Models\User;
use App\Notifications\StockBajoNotification;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditoController extends Controller
{
   public function index(Request $request)
   {
       Gate::authorize('ver-creditos');

       $user = auth()->user();

       // 1. Obtener los IDs de los locales del usuario
       $misLocales = [];
       if (!$user->esAdmin()) {
           $misLocales = \DB::table('users_has_local')
                           ->where('id_user', $user->id)
                           ->pluck('id_local')
                           ->toArray();
       }

       // Callback reutilizable para filtrar por estado y local
       $filtroCreditosActivos = function($qCredito) use ($user, $misLocales) {
           $qCredito->whereIn('estado', ['pendiente', 'anticipo']);

           if (!$user->esAdmin()) {
               $qCredito->where(function($q) use ($misLocales) {
                   $q->whereHas('venta', function($qVenta) use ($misLocales) {
                       $qVenta->whereIn('id_local', $misLocales);
                   })
                   ->orWhereNull('id_venta');
               });
           }
       };

       // 2. Consulta principal: Cargar créditos e incluir saldo total pendiente
       $query = Cliente::whereHas('creditos', $filtroCreditosActivos)
           ->with(['creditos' => $filtroCreditosActivos]) // Carga Eager Loading de créditos
           ->withSum(['creditos as saldo_total_pendiente' => $filtroCreditosActivos], 'saldo_pendiente');

       // 3. Filtro de búsqueda por nombre, identificación O ALIAS
       if ($request->filled('buscar')) {
           $buscar = $request->buscar;
           $query->where(function($q) use ($buscar) {
               $q->where('nombre', 'like', "%{$buscar}%")
                 ->orWhere('identificacion', 'like', "%{$buscar}%")
                 ->orWhere('alias', 'like', "%{$buscar}%"); // <-- Se agrega la búsqueda por alias
           });
       }

       $clientes = $query->get();

       // 4. Modal / Selector: Clientes QUE NO TIENEN créditos activos ni anticipos
       $todosLosClientes = Cliente::whereDoesntHave('creditos', $filtroCreditosActivos)
           ->orderBy('nombre', 'asc')
           ->get();

       return view('creditos.index', compact('clientes', 'todosLosClientes'));
   }

    public function show($id)
    {
        // 1. Buscamos al cliente y cargamos sus créditos
        $cliente = Cliente::with([
            'creditos' => function($q) {
                $q->with(['venta', 'abonos.usuario', 'intereses.administrador'])
                  ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // 2. Historial global (para las tablas de la vista)
        $historialAbonos = $cliente->creditos->flatMap(function($credito) {
            return $credito->abonos;
        })->sortByDesc('created_at');

        $historialIntereses = $cliente->creditos->flatMap(function($credito) {
            return $credito->intereses;
        })->sortByDesc('aplicado_en');

        // 3. SEPARACIÓN DE CRÉDITOS POR ESTADO
        // Créditos que actualmente representan una deuda activa
        $creditosPendientes = $cliente->creditos->where('estado', 'pendiente');

        // Créditos en saldo a favor / anticipos
        $creditosAnticipo = $cliente->creditos->filter(function($c) {
            return $c->estado === 'anticipo' || $c->saldo_pendiente < 0;
        });

        // 4. CÁLCULO DE MÉTRICAS ENFOCADAS EN LA DEUDA ACTIVA
        $montoInicialPendiente = $creditosPendientes->sum('monto_inicial');
        $saldoPendienteDeuda   = $creditosPendientes->sum('saldo_pendiente');
        
        // Abonos aplicados EXCLUSIVAMENTE a los créditos que hoy están pendientes
        $totalAbonadoPendiente = $creditosPendientes->flatMap(function($credito) {
            return $credito->abonos;
        })->sum('monto_pagado_usd');

        // Intereses aplicados solo a deudas pendientes
        $totalInteresesPendientes = $creditosPendientes->sum(function($c) { 
            return $c->intereses->sum('monto_interes'); 
        });

        // Saldo a favor acumulado
        $saldoAFavor = abs($creditosAnticipo->sum('saldo_pendiente'));

        // 5. Estructuración del Resumen
        $resumen = [
            'monto_inicial'   => $montoInicialPendiente,
            'total_intereses' => $totalInteresesPendientes,
            'deuda_total'     => $montoInicialPendiente + $totalInteresesPendientes,
            'total_abonado'   => $totalAbonadoPendiente,
            'saldo_pendiente' => $saldoPendienteDeuda,
            'saldo_a_favor'   => $saldoAFavor,
        ];

        return view('creditos.show', compact('cliente', 'historialAbonos', 'resumen', 'historialIntereses'));
    }

    public function registrarAbono(Request $request, $id)
    {
        // 1. Validaciones iniciales
        $request->validate([
            'monto_total_usd' => 'required|numeric|min:0.01',
            'fecha_abono'     => 'required|date'
        ]);
        
        $totalDesglose = ($request->pago_usd_efectivo ?? 0) + ($request->pago_bs_efectivo ?? 0) + 
                         ($request->pago_punto_bs ?? 0) + ($request->pago_pagomovil_bs ?? 0);

        if ($totalDesglose <= 0) {
            return back()->with('error', 'Debe registrar al menos un valor en el desglose.');
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $creditoReferencia = Credito::findOrFail($id);
                $cliente = $creditoReferencia->cliente;
                $idCajaActiva = $this->obtenerCajaActiva();
                
                // Convertir la fecha recibida del modal
                $fechaAbono = \Carbon\Carbon::parse($request->fecha_abono);

                // 2. Buscamos TODOS los créditos pendientes de este cliente (Más viejos primero)
                $creditos = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                $montoRestante = round($request->monto_total_usd, 2);

                // 3. Distribuir abono en las deudas activas
                foreach ($creditos as $credito) {
                    if ($montoRestante <= 0) break;

                    $saldo = round($credito->saldo_pendiente, 2);
                    $abono = min($montoRestante, $saldo);

                    AbonoCredito::create([
                        'id_credito'       => $credito->id,
                        'id_user'          => Auth::id(),
                        'id_caja'          => $idCajaActiva,
                        'monto_pagado_usd' => $abono,
                        'detalles'         => 'Abono Global: ' . ($request->referencia ?? 'Sin referencia'),
                        'estado'           => 'Realizado',
                        'created_at'       => $fechaAbono,
                        'updated_at'       => now(),
                    ]);

                    $credito->saldo_pendiente = round($saldo - $abono, 2);
                    if ($credito->saldo_pendiente <= 0) {
                        $credito->estado = 'pagado';
                        if ($credito->venta) {
                            $credito->venta->update(['estado_pago' => 'Pagado']);
                        }
                    }
                    $credito->save();

                    $montoRestante = round($montoRestante - $abono, 2);
                }

                // 4. MANEJO DEL EXCEDENTE (Saldo a favor / Anticipo)
                if ($montoRestante > 0) {
                    $codigoAnticipo = 'ANT-' . strtoupper(Str::random(6));

                    $ventaAnticipo = new Venta();
                    $ventaAnticipo->codigo_factura     = $codigoAnticipo;
                    $ventaAnticipo->id_cliente         = $cliente->id;
                    $ventaAnticipo->id_user            = auth()->id();
                    $ventaAnticipo->id_local           = auth()->user()->id_local ?? 1;
                    $ventaAnticipo->id_caja            = $idCajaActiva;
                    
                    $ventaAnticipo->pago_usd_efectivo  = 0.00;
                    $ventaAnticipo->pago_bs_efectivo   = 0.00;
                    $ventaAnticipo->monto_credito_usd  = 0.00;
                    $ventaAnticipo->total_usd          = 0.00;
                    
                    $ventaAnticipo->estado             = 'completada';
                    $ventaAnticipo->observacion        = 'Venta generada automáticamente para respaldo de Saldo a Favor / Anticipo';
                    $ventaAnticipo->created_at         = $fechaAbono;
                    $ventaAnticipo->updated_at         = now();
                    $ventaAnticipo->save();

                    $creditoAnticipo = Credito::create([
                        'id_cliente'        => $cliente->id,
                        'id_venta'          => $ventaAnticipo->id,
                        'monto_inicial'     => 0.00,
                        'saldo_pendiente'   => -$montoRestante,
                        'saldo_a_favor'     => $montoRestante,
                        'fecha_vencimiento' => $fechaAbono,
                        'estado'            => 'anticipo',
                        'created_at'        => $fechaAbono,
                        'updated_at'        => now(),
                    ]);

                    AbonoCredito::create([
                        'id_credito'       => $creditoAnticipo->id,
                        'id_user'          => Auth::id(),
                        'id_caja'          => $idCajaActiva,
                        'monto_pagado_usd' => $montoRestante,
                        'detalles'         => 'Excedente a favor: ' . ($request->referencia ?? 'Sin referencia'),
                        'estado'           => 'Realizado',
                        'created_at'       => $fechaAbono,
                        'updated_at'       => now(),
                    ]);

                    if ($cliente) {
                        $cliente->increment('saldo_a_favor', $montoRestante);
                    }
                }
            });

            return redirect()->back()->with('success', 'Abono procesado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    /**
     * Revalorizar: Ajusta la deuda si la tasa de cambio subió 
     * (Opcional según tu modelo de negocio)
     */
    public function revalorizar(Request $request, $id)
    {
        $credito = Credito::findOrFail($id);
        // Aquí iría tu lógica para multiplicar saldo_pendiente por nueva tasa
        // Por ahora lo dejamos como stub para que la ruta no de error 404
        return back()->with('info', 'Función de revalorización en desarrollo.');
    }

    public function anularAbono($id)
    {
        if (Gate::denies('anular-abono')) {
            return redirect()->back()->with('error', 'No autorizado para anular abonos.');
        }

        try {
            DB::transaction(function () use ($id) {
                $abono = AbonoCredito::findOrFail($id);

                if ($abono->estado === 'Anulado') {
                    throw new \Exception('Este abono ya ha sido anulado anteriormente.');
                }

                $abono->update(['estado' => 'Anulado']);

                // Obtenemos el crédito asociado
                $credito = Credito::findOrFail($abono->id_credito);

                // 1. Si el crédito es un ANTICIPO/SALDO A FAVOR
                if ($credito->estado === 'anticipo') {
                    // Al anular el abono del anticipo, este pierde su dinero a favor
                    $credito->saldo_pendiente = 0.00;
                    $credito->estado = 'anulado'; // O 'pagado' para que desaparezca de las vistas activas
                    $credito->save();
                } 
                // 2. Si es un CRÉDITO NORMAL DE VENTA
                else {
                    // RE-CALCULO: Usamos el servicio para asegurar consistencia
                    $service = new \App\Services\CreditoService();
                    $nuevoSaldo = $service->calcularSaldoReal($credito->id);

                    $credito->saldo_pendiente = $nuevoSaldo;
                    $credito->estado = ($nuevoSaldo > 0) ? 'pendiente' : 'pagado';
                    $credito->save();

                    // Sincronizamos el estado de la venta si existe
                    if ($credito->venta) {
                        $estadoVenta = ($nuevoSaldo > 0) ? 'Pendiente' : 'Pagado';
                        $credito->venta->update(['estado_pago' => $estadoVenta]);
                    }
                }
            });

            return redirect()->back()->with('success', 'Abono anulado correctamente. La cuenta ha sido actualizada.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    /**
     * Retorna la vista/modal para aplicar interés
     */
    public function modalInteres($id)
    {
        // Solo admins pueden indexar (puedes usar Gate si lo prefieres)
        if (!auth()->user()->esAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $credito = Credito::with('cliente')->findOrFail($id);
        
        // Retornamos una vista parcial para el modal
        return view('creditos.modals.modal_interes', compact('credito'))->render();
    }

    /**
     * Procesa la aplicación del interés moratorio
     */
    public function aplicarInteres(Request $request, $id)
    {
        $request->validate([
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'observacion' => 'nullable|string|max:255'
        ]);
        
        try {
            $res = DB::transaction(function () use ($request, $id) {
                $credito = Credito::lockForUpdate()->findOrFail($id);
                
                // 1. Cálculos previos
                $saldoAnterior = $credito->saldo_pendiente;
                $montoInteres = $saldoAnterior * ($request->porcentaje / 100);
                $saldoNuevo = $saldoAnterior + $montoInteres;

                // 2. Registro histórico (Agregando los campos faltantes)
                CreditoInteres::create([
                    'id_credito'    => $credito->id,
                    'id_user'       => Auth::id(),
                    'monto_interes' => $montoInteres,
                    'porcentaje'    => $request->porcentaje,
                    'saldo_anterior'=> $saldoAnterior, // Nuevo
                    'saldo_nuevo'   => $saldoNuevo,    // Nuevo
                    'aplicado_en'   => now(),          // Nuevo (timestamp actual)
                    'estado'        => 'aplicado',
                    'observacion'   => $request->observacion
                ]);

                // 3. Actualizar crédito
                $credito->saldo_pendiente = $saldoNuevo;
                $credito->save();

                return ['success' => true, 'mensaje' => "Interés aplicado exitosamente."];
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Verifica la consistencia financiera del crédito
     */
    public function verificarAuditoria($id)
    {
        $credito = Credito::with(['abonos', 'intereses'])->findOrFail($id);
        
        // Cálculo: Monto Inicial + Total Intereses - Total Abonos
        $totalIntereses = $credito->total_intereses; // Usando el atributo del modelo
        $totalAbonos = $credito->abonos()->where('estado', '!=', 'Anulado')->sum('monto_pagado_usd');
        $saldoCalculado = ($credito->monto_inicial + $totalIntereses) - $totalAbonos;
        
        $esConsistente = abs($saldoCalculado - $credito->saldo_pendiente) < 0.01;

        return response()->json([
            'credito_id' => $credito->id,
            'consistente' => $esConsistente,
            'saldo_db' => $credito->saldo_pendiente,
            'saldo_calculado' => $saldoCalculado,
            'detalle' => $esConsistente ? "El saldo es correcto." : "¡Alerta! Descuadre detectado."
        ]);
    }

    public function anularInteres(Request $request, $id) 
    {
        $service = new CreditoService();
        $resultado = $service->anularIndexacion($id, $request->observacion);
        
        // Si necesitas avisar de un reembolso, puedes usar with('info', ...)
        $mensaje = 'Interés anulado correctamente.';
        if ($resultado['monto_a_reembolsar'] > 0) {
            $mensaje .= ' Nota: Se requiere un reembolso de $' . number_format($resultado['monto_a_reembolsar'], 2);
        }
        
        return redirect()->back()->with('success', $mensaje);
    }

    public function procesarGestionSaldo(int $creditoId, string $accion, array $datos)
    {
        return DB::transaction(function () use ($creditoId, $accion, $datos) {
            $creditoAnticipo = Credito::lockForUpdate()->findOrFail($creditoId);

            $montoDisponible = abs($creditoAnticipo->saldo_pendiente);

            if ($montoDisponible <= 0 || $creditoAnticipo->estado !== 'anticipo') {
                throw new \Exception('Este registro no posee saldo a favor disponible para gestionar.');
            }

            $cliente = $creditoAnticipo->cliente;
            $idCaja = $datos['id_caja'] ?? $this->obtenerCajaActiva();

            // CASO 1: REEMBOLSO
            if ($accion === 'reembolso') {
                AbonoCredito::create([
                    'id_credito'       => $creditoAnticipo->id,
                    'id_user'          => auth()->id(),
                    'id_caja'          => $idCaja,
                    'monto_pagado_usd' => -$montoDisponible,
                    'detalles'         => 'REEMBOLSO DE SALDO A FAVOR: ' . ($datos['motivo'] ?? 'Devolución a cliente'),
                    'estado'           => 'Realizado'
                ]);

                $creditoAnticipo->saldo_pendiente = 0.00;
                $creditoAnticipo->saldo_a_favor = 0.00;
                $creditoAnticipo->estado = 'pagado';
                $creditoAnticipo->save();

                if ($cliente) {
                    $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $montoDisponible));
                }

                return [
                    'status'  => 'success',
                    'message' => 'Se ha procesado el reembolso de $' . number_format($montoDisponible, 2) . ' correctamente.'
                ];
            }

            // CASO 2: APLICAR SALDO
            if ($accion === 'aplicar') {
                $deudasPendientes = Credito::where('id_cliente', $creditoAnticipo->id_cliente)
                    ->where('estado', 'pendiente')
                    ->where('id', '!=', $creditoAnticipo->id)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                if ($deudasPendientes->isEmpty()) {
                    throw new \Exception('El cliente no tiene deudas pendientes a las cuales aplicar este saldo.');
                }

                $saldoParaAplicar = $montoDisponible;

                foreach ($deudasPendientes as $deuda) {
                    if ($saldoParaAplicar <= 0) break;

                    $montoDeuda = round($deuda->saldo_pendiente, 2);
                    $descuento = min($saldoParaAplicar, $montoDeuda);

                    AbonoCredito::create([
                        'id_credito'       => $deuda->id,
                        'id_user'          => auth()->id(),
                        'id_caja'          => $idCaja,
                        'monto_pagado_usd' => $descuento,
                        'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $creditoAnticipo->id . ')',
                        'estado'           => 'Realizado'
                    ]);

                    $deuda->saldo_pendiente = round($montoDeuda - $descuento, 2);
                    if ($deuda->saldo_pendiente <= 0) {
                        $deuda->estado = 'pagado';
                        if ($deuda->venta) {
                            $deuda->venta->update(['estado_pago' => 'Pagado']);
                        }
                    }
                    $deuda->save();

                    $saldoParaAplicar = round($saldoParaAplicar - $descuento, 2);
                }

                $montoConsumido = round($montoDisponible - $saldoParaAplicar, 2);

                if ($saldoParaAplicar <= 0) {
                    $creditoAnticipo->saldo_pendiente = 0.00;
                    $creditoAnticipo->saldo_a_favor = 0.00;
                    $creditoAnticipo->estado = 'pagado';
                } else {
                    $creditoAnticipo->saldo_pendiente = -$saldoParaAplicar;
                    $creditoAnticipo->saldo_a_favor = $saldoParaAplicar;
                }
                
                $creditoAnticipo->save();

                if ($cliente && $montoConsumido > 0) {
                    $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $montoConsumido));
                }

                return [
                    'status'  => 'success',
                    'message' => 'Saldo a favor aplicado exitosamente a las deudas pendientes.'
                ];
            }

            throw new \Exception('Acción no reconocida.');
        });
    }

    public function gestionarSaldo(Request $request, $id)
    {
        $request->validate([
            'tipo_accion' => 'required|in:aplicar,reembolso',
            'referencia'  => 'nullable|string|max:255', // Cambiado a nullable por si no siempre meten texto
        ]);

        try {
            // Llamamos al servicio que contiene la lógica pesada
            $service = new CreditoService();
            $resultado = $service->procesarGestionSaldo($id, $request->tipo_accion, $request->all());

            // Verificamos si la respuesta del servicio fue exitosa
            if ($resultado['status'] === 'success') {
                return redirect()->back()->with('success', $resultado['message']);
            }

            return redirect()->back()->with('error', 'No se pudo completar la operación.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al gestionar saldo: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el ID de la caja abierta para el local actual o lanza una excepción.
     *
     * @return int
     * @throws \Exception
     */
    private function obtenerCajaActiva(): int
    {
        $user = Auth::user();
        $local = $user ? $user->localActual() : null;
        $localId = $local ? $local->id : (auth()->user()->id_local ?? 1);

        $caja = Caja::where('id_local', $localId)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            throw new \Exception('No hay una caja abierta en el local actual para registrar el movimiento.');
        }

        return $caja->id;
    }

    public function listarProductos($id) 
    {
        $cliente = Cliente::findOrFail($id);
        
        // Obtenemos los créditos pendientes y los anticipos del cliente
        $creditos = Credito::where('id_cliente', $id)
            ->whereIn('estado', ['pendiente', 'anticipo']) 
            ->with([
                'venta.detalles.insumo', 
                'abonos' => function($q) {
                    $q->orderBy('created_at', 'asc');
                }
            ])
            ->orderBy('created_at', 'asc')
            ->get();
        
        return view('creditos.productos', compact('cliente', 'creditos'));
    }
    /**
     * Genera el Estado de Cuenta del Cliente en PDF
     *
     * @param int $cliente_id
     * @return \Illuminate\Http\Response
     */
    public function pdfEstadoCuenta($cliente_id)
    {
        // 1. Obtener los datos del cliente con sus relaciones
        $cliente = Cliente::findOrFail($cliente_id);

        // 2. Obtener SOLAMENTE los créditos activos (Pendientes de cobro) y Anticipos disponibles
        $creditos = Credito::where('id_cliente', $cliente_id)
            ->whereIn('estado', ['pendiente', 'anticipo'])
            ->get();

        $creditosIds = $creditos->pluck('id');

        // 3. Obtener el historial de abonos vinculados a estos registros activos
        $historialAbonos = AbonoCredito::whereIn('id_credito', $creditosIds)
            ->with(['usuario', 'credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Obtener el historial de intereses/indexación de los créditos activos
        $historialIntereses = CreditoInteres::whereIn('id_credito', $creditosIds)
            ->with(['administrador', 'credito'])
            ->orderBy('aplicado_en', 'desc')
            ->get();

        // 5. Estructurar el resumen financiero acumulado
        // Los créditos normales aportan al monto inicial, los anticipos son 0
        $montoInicialTotal = $creditos->where('estado', 'pendiente')->sum('monto_inicial');
        
        // Suma de intereses aplicados
        $totalIntereses = $historialIntereses
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        // Suma de abonos realizados
        $totalAbonado = $historialAbonos
            ->where('estado', 'Realizado')
            ->sum('monto_pagado_usd');

        // Deuda activa pendiente por cobrar al cliente
        $saldoPendienteTotal = $creditos
            ->where('estado', 'pendiente')
            ->sum('saldo_pendiente');

        // Dinero a favor disponible para el cliente (tomamos el valor absoluto si está guardado en negativo)
        $saldoAFavorTotal = abs($creditos
            ->where('estado', 'anticipo')
            ->sum('saldo_pendiente'));

        $resumen = [
            'monto_inicial'   => $montoInicialTotal,
            'total_intereses' => $totalIntereses,
            'total_abonado'   => $totalAbonado,
            'saldo_a_favor'   => $saldoAFavorTotal,
            'saldo_pendiente' => $saldoPendienteTotal,
            'neto_a_pagar'    => max(0, $saldoPendienteTotal - $saldoAFavorTotal) // Opcional: Deuda menos saldo a favor
        ];

        // 6. Obtener la información de la empresa / local para el encabezado
        $empresa = Local::first();

        // 7. Renderizar la vista PDF enviando también la colección $creditos
        $pdf = Pdf::loadView('creditos.pdf.estado_cuenta', compact(
            'cliente',
            'creditos',
            'resumen',
            'historialAbonos',
            'historialIntereses',
            'empresa'
        ));

        // Ajustar tamaño de papel y orientación
        $pdf->setPaper('letter', 'portrait');

        // Retornar en línea (preview en pestaña)
        return $pdf->stream("Estado_Cuenta_{$cliente->identificacion}.pdf");
    }

    public function storeDirecto(Request $request, $cliente_id)
{
    // 1. Validar los campos del modal
    $request->validate([
        'monto_credito_usd' => 'required|numeric|min:0.01',
        'fecha_credito'     => 'required|date',
        'observacion'       => 'nullable|string',
        'pin_autorizacion'  => 'nullable|string'
    ]);

    // Validar autorización si no tiene el permiso avanzado
    if (Gate::denies('gestionar-creditos-avanzado')) {
        $local = auth()->user()->localActual();
        $auth = AutorizacionPin::where('id_local', $local->id)
                    ->where('pin', $request->pin_autorizacion)
                    ->where('estado', 'usado')
                    ->first();

        if (!$auth) {
            return redirect()->back()->with('error', 'El PIN de autorización no es válido o expiró.');
        }
    }

    $montoUsd = (float) $request->monto_credito_usd;
    $tasa_bcv = bcv_rate('USD');
    DB::beginTransaction();

    try {
        $cliente = Cliente::findOrFail($cliente_id);

        // Generar un código de factura único para esta operación
        $codigoFactura = 'CRD-' . strtoupper(Str::random(6));
        $fechaCredito = Carbon::parse($request->fecha_credito);

        // 2. Registrar la VENTA obligatoria para obtener $venta->id
        $venta = new Venta();
        $venta->codigo_factura     = $codigoFactura;
        $venta->id_cliente         = $cliente->id;
        $venta->id_user            = auth()->id();
        $venta->id_local           = auth()->user()->id_local ?? 1;
        $venta->id_caja            = auth()->user()->id_caja ?? 1;
        
        $venta->pago_usd_efectivo  = 0.00;
        $venta->pago_bs_efectivo   = 0.00;
        $venta->monto_credito_usd  = $montoUsd;
        $venta->total_usd          = $montoUsd;
        
        $venta->estado             = 'completada';
        $venta->observacion        = $request->observacion;
        
        $venta->created_at         = $fechaCredito;
        $venta->updated_at         = now();
        $venta->save();

        // 3. Registrar el CRÉDITO enlazado a la Venta
        $credito = Credito::create([
            'id_venta'           => $venta->id,
            'id_cliente'         => $cliente->id,
            'monto_inicial'      => $montoUsd,
            'saldo_pendiente'    => $montoUsd,
            'fecha_vencimiento'  => $fechaCredito->copy()->addDays(15),
            'estado'             => 'pendiente',
            'tasa_cambio_origen' => $tasa_bcv,
            'created_at'         => $fechaCredito,
            'updated_at'         => now(),
        ]);

        // 4. VERIFICAR Y APLICAR SALDOS A FAVOR / ANTICIPOS ACTIVOS
        $anticipos = Credito::where('id_cliente', $cliente->id)
            ->where(function ($q) {
                $q->where('estado', 'anticipo')
                  ->orWhere('saldo_pendiente', '<', 0);
            })
            ->where('saldo_pendiente', '<', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $deudaPendiente = (float) $credito->saldo_pendiente;

        foreach ($anticipos as $anticipo) {
            if ($deudaPendiente <= 0) break;

            $disponibleAnticipo = abs((float) $anticipo->saldo_pendiente);
            if ($disponibleAnticipo <= 0) continue;

            $descuento = min($deudaPendiente, $disponibleAnticipo);

            // A) Registrar el abono en el nuevo crédito
            AbonoCredito::create([
                'id_credito'       => $credito->id,
                'id_user'          => auth()->id(),
                'id_caja'          => auth()->user()->id_caja ?? 1,
                'monto_pagado_usd' => $descuento,
                'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $anticipo->id . ')',
                'estado'           => 'Realizado',
                'created_at'       => $fechaCredito
            ]);

            // B) Actualizar la deuda del nuevo crédito
            $deudaPendiente -= $descuento;
            $credito->saldo_pendiente = round($deudaPendiente, 2);

            if ($credito->saldo_pendiente <= 0) {
                $credito->saldo_pendiente = 0.00;
                $credito->estado = 'pagado';
            }
            $credito->save();

            // C) Actualizar o cerrar el anticipo usado
            $nuevoRemanenteAnticipo = $disponibleAnticipo - $descuento;

            if ($nuevoRemanenteAnticipo <= 0) {
                $anticipo->saldo_pendiente = 0.00;
                $anticipo->estado = 'pagado';
            } else {
                $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
            }
            $anticipo->save();
        }

        // 5. Notificaciones
        $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
        $detalles = [
            'titulo'  => '💸 Nueva Venta a Crédito Directo',
            'mensaje' => "Se otorgó un crédito directo de {$montoUsd}$ a {$cliente->nombre}.",
            'url'     => route('creditos.index'),
            'icono'   => 'fas fa-hand-holding-usd text-info'
        ];

        foreach ($gerentes as $gerente) {
            $gerente->notify(new StockBajoNotification($detalles));
        }

        DB::commit();

        if ($credito->estado === 'pagado') {
            $msj = 'Crédito directo registrado y saldado automáticamente con el Saldo a Favor disponible.';
        } elseif ($deudaPendiente < $montoUsd) {
            $msj = 'Crédito directo registrado. Se aplicó un saldo a favor y la deuda restante es de $' . number_format($credito->saldo_pendiente, 2);
        } else {
            $msj = 'Crédito directo de $' . number_format($montoUsd, 2) . ' registrado con éxito.';
        }

        return redirect()->back()->with('success', $msj);

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Ocurrió un error al registrar el crédito directo: ' . $e->getMessage());
    }
}

    public function storeDirectoGeneral(Request $request)
    {
        // 1. Validaciones de entrada
        $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'monto_credito_usd' => 'required|numeric|min:0.01',
            'fecha_credito'     => 'required|date',
            'observacion'       => 'nullable|string',
            'pin_autorizacion'  => 'nullable|string'
        ]);

        // Validar autorización si el usuario no tiene el permiso avanzado
        if (Gate::denies('gestionar-creditos-avanzado')) {
            $local = auth()->user()->localActual();
            $auth = AutorizacionPin::where('id_local', $local ? $local->id : (auth()->user()->id_local ?? 1))
                        ->where('pin', $request->pin_autorizacion)
                        ->where('estado', 'usado')
                        ->first();

            if (!$auth) {
                return redirect()->back()->with('error', 'El PIN de autorización no es válido o expiró.');
            }
        }

        $montoUsd = (float) $request->monto_credito_usd;
        $tasa_bcv = bcv_rate('USD');
        DB::beginTransaction();

        try {
            $cliente = Cliente::findOrFail($request->cliente_id);

            // Generar un código de factura único
            $codigoFactura = 'CRD-' . strtoupper(Str::random(6));
            $fechaCredito = Carbon::parse($request->fecha_credito);

            // 2. Registrar en la tabla VENTA
            $venta = new Venta();
            $venta->codigo_factura     = $codigoFactura;
            $venta->id_cliente         = $cliente->id;
            $venta->id_user            = auth()->id();
            $venta->id_local           = auth()->user()->id_local ?? 1;
            $venta->id_caja            = auth()->user()->id_caja ?? 1;
            
            $venta->pago_usd_efectivo  = 0.00;
            $venta->pago_bs_efectivo   = 0.00;
            $venta->monto_credito_usd  = $montoUsd;
            $venta->total_usd          = $montoUsd;
            
            $venta->estado             = 'completada';
            $venta->observacion        = $request->observacion;
            
            $venta->created_at         = $fechaCredito;
            $venta->updated_at         = now();
            $venta->save();

            // 3. Registrar en la tabla CREDITO
            $credito = Credito::create([
                'id_venta'           => $venta->id,
                'id_cliente'         => $cliente->id,
                'monto_inicial'      => $montoUsd,
                'saldo_pendiente'    => $montoUsd,
                'fecha_vencimiento'  => $fechaCredito->copy()->addDays(15), 
                'estado'             => 'pendiente',
                'tasa_cambio_origen' => $tasa_bcv,
                'created_at'         => $fechaCredito,
                'updated_at'         => now(),
            ]);

            // 4. APLICAR SALDOS A FAVOR / ANTICIPOS ACTIVOS DEL CLIENTE
            $anticipos = Credito::where('id_cliente', $cliente->id)
                ->where(function ($q) {
                    $q->where('estado', 'anticipo')
                      ->orWhere('saldo_pendiente', '<', 0);
                })
                ->where('saldo_pendiente', '<', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $deudaPendiente = (float) $credito->saldo_pendiente;

            foreach ($anticipos as $anticipo) {
                if ($deudaPendiente <= 0) break;

                $disponibleAnticipo = abs((float) $anticipo->saldo_pendiente);
                if ($disponibleAnticipo <= 0) continue;

                $descuento = min($deudaPendiente, $disponibleAnticipo);

                // A) Registrar el abono vinculante
                AbonoCredito::create([
                    'id_credito'       => $credito->id,
                    'id_user'          => auth()->id(),
                    'id_caja'          => auth()->user()->id_caja ?? 1,
                    'monto_pagado_usd' => $descuento,
                    'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $anticipo->id . ')',
                    'estado'           => 'Realizado',
                    'created_at'       => $fechaCredito
                ]);

                // B) Descontar de la nueva deuda
                $deudaPendiente -= $descuento;
                $credito->saldo_pendiente = round($deudaPendiente, 2);

                if ($credito->saldo_pendiente <= 0) {
                    $credito->saldo_pendiente = 0.00;
                    $credito->estado = 'pagado';
                }
                $credito->save();

                // C) Ajustar o saldar el registro de anticipo
                $nuevoRemanenteAnticipo = $disponibleAnticipo - $descuento;

                if ($nuevoRemanenteAnticipo <= 0) {
                    $anticipo->saldo_pendiente = 0.00;
                    $anticipo->estado = 'pagado';
                } else {
                    $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
                }
                $anticipo->save();
            }

            // 5. Notificaciones
            $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
            $detalles = [
                'titulo'  => '💸 Nueva Venta a Crédito Directo',
                'mensaje' => "Se otorgó un crédito directo de {$montoUsd}$ a {$cliente->nombre}.",
                'url'     => route('creditos.index'),
                'icono'   => 'fas fa-hand-holding-usd text-info'
            ];

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }

            DB::commit();

            // Respuestas con retroalimentación precisa del saldo
            if ($credito->estado === 'pagado') {
                $msj = 'Crédito directo a ' . $cliente->nombre . ' registrado y saldado automáticamente con su Saldo a Favor.';
            } elseif ($deudaPendiente < $montoUsd) {
                $msj = 'Crédito directo registrado a ' . $cliente->nombre . '. Se le aplicó saldo a favor. Restan $' . number_format($credito->saldo_pendiente, 2) . ' por pagar.';
            } else {
                $msj = 'Crédito directo de $' . number_format($montoUsd, 2) . ' a ' . $cliente->nombre . ' registrado con éxito.';
            }

            return redirect()->back()->with('success', $msj);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al registrar el crédito directo: ' . $e->getMessage());
        }
    }
        /**
             * Elimina una venta a crédito o crédito directo, devolviendo el stock
             * y eliminando los registros financieros asociados.
             */
    public function destroy($id)
    {
        if (Gate::denies('gestionar-creditos-avanzado') && !auth()->user()->esAdmin()) {
            return redirect()->back()->with('error', 'No posee autorización suficiente para eliminar registros de crédito.');
        }

        try {
            $idCliente = null;

            DB::transaction(function () use ($id, &$idCliente) {
                $credito = Credito::with(['venta.detalles.insumo.existencias', 'abonos', 'intereses', 'cliente'])->findOrFail($id);
                
                $idCliente = $credito->id_cliente;
                $cliente = $credito->cliente;
                $venta = $credito->venta;

                // 1. SI ES UN ANTICIPO, DESCONTAR DEL SALDO A FAVOR DEL CLIENTE
                if ($credito->estado === 'anticipo' && $cliente) {
                    $montoAnticipo = abs($credito->saldo_pendiente);
                    $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $montoAnticipo));
                }

                // 2. REVERTIR ABONOS PROVENIENTES DE SALDOS A FAVOR / ANTICIPOS
                foreach ($credito->abonos as $abono) {
                    if (str_contains($abono->detalles, 'Ref #')) {
                        preg_match('/Ref #(\d+)/', $abono->detalles, $coincidencias);
                        if (isset($coincidencias[1])) {
                            $idAnticipoOrigen = $coincidencias[1];
                            $anticipoOrigen = Credito::find($idAnticipoOrigen);

                            if ($anticipoOrigen) {
                                $nuevoSaldo = $anticipoOrigen->saldo_pendiente - $abono->monto_pagado_usd;
                                $montoRestaurado = $abono->monto_pagado_usd;

                                $anticipoOrigen->update([
                                    'saldo_pendiente' => $nuevoSaldo,
                                    'saldo_a_favor'   => abs($nuevoSaldo),
                                    'estado'          => 'anticipo'
                                ]);

                                if ($cliente) {
                                    $cliente->increment('saldo_a_favor', $montoRestaurado);
                                }
                            }
                        }
                    }
                }

                // 3. RETORNO DE STOCK
                if ($venta && $venta->detalles->isNotEmpty()) {
                    foreach ($venta->detalles as $detalle) {
                        if ($detalle->insumo) {
                            $existencia = $detalle->insumo->existencias()->first();
                            if ($existencia) {
                                $existencia->increment('cantidad', $detalle->cantidad);
                            }
                        }
                    }
                    $venta->detalles()->delete();
                }

                // 4. ELIMINAR RELACIONES FINANCIERAS Y REGISTRO
                $credito->abonos()->delete();
                $credito->intereses()->delete();
                $credito->delete();
                
                if ($venta) {
                    $venta->delete();
                }
            });

            $quedanCreditos = Credito::where('id_cliente', $idCliente)
                ->whereIn('estado', ['pendiente', 'anticipo'])
                ->exists();

            if (!$quedanCreditos) {
                return redirect()->route('creditos.index')
                    ->with('success', 'Registro eliminado correctamente. El cliente ya no posee deudas ni saldos pendientes.');
            }

            return redirect()->back()->with('success', 'El registro y sus relaciones han sido eliminados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al intentar eliminar el registro: ' . $e->getMessage());
        }
    }

    public function historialPorFecha(Request $request, $id)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->fecha_inicio . ' 00:00:00';
        $fechaFin = $request->fecha_fin . ' 23:59:59';

        $cliente = Cliente::findOrFail($id);

        // 1. Créditos registrados en el rango de fechas (activos, pagados, anticipos)
        $creditos = Credito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with([
                'venta.detalles.insumo',
                'abonos.usuario',
                'intereses.administrador'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Abonos realizados dentro del rango de fechas (para trazabilidad financiera exacta)
        $abonosPeriodo = AbonoCredito::whereHas('credito', function($q) use ($id) {
                $q->where('id_cliente', $id);
            })
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with(['usuario', 'credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Intereses aplicados en el rango
        $interesesPeriodo = CreditoInteres::whereHas('credito', function($q) use ($id) {
                $q->where('id_cliente', $id);
            })
            ->whereBetween('aplicado_en', [$fechaInicio, $fechaFin])
            ->with(['administrador', 'credito'])
            ->orderBy('aplicado_en', 'desc')
            ->get();

        // 4. Métricas y totales del periodo
        $montoTotalCreditos = $creditos->sum('monto_inicial');
        $totalAbonadoPeriodo = $abonosPeriodo->where('estado', 'Realizado')->sum('monto_pagado_usd');
        $totalInteresesPeriodo = $interesesPeriodo->where('estado', 'aplicado')->sum('monto_interes');

        $empresa = Local::first();

        // 5. Generar el PDF y enviarlo al navegador en línea (stream) para la pestaña nueva
        $pdf = Pdf::loadView('creditos.historial_fechas', compact(
            'cliente', 
            'creditos', 
            'abonosPeriodo', 
            'interesesPeriodo',
            'fechaInicio', 
            'fechaFin', 
            'montoTotalCreditos', 
            'totalAbonadoPeriodo',
            'totalInteresesPeriodo',
            'empresa'
        ));

        // Opcional: configurar orientación vertical u horizontal si lo requieres
        $pdf->setPaper('a4', 'portrait');

        // stream() abre el visor nativo de PDF en la nueva pestaña del navegador
        // return $pdf->stream('estado_cuenta_' . Str::slug($cliente->nombre) . '.pdf');
        return $pdf->stream('estado_cuenta.pdf');
    }
}
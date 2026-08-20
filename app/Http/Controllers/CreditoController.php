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

        // 2. Consulta principal: Clientes QUE TIENEN créditos pendientes (Tabla principal)
        $query = Cliente::whereHas('creditos', function($qCredito) use ($user, $misLocales) {
            $qCredito->where('estado', 'pendiente');

            if (!$user->esAdmin()) {
                $qCredito->whereHas('venta', function($qVenta) use ($misLocales) {
                    $qVenta->whereIn('id_local', $misLocales);
                });
            }
        })
        ->withSum(['creditos as saldo_total_pendiente' => function($qCredito) use ($user, $misLocales) {
            $qCredito->where('estado', 'pendiente');
            
            if (!$user->esAdmin()) {
                $qCredito->whereHas('venta', function($qVenta) use ($misLocales) {
                    $qVenta->whereIn('id_local', $misLocales);
                });
            }
        }], 'saldo_pendiente');

        // 3. Filtro de búsqueda por nombre o identificación
        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->buscar}%")
                  ->orWhere('identificacion', 'like', "%{$request->buscar}%");
            });
        }

        $clientes = $query->get();

        // 4. Modal: Clientes QUE NO TIENEN créditos pendientes activos
        $todosLosClientes = Cliente::whereDoesntHave('creditos', function($qCredito) use ($user, $misLocales) {
            $qCredito->where('estado', 'pendiente');

            if (!$user->esAdmin()) {
                $qCredito->whereHas('venta', function($qVenta) use ($misLocales) {
                    $qVenta->whereIn('id_local', $misLocales);
                });
            }
        })
        ->orderBy('nombre', 'asc')
        ->get();

        return view('creditos.index', compact('clientes', 'todosLosClientes'));
    }

    public function show($id)
    {
        // 1. Buscamos al cliente (el $id ahora representa al cliente)
        // Cargamos sus créditos pendientes y, de esos créditos, sus abonos e intereses
        $cliente = Cliente::with([
                'creditos' => function($q) {
                    //$q->where('estado', 'pendiente')
                      $q->with(['venta', 'abonos.usuario', 'intereses.administrador']);
                }
            ])->findOrFail($id);

        //dd($cliente);
        // 2. Aplanamos todos los abonos de todos los créditos para el historial global
        // Esto junta los abonos de la Factura A, B y C en una sola lista cronológica
        $historialAbonos = $cliente->creditos->flatMap(function($credito) {
            return $credito->abonos;
        })->sortByDesc('created_at');

        // 3. Preparamos el resumen financiero para la sección lateral (col-md-4)
        $resumen = [
            'monto_inicial'    => $cliente->creditos->sum('monto_inicial'),
            'total_intereses'  => $cliente->creditos->sum(function($c) { 
                return $c->intereses->sum('monto_interes'); 
            }),
            'saldo_pendiente'  => $cliente->creditos->sum('saldo_pendiente'),
            'saldo_a_favor'    => $cliente->creditos->sum('saldo_a_favor'),
        ];

        // Cálculos derivados
        $resumen['deuda_total']    = $resumen['monto_inicial'] + $resumen['total_intereses'];
        $resumen['total_abonado']  = $resumen['deuda_total'] - $resumen['saldo_pendiente'];

        // Consolidamos todos los intereses de todos los créditos del cliente
        $historialIntereses = $cliente->creditos->flatMap(function($credito) {
                return $credito->intereses;
            })->sortByDesc('aplicado_en');

        // 4. Retornamos la vista con los datos procesados
        return view('creditos.show', compact('cliente', 'historialAbonos', 'resumen','historialIntereses'));
    }

    public function registrarAbono(Request $request, $id)
    {
        // 1. Validaciones iniciales
        $request->validate(['monto_total_usd' => 'required|numeric|min:0.01']);
        
        // Validar desglose (mínimo un valor mayor a 0)
        $totalDesglose = ($request->pago_usd_efectivo ?? 0) + ($request->pago_bs_efectivo ?? 0) + 
                         ($request->pago_punto_bs ?? 0) + ($request->pago_pagomovil_bs ?? 0);

        if ($totalDesglose <= 0) return back()->with('error', 'Debe registrar al menos un valor en el desglose.');

        try {
            DB::transaction(function () use ($request, $id, $totalDesglose) {
                // El ID que llega es de un crédito, lo usamos para identificar al cliente
                $creditoReferencia = Credito::findOrFail($id);
                $cliente = $creditoReferencia->cliente;

                // 2. Buscamos TODOS los créditos pendientes de este cliente (Más viejos primero)
                $creditos = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                $montoRestante = round($request->monto_total_usd, 2);

                foreach ($creditos as $credito) {
                    if ($montoRestante <= 0) break;

                    $saldo = round($credito->saldo_pendiente, 2);
                    $abono = min($montoRestante, $saldo);

                    // Registramos el abono para este crédito específico
                    AbonoCredito::create([
                        'id_credito' => $credito->id,
                        'id_user'    => Auth::id(),
                        'id_caja'    => $this->obtenerCajaActiva($credito), // Método auxiliar recomendado
                        'monto_pagado_usd' => $abono,
                        'detalles'   => 'Abono Global: ' . ($request->referencia ?? 'Sin referencia'),
                        'estado'     => 'Realizado'
                    ]);

                    // Actualizamos saldo
                    $credito->saldo_pendiente = round($saldo - $abono, 2);
                    if ($credito->saldo_pendiente <= 0) {
                        $credito->estado = 'pagado';
                        if($credito->venta) $credito->venta->update(['estado_pago' => 'Pagado']);
                    }
                    $credito->save();

                    $montoRestante -= $abono;
                }
            });

            return redirect()->back()->with('success', 'Abono procesado y distribuido correctamente.');
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

                    // RE-CALCULO: Usamos el servicio para asegurar consistencia
                    $service = new \App\Services\CreditoService();
                    $nuevoSaldo = $service->calcularSaldoReal($abono->id_credito);

                    $credito = Credito::findOrFail($abono->id_credito);
                    $credito->saldo_pendiente = $nuevoSaldo;
                    
                    $credito->estado = ($nuevoSaldo > 0) ? 'pendiente' : 'pagado';
                    $credito->save();
                });

                return redirect()->back()->with('success', 'Abono anulado correctamente. La deuda del cliente ha sido actualizada.');

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
            $credito = Credito::lockForUpdate()->findOrFail($creditoId);
            
            if ($accion === 'reembolso') {
                // 1. Aquí registrarías la salida en tu tabla de "MovimientosCaja" o "Egresos"
                // MovimientoCaja::create([...]);
                
                // 2. Limpiamos el saldo a favor
                $credito->saldo_a_favor = 0;
            } 
            // ... (lógica de 'aplicar' que ya definimos)
            
            $credito->save();
        });
    }

    public function gestionarSaldo(Request $request, $id)
    {
        $request->validate([
            'tipo_accion' => 'required|in:aplicar,reembolso',
            'referencia'  => 'required|string|max:255',
        ]);

        // Llamamos al servicio que contiene la lógica de negocio
        $service = new CreditoService();
        $resultado = $service->procesarGestionSaldo($id, $request->tipo_accion, $request->all());

        if ($resultado['success']) {
            return redirect()->back()->with('success', 'Operación realizada correctamente.');
        }

        return redirect()->back()->with('error', 'No se pudo completar la operación.');
    }

    private function obtenerCajaActiva()
    {
        $user = Auth::user();
        $local = $user->localActual();
        
        if (!$local) {
            return redirect()->route('home')->with('error', 'No tienes un local activo asignado.');
        }

        // Buscamos la caja abierta del local (Sin tocar sesiones)
        $caja = Caja::where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            return redirect()->route('cajas.create')->with('error', 'No hay una caja abierta en este local.');
        }else{
            return $caja->id;
        }
    }

    public function listarProductos($id) 
    {
        $cliente = Cliente::findOrFail($id);
        
        // Obtenemos los detalles de venta directamente
        $detalles = DetalleVenta::whereHas('venta', function($q) use ($id) {
                $q->where('id_cliente', $id)->where('monto_credito_usd', '>', 0);
            })
            ->with(['venta', 'insumo.categoria', 'insumo.modeloVenta'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('creditos.productos', compact('cliente', 'detalles'));
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
        $cliente = Cliente::with(['creditos'])->findOrFail($cliente_id);

        // 2. Obtener créditos pendientes y sus IDs
        $creditos = Credito::where('id_cliente', $cliente_id)->get();
        $creditosIds = $creditos->pluck('id');

        // 3. Obtener el historial completo de abonos
        $historialAbonos = AbonoCredito::whereIn('id_credito', $creditosIds)
            ->with(['usuario', 'credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Obtener el historial completo de intereses / indexación
        $historialIntereses = CreditoInteres::whereIn('id_credito', $creditosIds)
            ->with(['administrador', 'credito'])
            ->orderBy('aplicado_en', 'desc')
            ->get();

        // 5. Estructurar el resumen financiero acumulado
        $montoInicialTotal = $creditos->sum('monto_inicial');
        
        // Suma de intereses aplicados activos
        $totalIntereses = $historialIntereses
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        // Suma de abonos realizados activos
        $totalAbonado = $historialAbonos
            ->where('estado', 'Realizado')
            ->sum('monto_pagado_usd');

        // Saldo pendiente total actual
        $saldoPendienteTotal = $creditos
            ->where('estado', 'pendiente')
            ->sum('saldo_pendiente');

        $resumen = [
            'monto_inicial'   => $montoInicialTotal,
            'total_intereses' => $totalIntereses,
            'total_abonado'   => $totalAbonado,
            'saldo_a_favor'   => $cliente->creditos->sum('saldo_a_favor'),
            'saldo_pendiente' => $saldoPendienteTotal,
        ];

        // 6. Obtener la información de la empresa / local para el encabezado
        $empresa = Local::first();

        // 7. Renderizar la vista PDF (Configuración en vertical / Letter o A4)
        $pdf = Pdf::loadView('creditos.pdf.estado_cuenta', compact(
            'cliente',
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
                        ->where('estado', 'usado') // Marcado previamente por verificarPin
                        ->first();

            if (!$auth) {
                return redirect()->back()->with('error', 'El PIN de autorización no es válido o expiró.');
            }
        }

        $tasa_bcv = bcv_rate('USD');
        DB::beginTransaction();

        try {
            $cliente = Cliente::findOrFail($cliente_id);

            // Generar un código de factura único para esta operación
            $codigoFactura = 'CRD-' . strtoupper(Str::random(6));

            // 2. Registrar en la tabla VENTA
            $venta = new Venta();
            $venta->codigo_factura     = $codigoFactura;
            $venta->id_cliente         = $cliente->id;
            $venta->id_user            = auth()->id();
            $venta->id_local           = auth()->user()->id_local ?? 1;
            $venta->id_caja            = auth()->user()->id_caja ?? 1;
            
            $venta->pago_usd_efectivo  = 0.00;
            $venta->pago_bs_efectivo   = 0.00;
            $venta->monto_credito_usd  = $request->monto_credito_usd;
            $venta->total_usd          = $request->monto_credito_usd;
            
            $venta->estado             = 'completada';
            $venta->observacion        = $request->observacion;
            
            // Asignar la fecha personalizada al created_at de la venta
            $venta->created_at         = $request->fecha_credito;
            $venta->updated_at         = now();
            $venta->save();

            // 3. Registrar en la tabla CREDITO asociado a la Venta
            $credito = Credito::create([
                'id_venta'           => $venta->id,
                'id_cliente'         => $cliente->id,
                'monto_inicial'      => $request->monto_credito_usd,
                'saldo_pendiente'    => $request->monto_credito_usd,
                'fecha_vencimiento'  => now()->addDays(15), 
                'estado'             => 'pendiente',
                'tasa_cambio_origen' => $tasa_bcv,
                'created_at'         => $request->fecha_credito,
                'updated_at'         => now(),
            ]);

            // 4. Notificaciones a administradores / gerentes
            $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
            $detalles = [
                'titulo'  => '💸 Nueva Venta a Crédito Directo',
                'mensaje' => "Se otorgó un crédito directo de {$request->monto_credito_usd}$ a {$cliente->nombre}.",
                'url'     => route('creditos.index'),
                'icono'   => 'fas fa-hand-holding-usd text-info'
            ];

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }

            DB::commit();

            return redirect()->back()->with('success', 'Crédito directo de $' . number_format($request->monto_credito_usd, 2) . ' registrado con éxito.');

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

            $tasa_bcv = bcv_rate('USD');
            DB::beginTransaction();

            try {
                $cliente = Cliente::findOrFail($request->cliente_id);

                // Generar un código de factura único
                $codigoFactura = 'CRD-' . strtoupper(Str::random(6));

                // 2. Registrar en la tabla VENTA
                $venta = new Venta();
                $venta->codigo_factura     = $codigoFactura;
                $venta->id_cliente         = $cliente->id;
                $venta->id_user            = auth()->id();
                $venta->id_local           = auth()->user()->id_local ?? 1;
                $venta->id_caja            = auth()->user()->id_caja ?? 1;
                
                $venta->pago_usd_efectivo  = 0.00;
                $venta->pago_bs_efectivo   = 0.00;
                $venta->monto_credito_usd  = $request->monto_credito_usd;
                $venta->total_usd          = $request->monto_credito_usd;
                
                $venta->estado             = 'completada';
                $venta->observacion        = $request->observacion;
                
                $venta->created_at         = $request->fecha_credito;
                $venta->updated_at         = now();
                $venta->save();

                // 3. Registrar en la tabla CREDITO
                $credito = Credito::create([
                    'id_venta'           => $venta->id,
                    'id_cliente'         => $cliente->id,
                    'monto_inicial'      => $request->monto_credito_usd,
                    'saldo_pendiente'    => $request->monto_credito_usd,
                    'fecha_vencimiento'  => now()->addDays(15), 
                    'estado'             => 'pendiente',
                    'tasa_cambio_origen' => $tasa_bcv,
                    'created_at'         => $request->fecha_credito,
                    'updated_at'         => now(),
                ]);

                // 4. Notificaciones
                $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
                $detalles = [
                    'titulo'  => '💸 Nueva Venta a Crédito Directo',
                    'mensaje' => "Se otorgó un crédito directo de {$request->monto_credito_usd}$ a {$cliente->nombre}.",
                    'url'     => route('creditos.index'),
                    'icono'   => 'fas fa-hand-holding-usd text-info'
                ];

                foreach ($gerentes as $gerente) {
                    $gerente->notify(new StockBajoNotification($detalles));
                }

                DB::commit();

                return redirect()->back()->with('success', 'Crédito directo de $' . number_format($request->monto_credito_usd, 2) . ' a ' . $cliente->nombre . ' registrado con éxito.');

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
        // 1. Autorización mediante Gate o verificación Admin
        if (Gate::denies('gestionar-creditos-avanzado') && !auth()->user()->esAdmin()) {
            return redirect()->back()->with('error', 'No posee autorización suficiente para eliminar registros de crédito.');
        }

        try {
            $idCliente = null;

            DB::transaction(function () use ($id, &$idCliente) {
                $credito = Credito::with(['venta.detalles.insumo', 'abonos', 'intereses'])->findOrFail($id);
                
                // 📌 Usamos la columna exacta de la base de datos: id_cliente
                $idCliente = $credito->id_cliente;
                
                $venta = $credito->venta;

                // 2. Retorno de stock si la venta tiene detalles (productos de inventario)
                if ($venta && $venta->detalles->isNotEmpty()) {
                    foreach ($venta->detalles as $detalle) {
                        if ($detalle->insumo) {
                            $detalle->insumo->increment('stock', $detalle->cantidad);
                        }
                    }
                    $venta->detalles()->delete();
                }

                // 3. Eliminar historial financiero del crédito (abonos e intereses)
                $credito->abonos()->delete();
                $credito->intereses()->delete();

                // 4. Eliminar la Venta y el Crédito
                $credito->delete();
                
                if ($venta) {
                    $venta->delete();
                }
            });

            // 📌 Consultamos por la columna correcta: 'id_cliente'
            $quedanCreditos = Credito::where('id_cliente', $idCliente)->exists();

            if (!$quedanCreditos) {
                return redirect()->route('creditos.index')
                    ->with('success', 'Crédito eliminado y stock devuelto correctamente. El cliente ya no posee créditos pendientes.');
            }

            return redirect()->back()->with('success', 'El crédito y sus registros asociados han sido eliminados correctamente. El inventario fue actualizado.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al intentar eliminar el crédito: ' . $e->getMessage());
        }
    }
}
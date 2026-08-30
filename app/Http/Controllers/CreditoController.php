<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Credito;
use App\Models\AbonoCredito;
use App\Models\AbonoDetalle;
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
            $misLocales = DB::table('users_has_local')
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
            ->with(['creditos' => $filtroCreditosActivos])
            ->withSum(['creditos as saldo_total_pendiente' => $filtroCreditosActivos], 'saldo_pendiente');

        // 3. Filtro de búsqueda por nombre, identificación o alias
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('identificacion', 'like', "%{$buscar}%")
                  ->orWhere('alias', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query->get();

        // 4. Modal / Selector: Clientes que no tienen créditos activos ni anticipos
        $todosLosClientes = Cliente::whereDoesntHave('creditos', $filtroCreditosActivos)
            ->orderBy('nombre', 'asc')
            ->get();

        return view('creditos.index', compact('clientes', 'todosLosClientes'));
    }

    public function show($id)
    {
        // 1. Buscamos al cliente y cargamos sus créditos con sus detalles de abono e intereses
        $cliente = Cliente::with([
            'creditos' => function($q) {
                $q->with(['venta', 'intereses.administrador'])
                  ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // 2. Historial global de cabeceras de abonos asociadas al cliente
        $historialAbonos = AbonoCredito::where('id_cliente', $cliente->id)
            ->with(['usuario', 'caja', 'detalles.credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        $historialIntereses = $cliente->creditos->flatMap(function($credito) {
            return $credito->intereses;
        })->sortByDesc('aplicado_en');

        // 3. SEPARACIÓN DE CRÉDITOS POR ESTADO
        $creditosPendientes = $cliente->creditos->where('estado', 'pendiente');

        $creditosAnticipo = $cliente->creditos->filter(function($c) {
            return $c->estado === 'anticipo' || $c->saldo_pendiente < 0;
        });

        // 4. CÁLCULO DE MÉTRICAS ENFOCADAS EN LA DEUDA ACTIVA
        $montoInicialPendiente = $creditosPendientes->sum('monto_inicial');
        $saldoPendienteDeuda   = $creditosPendientes->sum('saldo_pendiente');
        
        // Suma de montos aplicados a través de AbonoDetalle exclusivamente a créditos pendientes
        $idsPendientes = $creditosPendientes->pluck('id');
        $totalAbonadoPendiente = AbonoDetalle::whereIn('id_credito', $idsPendientes)
            ->whereHas('abono', function($q) {
                $q->where('estado', '!=', 'Anulado');
            })
            ->sum('monto_aplicado_usd');

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

        $pagoUsdEfectivo = (float)($request->pago_usd_efectivo ?? 0);
        $pagoBsEfectivo  = (float)($request->pago_bs_efectivo ?? 0);
        $pagoPuntoBs     = (float)($request->pago_punto_bs ?? 0);
        $pagoPagomovilBs = (float)($request->pago_pagomovil_bs ?? 0);

        $totalDesglose = $pagoUsdEfectivo + $pagoBsEfectivo + $pagoPuntoBs + $pagoPagomovilBs;

        if ($totalDesglose <= 0) {
            return back()->with('error', 'Debe registrar al menos un valor en el desglose.');
        }

        try {
            DB::transaction(function () use ($request, $id, $pagoUsdEfectivo, $pagoBsEfectivo, $pagoPuntoBs, $pagoPagomovilBs) {
                $creditoReferencia = Credito::findOrFail($id);
                $cliente = $creditoReferencia->cliente;
                $idCajaActiva = $this->obtenerCajaActiva();
                
                $fechaAbono = Carbon::parse($request->fecha_abono);
                $montoTotalUSD = round($request->monto_total_usd, 2);

                // 2. Crear la cabecera única del abono (AbonoCredito)
                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'        => $cliente->id,
                    'id_user'           => Auth::id(),
                    'id_caja'           => $idCajaActiva,
                    'monto_total_usd'   => $montoTotalUSD,
                    'pago_usd_efectivo' => $pagoUsdEfectivo,
                    'pago_bs_efectivo'  => $pagoBsEfectivo,
                    'pago_punto_bs'     => $pagoPuntoBs,
                    'pago_pagomovil_bs' => $pagoPagomovilBs,
                    'detalles'          => 'Abono Global: ' . ($request->referencia ?? 'Sin referencia'),
                    'estado'            => 'Realizado',
                    'created_at'        => $fechaAbono,
                    'updated_at'        => now(),
                ]);

                // 3. Buscamos TODOS los créditos pendientes del cliente (Más antiguos primero)
                $creditos = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                $montoRestante = $montoTotalUSD;

                // 4. Amortización e imputación a través de AbonoDetalle
                foreach ($creditos as $credito) {
                    if ($montoRestante <= 0) break;

                    $saldo = round($credito->saldo_pendiente, 2);
                    $montoAplicado = min($montoRestante, $saldo);

                    AbonoDetalle::create([
                        'id_abono'           => $abonoCabecera->id,
                        'id_credito'         => $credito->id,
                        'monto_aplicado_usd' => $montoAplicado,
                        'created_at'         => $fechaAbono,
                        'updated_at'         => now(),
                    ]);

                    $credito->saldo_pendiente = round($saldo - $montoAplicado, 2);
                    if ($credito->saldo_pendiente <= 0) {
                        $credito->estado = 'pagado';
                        if ($credito->venta) {
                            $credito->venta->update(['estado_pago' => 'Pagado']);
                        }
                    }
                    $credito->save();

                    $montoRestante = round($montoRestante - $montoAplicado, 2);
                }

                // 5. MANEJO DEL EXCEDENTE (Saldo a favor / Anticipo)
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

                    AbonoDetalle::create([
                        'id_abono'           => $abonoCabecera->id,
                        'id_credito'         => $creditoAnticipo->id,
                        'monto_aplicado_usd' => $montoRestante,
                        'created_at'         => $fechaAbono,
                        'updated_at'         => now(),
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

    public function revalorizar(Request $request, $id)
    {
        $credito = Credito::findOrFail($id);
        return back()->with('info', 'Función de revalorización en desarrollo.');
    }

    public function anularAbono($id)
    {
        if (Gate::denies('anular-abono')) {
            return redirect()->back()->with('error', 'No autorizado para anular abonos.');
        }

        try {
            DB::transaction(function () use ($id) {
                // $id corresponde a la cabecera AbonoCredito
                $abonoCabecera = AbonoCredito::with('detalles.credito')->findOrFail($id);

                if ($abonoCabecera->estado === 'Anulado') {
                    throw new \Exception('Este abono ya ha sido anulado anteriormente.');
                }

                $abonoCabecera->update(['estado' => 'Anulado']);

                // Recorrer los detalles vinculados a esta cabecera para recalcular saldos
                foreach ($abonoCabecera->detalles as $detalle) {
                    $credito = $detalle->credito;
                    if (!$credito) continue;

                    // 1. Si el crédito afectado es un ANTICIPO/SALDO A FAVOR
                    if ($credito->estado === 'anticipo') {
                        $credito->saldo_pendiente = 0.00;
                        $credito->saldo_a_favor = 0.00;
                        $credito->estado = 'anulado';
                        $credito->save();

                        $cliente = Cliente::find($abonoCabecera->id_cliente);
                        if ($cliente) {
                            $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $detalle->monto_aplicado_usd));
                        }
                    } 
                    // 2. Si es un CRÉDITO NORMAL DE VENTA
                    else {
                        $service = new CreditoService();
                        $nuevoSaldo = $service->calcularSaldoReal($credito->id);

                        $credito->saldo_pendiente = $nuevoSaldo;
                        $credito->estado = ($nuevoSaldo > 0) ? 'pendiente' : 'pagado';
                        $credito->save();

                        if ($credito->venta) {
                            $estadoVenta = ($nuevoSaldo > 0) ? 'Pendiente' : 'Pagado';
                            $credito->venta->update(['estado_pago' => $estadoVenta]);
                        }
                    }
                }
            });

            return redirect()->back()->with('success', 'Abono anulado correctamente. La cuenta ha sido actualizada.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function modalInteres($id)
    {
        if (!auth()->user()->esAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $credito = Credito::with('cliente')->findOrFail($id);
        
        return view('creditos.modals.modal_interes', compact('credito'))->render();
    }

    public function aplicarInteres(Request $request, $id)
    {
        $request->validate([
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'observacion' => 'nullable|string|max:255'
        ]);
        
        try {
            $res = DB::transaction(function () use ($request, $id) {
                $credito = Credito::lockForUpdate()->findOrFail($id);
                
                $saldoAnterior = $credito->saldo_pendiente;
                $montoInteres = $saldoAnterior * ($request->porcentaje / 100);
                $saldoNuevo = $saldoAnterior + $montoInteres;

                CreditoInteres::create([
                    'id_credito'    => $credito->id,
                    'id_user'       => Auth::id(),
                    'monto_interes' => $montoInteres,
                    'porcentaje'    => $request->porcentaje,
                    'saldo_anterior'=> $saldoAnterior,
                    'saldo_nuevo'   => $saldoNuevo,
                    'aplicado_en'   => now(),
                    'estado'        => 'aplicado',
                    'observacion'   => $request->observacion
                ]);

                $credito->saldo_pendiente = $saldoNuevo;
                $credito->save();

                return ['success' => true, 'mensaje' => "Interés aplicado exitosamente."];
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function verificarAuditoria($id)
    {
        $credito = Credito::with(['intereses'])->findOrFail($id);
        
        $totalIntereses = $credito->total_intereses;
        
        // Sumar montos aplicados desde la tabla abono_detalles filtrando cabeceras válidas
        $totalAbonos = AbonoDetalle::where('id_credito', $id)
            ->whereHas('abono', function($q) {
                $q->where('estado', '!=', 'Anulado');
            })
            ->sum('monto_aplicado_usd');

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
                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'        => $cliente->id,
                    'id_user'           => auth()->id(),
                    'id_caja'           => $idCaja,
                    'monto_total_usd'   => -$montoDisponible,
                    'pago_usd_efectivo' => -$montoDisponible,
                    'detalles'          => 'REEMBOLSO DE SALDO A FAVOR: ' . ($datos['motivo'] ?? 'Devolución a cliente'),
                    'estado'            => 'Realizado'
                ]);

                AbonoDetalle::create([
                    'id_abono'           => $abonoCabecera->id,
                    'id_credito'         => $creditoAnticipo->id,
                    'monto_aplicado_usd' => -$montoDisponible,
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

            // CASO 2: APLICAR SALDO A OTRAS DEUDAS
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

                // Registro global del traspaso/aplicación en cabecera
                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'       => $cliente->id,
                    'id_user'          => auth()->id(),
                    'id_caja'          => $idCaja,
                    'monto_total_usd'  => $montoDisponible,
                    'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $creditoAnticipo->id . ')',
                    'estado'           => 'Realizado'
                ]);

                foreach ($deudasPendientes as $deuda) {
                    if ($saldoParaAplicar <= 0) break;

                    $montoDeuda = round($deuda->saldo_pendiente, 2);
                    $descuento = min($saldoParaAplicar, $montoDeuda);

                    AbonoDetalle::create([
                        'id_abono'           => $abonoCabecera->id,
                        'id_credito'         => $deuda->id,
                        'monto_aplicado_usd' => $descuento,
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
            'referencia'  => 'nullable|string|max:255',
        ]);

        try {
            $service = new CreditoService();
            $resultado = $service->procesarGestionSaldo($id, $request->tipo_accion, $request->all());

            if ($resultado['status'] === 'success') {
                return redirect()->back()->with('success', $resultado['message']);
            }

            return redirect()->back()->with('error', 'No se pudo completar la operación.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al gestionar saldo: ' . $e->getMessage());
        }
    }

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
        
        $creditos = Credito::where('id_cliente', $id)
            ->whereIn('estado', ['pendiente', 'anticipo']) 
            ->with([
                'venta.detalles.insumo', 
                'detallesAbono.abono' => function($q) {
                    $q->orderBy('created_at', 'asc');
                }
            ])
            ->orderBy('created_at', 'asc')
            ->get();
        
        return view('creditos.productos', compact('cliente', 'creditos'));
    }

    public function pdfEstadoCuenta($cliente_id)
    {
        $cliente = Cliente::findOrFail($cliente_id);

        $creditos = Credito::where('id_cliente', $cliente_id)
            ->whereIn('estado', ['pendiente', 'anticipo'])
            ->get();

        $creditosIds = $creditos->pluck('id');

        // Historial global de cabeceras de abonos
        $historialAbonos = AbonoCredito::where('id_cliente', $cliente_id)
            ->with(['usuario', 'caja', 'detalles.credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        $historialIntereses = CreditoInteres::whereIn('id_credito', $creditosIds)
            ->with(['administrador', 'credito'])
            ->orderBy('aplicado_en', 'desc')
            ->get();

        $montoInicialTotal = $creditos->where('estado', 'pendiente')->sum('monto_inicial');
        
        $totalIntereses = $historialIntereses
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        // Suma total de abonos imputados a los créditos mediante AbonoDetalle
        $totalAbonado = AbonoDetalle::whereIn('id_credito', $creditosIds)
            ->whereHas('abono', function($q) {
                $q->where('estado', 'Realizado');
            })
            ->sum('monto_aplicado_usd');

        $saldoPendienteTotal = $creditos
            ->where('estado', 'pendiente')
            ->sum('saldo_pendiente');

        $saldoAFavorTotal = abs($creditos
            ->where('estado', 'anticipo')
            ->sum('saldo_pendiente'));

        $resumen = [
            'monto_inicial'   => $montoInicialTotal,
            'total_intereses' => $totalIntereses,
            'total_abonado'   => $totalAbonado,
            'saldo_a_favor'   => $saldoAFavorTotal,
            'saldo_pendiente' => $saldoPendienteTotal,
            'neto_a_pagar'    => max(0, $saldoPendienteTotal - $saldoAFavorTotal)
        ];

        $empresa = Local::first();

        $pdf = Pdf::loadView('creditos.pdf.estado_cuenta', compact(
            'cliente',
            'creditos',
            'resumen',
            'historialAbonos',
            'historialIntereses',
            'empresa'
        ));

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Estado_Cuenta_{$cliente->identificacion}.pdf");
    }

    public function storeDirecto(Request $request, $cliente_id)
    {
        $request->validate([
            'monto_credito_usd' => 'required|numeric|min:0.01',
            'fecha_credito'     => 'required|date',
            'observacion'       => 'nullable|string',
            'pin_autorizacion'  => 'nullable|string'
        ]);

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

            $codigoFactura = 'CRD-' . strtoupper(Str::random(6));
            $fechaCredito = Carbon::parse($request->fecha_credito);
            $idCajaActiva = $this->obtenerCajaActiva();

            $venta = new Venta();
            $venta->codigo_factura     = $codigoFactura;
            $venta->id_cliente         = $cliente->id;
            $venta->id_user            = auth()->id();
            $venta->id_local           = auth()->user()->id_local ?? 3;
            $venta->id_caja            = $idCajaActiva;
            
            $venta->pago_usd_efectivo  = 0.00;
            $venta->pago_bs_efectivo   = 0.00;
            $venta->monto_credito_usd  = $montoUsd;
            $venta->total_usd          = $montoUsd;
            
            $venta->estado             = 'completada';
            $venta->observacion        = $request->observacion;
            
            $venta->created_at         = $fechaCredito;
            $venta->updated_at         = now();
            $venta->save();

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

            // APLICAR SALDOS A FAVOR / ANTICIPOS ACTIVOS
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

                // Crear cabecera de abono para la transacción automática de saldo a favor
                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'       => $cliente->id,
                    'id_user'          => auth()->id(),
                    'id_caja'          => $idCajaActiva,
                    'monto_total_usd'  => $descuento,
                    'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $anticipo->id . ')',
                    'estado'           => 'Realizado',
                    'created_at'       => $fechaCredito
                ]);

                // Crear el detalle vinculando al nuevo crédito
                AbonoDetalle::create([
                    'id_abono'           => $abonoCabecera->id,
                    'id_credito'         => $credito->id,
                    'monto_aplicado_usd' => $descuento,
                    'created_at'         => $fechaCredito
                ]);

                $deudaPendiente -= $descuento;
                $credito->saldo_pendiente = round($deudaPendiente, 2);

                if ($credito->saldo_pendiente <= 0) {
                    $credito->saldo_pendiente = 0.00;
                    $credito->estado = 'pagado';
                }
                $credito->save();

                $nuevoRemanenteAnticipo = $disponibleAnticipo - $descuento;

                if ($nuevoRemanenteAnticipo <= 0) {
                    $anticipo->saldo_pendiente = 0.00;
                    $anticipo->estado = 'pagado';
                } else {
                    $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
                }
                $anticipo->save();
            }

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
        $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'monto_credito_usd' => 'required|numeric|min:0.01',
            'fecha_credito'     => 'required|date',
            'observacion'       => 'nullable|string',
            'pin_autorizacion'  => 'nullable|string'
        ]);

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

            $idCajaActiva = $this->obtenerCajaActiva();
            $codigoFactura = 'CRD-' . strtoupper(Str::random(6));
            $fechaCredito = Carbon::parse($request->fecha_credito);

            $venta = new Venta();
            $venta->codigo_factura     = $codigoFactura;
            $venta->id_cliente         = $cliente->id;
            $venta->id_user            = auth()->id();
            $venta->id_local           = auth()->user()->id_local ?? 3;
            $venta->id_caja            = $idCajaActiva;
            
            $venta->pago_usd_efectivo  = 0.00;
            $venta->pago_bs_efectivo   = 0.00;
            $venta->monto_credito_usd  = $montoUsd;
            $venta->total_usd          = $montoUsd;
            
            $venta->estado             = 'completada';
            $venta->observacion        = $request->observacion;
            
            $venta->created_at         = $fechaCredito;
            $venta->updated_at         = now();
            $venta->save();

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

                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'       => $cliente->id,
                    'id_user'          => auth()->id(),
                    'id_caja'          => $idCajaActiva,
                    'monto_total_usd'  => $descuento,
                    'detalles'         => 'Abono automático aplicado desde Saldo a Favor (Ref #' . $anticipo->id . ')',
                    'estado'           => 'Realizado',
                    'created_at'       => $fechaCredito
                ]);

                AbonoDetalle::create([
                    'id_abono'           => $abonoCabecera->id,
                    'id_credito'         => $credito->id,
                    'monto_aplicado_usd' => $descuento,
                    'created_at'         => $fechaCredito
                ]);

                $deudaPendiente -= $descuento;
                $credito->saldo_pendiente = round($deudaPendiente, 2);

                if ($credito->saldo_pendiente <= 0) {
                    $credito->saldo_pendiente = 0.00;
                    $credito->estado = 'pagado';
                }
                $credito->save();

                $nuevoRemanenteAnticipo = $disponibleAnticipo - $descuento;

                if ($nuevoRemanenteAnticipo <= 0) {
                    $anticipo->saldo_pendiente = 0.00;
                    $anticipo->estado = 'pagado';
                } else {
                    $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
                }
                $anticipo->save();
            }

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

    public function destroy($id)
    {
        if (Gate::denies('gestionar-creditos-avanzado') && !auth()->user()->esAdmin()) {
            return redirect()->back()->with('error', 'No posee autorización suficiente para eliminar registros de crédito.');
        }

        try {
            $idCliente = null;

            DB::transaction(function () use ($id, &$idCliente) {
                $credito = Credito::with(['venta.detalles.insumo.existencias', 'intereses', 'cliente'])->findOrFail($id);
                
                $idCliente = $credito->id_cliente;
                $cliente = $credito->cliente;
                $venta = $credito->venta;

                // 1. SI ES UN ANTICIPO, DESCONTAR DEL SALDO A FAVOR DEL CLIENTE
                if ($credito->estado === 'anticipo' && $cliente) {
                    $montoAnticipo = abs($credito->saldo_pendiente);
                    $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $montoAnticipo));
                }

                // 2. REVERTIR ABONOS PROVENIENTES DE SALDOS A FAVOR / ANTICIPOS
                $detallesAbono = AbonoDetalle::where('id_credito', $credito->id)->with('abono')->get();
                foreach ($detallesAbono as $detalle) {
                    $abonoCabecera = $detalle->abono;
                    if ($abonoCabecera && str_contains($abonoCabecera->detalles, 'Ref #')) {
                        preg_match('/Ref #(\d+)/', $abonoCabecera->detalles, $coincidencias);
                        if (isset($coincidencias[1])) {
                            $idAnticipoOrigen = $coincidencias[1];
                            $anticipoOrigen = Credito::find($idAnticipoOrigen);

                            if ($anticipoOrigen) {
                                $nuevoSaldo = $anticipoOrigen->saldo_pendiente - $detalle->monto_aplicado_usd;
                                $montoRestaurado = $detalle->monto_aplicado_usd;

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

                // 4. ELIMINAR DETALLES DE ABONO Y LIMPIAR CABECERAS HUÉRFANAS
                $idsCabecera = $detallesAbono->pluck('id_abono')->unique();
                AbonoDetalle::where('id_credito', $credito->id)->delete();

                foreach ($idsCabecera as $idAbono) {
                    if (!AbonoDetalle::where('id_abono', $idAbono)->exists()) {
                        AbonoCredito::where('id', $idAbono)->delete();
                    }
                }

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

        $creditos = Credito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with([
                'venta.detalles.insumo',
                'intereses.administrador'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $abonosPeriodo = AbonoCredito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with(['usuario', 'caja', 'detalles.credito'])
            ->orderBy('created_at', 'desc')
            ->get();

        $interesesPeriodo = CreditoInteres::whereHas('credito', function($q) use ($id) {
                $q->where('id_cliente', $id);
            })
            ->whereBetween('aplicado_en', [$fechaInicio, $fechaFin])
            ->with(['administrador', 'credito'])
            ->orderBy('aplicado_en', 'desc')
            ->get();

        $montoTotalCreditos = $creditos->sum('monto_inicial');
        $totalAbonadoPeriodo = $abonosPeriodo->where('estado', 'Realizado')->sum('monto_total_usd');
        $totalInteresesPeriodo = $interesesPeriodo->where('estado', 'aplicado')->sum('monto_interes');

        $empresa = Local::first();

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

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('estado_cuenta.pdf');
    }
}
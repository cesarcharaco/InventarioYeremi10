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
use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Correlativo;
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

    // Callback base: Valida únicamente el acceso por local (permite ver historial con 0 deuda o saldos pagados)
    $filtroLocalCredito = function($qCredito) use ($user, $misLocales) {
        if (!$user->esAdmin()) {
            $qCredito->where(function($q) use ($misLocales) {
                $q->whereHas('venta', function($qVenta) use ($misLocales) {
                    $qVenta->whereIn('id_local', $misLocales);
                })
                ->orWhereNull('id_venta');
            });
        }
    };

    // Callback estricto: Filtra únicamente créditos activos/anticipos y aplica el filtro de local
    $filtroCreditosActivos = function($qCredito) use ($filtroLocalCredito) {
        $filtroLocalCredito($qCredito);
        $qCredito->whereIn('estado', ['pendiente', 'anticipo']);
    };

    // 2. Consulta principal modificada:
    // - whereHas con $filtroLocalCredito: Trae a clientes con créditos activos Y clientes con 0 deuda. 
    //   Excluye automáticamente a los que NUNCA han tenido créditos.
    // - with y withSum con $filtroCreditosActivos: Siguen cargando y sumando solo lo pendiente/activo.
    $query = Cliente::whereHas('creditos', $filtroLocalCredito)
        ->with(['creditos' => $filtroCreditosActivos])
        // FIX: sumas separadas. Antes 'saldo_total_pendiente' restaba los anticipos
        // (saldo negativo) mostrando una deuda neta en vez de la deuda real
        ->withSum(['creditos as saldo_total_pendiente' => function($q) use ($filtroLocalCredito) {
            $filtroLocalCredito($q);
            $q->where('estado', 'pendiente');
        }], 'saldo_pendiente')
        ->withSum(['creditos as saldo_total_favor' => function($q) use ($filtroLocalCredito) {
            $filtroLocalCredito($q);
            $q->where('estado', 'anticipo')->where('saldo_pendiente', '<', 0);
        }], 'saldo_pendiente');

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

    $locales = $user->esAdmin() ? Local::where('tipo', 'LOCAL')->get() : collect();

    return view('creditos.index', compact('clientes', 'todosLosClientes', 'locales'));
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
            return $c->intereses->where('estado', 'aplicado')->sum('monto_interes'); 
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

        $locales = auth()->user()->esAdmin() ? Local::where('tipo', 'LOCAL')->get() : collect();

        return view('creditos.show', compact('cliente', 'historialAbonos', 'resumen', 'historialIntereses', 'locales'));
    }

    public function registrarAbono(Request $request, $id)
    {
        // 1. Validaciones iniciales
        $request->validate([
            'monto_total_usd' => 'required|numeric|min:0.01',
            'fecha_abono'     => 'required|date',
            'id_local'        => auth()->user()->esAdmin() ? 'required|exists:local,id' : 'nullable' // NUEVO
        ]);

        // OBTENER LA TASA DE CAMBIO
        $tasa_bcv = bcv_rate('USD');

        // Seguridad: Evitar división por cero si la función falla o devuelve 0
        if (!$tasa_bcv || $tasa_bcv <= 0) {
            return back()->with('error', 'No se pudo obtener la tasa del BCV. Verifique el sistema de tasas e intente nuevamente.');
        }

        $pagoUsdEfectivo = (float)($request->pago_usd_efectivo ?? 0);
        $pagoBsEfectivo  = (float)($request->pago_bs_efectivo ?? 0);
        $pagoPuntoBs     = (float)($request->pago_punto_bs ?? 0);
        $pagoPagomovilBs = (float)($request->pago_pagomovil_bs ?? 0);

        // 2. Conversión de Bolívares a Dólares
        $totalPagosBs = $pagoBsEfectivo + $pagoPuntoBs + $pagoPagomovilBs;
        $totalBsConvertidoAUsd = $totalPagosBs / $tasa_bcv;

        // 3. Sumatoria unificada en USD
        $totalDesgloseUsd = $pagoUsdEfectivo + $totalBsConvertidoAUsd;

        if ($totalDesgloseUsd <= 0) {
            return back()->with('error', 'Debe registrar al menos un valor en el desglose.');
        }
        
        // FIX: Tolerancia de 0.05 USD (5 centavos) para absorber diferencias matemáticas de redondeo en BS
        if (abs($totalDesgloseUsd - (float) $request->monto_total_usd) > 0.05) {
            return back()->with(
                'error', 
                'El desglose de pagos ($' . number_format($totalDesgloseUsd, 2) . ' equivalentes) no coincide con el monto total del abono ($' . number_format($request->monto_total_usd, 2) . ').'
            );
        }

        try {
            // Obtenemos el cliente y sus datos antes de la transacción para evitar que queden en NULL
            $creditoReferencia = Credito::findOrFail($id);
            $cliente = $creditoReferencia->cliente;
            $clienteId = $cliente->id;
            $clienteNombre = $cliente->nombre;

            $montoTotalUSD = 0;
            $creditoCanceladoTotal = false;
            
            DB::transaction(function () use ($request, $creditoReferencia, $cliente, $pagoUsdEfectivo, $pagoBsEfectivo, $pagoPuntoBs, $pagoPagomovilBs, &$montoTotalUSD, &$creditoCanceladoTotal) {

                $idCajaActiva = $this->obtenerCajaActiva($request->id_local);
                
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
                    $ventaAnticipo->id_local           = auth()->user()->esAdmin() ? $request->id_local : (auth()->user()->id_local ?? 1);
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
                
                // 6. Verificar si el cliente se ha quedado sin deudas pendientes (Cancelación total)
                $pendientesRestantes = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->count();

                $creditoCanceladoTotal = ($pendientesRestantes === 0);
            });

            // --- ENVÍO DE NOTIFICACIONES A GERENTES Y ENCARGADOS ---
            $gerentes = User::whereIn('role', ['admin', 'encargado'])->get();

            if ($creditoCanceladoTotal) {
                $detalles = [
                    'titulo'  => '🎉 Crédito Cancelado Totalmente',
                    'mensaje' => "El cliente {$clienteNombre} ha cancelado la totalidad de su deuda con un abono de \${$montoTotalUSD}.",
                    'url'     => route('creditos.show', $clienteId),
                    'icono'   => 'fas fa-check-circle text-success'
                ];
            } else {
                $detalles = [
                    'titulo'  => '💵 Nuevo Abono Registrado',
                    'mensaje' => "Se registró un abono de \${$montoTotalUSD} al cliente {$clienteNombre}.",
                    'url'     => route('creditos.show', $clienteId),
                    'icono'   => 'fas fa-hand-holding-usd text-info'
                ];
            }

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }
            // --------------------------------------------------------
            
            return redirect()->back()->with('success', 'Abono procesado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function editAbono($id)
    {
        if (Gate::denies('editar-abono')) {
            return response()->json(['error' => 'No autorizado para editar abonos.'], 403);
        }

        $abono = AbonoCredito::with('cliente')->findOrFail($id);

        if ($abono->estado === 'Anulado') {
            return response()->json(['error' => 'No se puede editar un abono en estado anulado.'], 422);
        }

        return response()->json([
            'success' => true,
            'abono' => [
                'id' => $abono->id,
                'fecha_abono' => $abono->fecha_abono ? $abono->fecha_abono->format('Y-m-d') : date('Y-m-d'),
                'monto_total_usd' => $abono->monto_total_usd,
                'referencia' => $abono->getAttribute('detalles'), // Columna física 'detalles'
                'pago_usd_efectivo' => $abono->pago_usd_efectivo ?? 0,
                'pago_bs_efectivo' => $abono->pago_bs_efectivo ?? 0,
                'pago_punto_bs' => $abono->pago_punto_bs ?? 0,
                'pago_pagomovil_bs' => $abono->pago_pagomovil_bs ?? 0,
                'nombre_cliente' => $abono->cliente->nombre ?? 'Cliente General'
            ]
        ]);
    }

    public function updateAbono(Request $request, $id)
    {
        if (Gate::denies('editar-abono')) {
            return redirect()->back()->with('error', 'No autorizado para editar abonos.');
        }

        $request->validate([
            'fecha_abono'       => 'required|date',
            'monto_total_usd'   => 'required|numeric|min:0.01',
            'referencia'        => 'nullable|string|max:500',
            'pago_usd_efectivo' => 'nullable|numeric|min:0',
            'pago_bs_efectivo'  => 'nullable|numeric|min:0',
            'pago_punto_bs'     => 'nullable|numeric|min:0',
            'pago_pagomovil_bs' => 'nullable|numeric|min:0',
        ]);
    
        try {
            // Variables de control externas para las notificaciones
            $clienteId = null;
            $clienteNombre = null;
            $creditoCanceladoTotal = false;
            $montoTotalUSD = round($request->monto_total_usd, 2);

            DB::transaction(function () use ($request, $id, $montoTotalUSD, &$clienteId, &$clienteNombre, &$creditoCanceladoTotal) {
                $abono = AbonoCredito::findOrFail($id);

                if ($abono->estado === 'Anulado') {
                    throw new \Exception('No se puede modificar un abono que ha sido anulado.');
                }

                $cliente = Cliente::findOrFail($abono->id_cliente);
                
                // Asignamos los datos del cliente para usarlos fuera de la transacción
                $clienteId = $cliente->id;
                $clienteNombre = $cliente->nombre;

                $fechaAbono = Carbon::parse($request->fecha_abono);

                // ---------------------------------------------------------
                // PASO 1: REVERTIR IMPACTO PREVIO
                // ---------------------------------------------------------
                $detallesPrevios = AbonoDetalle::where('id_abono', $abono->id)->get();

                foreach ($detallesPrevios as $detalle) {
                    $credito = Credito::lockForUpdate()->find($detalle->id_credito);
                    if ($credito) {
                        $credito->saldo_pendiente += $detalle->monto_aplicado_usd;
                        
                        if ($credito->saldo_pendiente > 0 && $credito->estado === 'pagado') {
                            $credito->estado = 'pendiente';
                            if ($credito->venta) {
                                $credito->venta->update(['estado_pago' => 'Pendiente']);
                            }
                        }
                        
                        if ($credito->estado === 'anticipo') {
                            // FIX: liquidar el anticipo revertido (crédito + venta respaldo).
                            // Antes quedaba vivo con saldo 0 y, si el abono editado seguía
                            // teniendo excedente, se creaba un SEGUNDO anticipo → doble saldo a favor
                            $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, abs($detalle->monto_aplicado_usd)));

                            if (abs($credito->saldo_pendiente) < 0.01) {
                                if ($credito->venta) {
                                    $credito->venta->delete();
                                }
                                $credito->delete();
                            } else {
                                $credito->saldo_a_favor = abs($credito->saldo_pendiente);
                                $credito->save();
                            }
                        }
                        
                        $credito->save();
                    }
                }

                AbonoDetalle::where('id_abono', $abono->id)->delete();

                // ---------------------------------------------------------
                // PASO 2: ACTUALIZAR CABECERA DEL ABONO
                // ---------------------------------------------------------
                $abono->monto_total_usd   = $montoTotalUSD;
                $abono->pago_usd_efectivo = (float) $request->input('pago_usd_efectivo', 0);
                $abono->pago_bs_efectivo  = (float) $request->input('pago_bs_efectivo', 0);
                $abono->pago_punto_bs     = (float) $request->input('pago_punto_bs', 0);
                $abono->pago_pagomovil_bs = (float) $request->input('pago_pagomovil_bs', 0);
                $abono->detalles          = $request->input('referencia');
                $abono->created_at        = $fechaAbono;
                $abono->updated_at        = now();
                $abono->save();

                // ---------------------------------------------------------
                // PASO 3: REAPLICAR MONTO SOBRE DEUDAS (FIFO)
                // ---------------------------------------------------------
                $montoRestante = $montoTotalUSD;

                $creditosPendientes = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($creditosPendientes as $credito) {
                    if ($montoRestante <= 0) break;

                    $saldo = round($credito->saldo_pendiente, 2);
                    $montoAplicado = min($montoRestante, $saldo);

                    AbonoDetalle::create([
                        'id_abono'           => $abono->id,
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

                // ---------------------------------------------------------
                // PASO 4: MANEJO DEL EXCEDENTE (Anticipo)
                // ---------------------------------------------------------
                if ($montoRestante > 0) {
                    $idCajaActiva = $abono->id_caja;
                    $codigoAnticipo = 'ANT-' . strtoupper(Str::random(6));

                    $ventaAnticipo = new Venta();
                    $ventaAnticipo->codigo_factura     = $codigoAnticipo;
                    $ventaAnticipo->id_cliente         = $cliente->id;
                    $ventaAnticipo->id_user            = $abono->id_user;
                    $ventaAnticipo->id_local           = auth()->user()->id_local ?? 1;
                    $ventaAnticipo->id_caja            = $idCajaActiva;
                    $ventaAnticipo->pago_usd_efectivo  = 0.00;
                    $ventaAnticipo->pago_bs_efectivo   = 0.00;
                    $ventaAnticipo->monto_credito_usd  = 0.00;
                    $ventaAnticipo->total_usd          = 0.00;
                    $ventaAnticipo->estado             = 'completada';
                    $ventaAnticipo->observacion        = 'Venta generada automáticamente para respaldo de Saldo a Favor / Anticipo (edición)';
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
                        'id_abono'           => $abono->id,
                        'id_credito'         => $creditoAnticipo->id,
                        'monto_aplicado_usd' => $montoRestante,
                        'created_at'         => $fechaAbono,
                        'updated_at'         => now(),
                    ]);

                    $cliente->increment('saldo_a_favor', $montoRestante);
                }

                // Verificar si tras la actualización el cliente se quedó sin deudas pendientes
                $pendientesRestantes = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->count();

                $creditoCanceladoTotal = ($pendientesRestantes === 0);
            });

            // --- ENVÍO DE NOTIFICACIONES A GERENTES Y ENCARGADOS (FUERA DE LA TRANSACCIÓN) ---
            $gerentes = User::whereIn('role', ['admin', 'encargado'])->get();

            if ($creditoCanceladoTotal) {
                $detalles = [
                    'titulo'  => '🔄 Abono Modificado (Deuda Cancelada)',
                    'mensaje' => "Se actualizó el abono del cliente {$clienteNombre}. Tras la modificación, el cliente ha saldado la totalidad de sus deudas.",
                    'url'     => route('creditos.show', $clienteId),
                    'icono'   => 'fas fa-check-circle text-success'
                ];
            } else {
                $detalles = [
                    'titulo'  => '🔄 Abono Modificado',
                    'mensaje' => "Se ha actualizado un abono de \${$montoTotalUSD} correspondiente al cliente {$clienteNombre}.",
                    'url'     => route('creditos.show', $clienteId),
                    'icono'   => 'fas fa-edit text-warning'
                ];
            }

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }
            // ------------------------------------------------------------------------------

            return redirect()->back()->with('success', 'Abono actualizado correctamente. Las deudas y saldos a favor han sido recalculados.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar el abono: ' . $e->getMessage());
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
                foreach ($abonoCabecera->getRelation('detalles') as $detalle) {
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

    // CÓDIGO CORREGIDO (CreditoController.php - aplicarInteres):
    public function aplicarInteres(Request $request, $id)
    {
        $request->validate([
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'observacion' => 'nullable|string|max:255'
        ]);
        
        try {
            $res = DB::transaction(function () use ($request, $id) {
                $creditoRef = Credito::findOrFail($id);
                
                // Obtener TODOS los créditos pendientes con saldo del cliente
                $creditosPendientes = Credito::where('id_cliente', $creditoRef->id_cliente)
                    ->where('estado', 'pendiente')
                    ->where('saldo_pendiente', '>', 0)
                    ->lockForUpdate()
                    ->get();

                if ($creditosPendientes->isEmpty()) {
                    throw new \Exception('No hay créditos pendientes con saldo mayor a cero para aplicar intereses.');
                }

                foreach ($creditosPendientes as $credito) {
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
                }

                return ['success' => true, 'mensaje' => "Interés aplicado exitosamente a los créditos pendientes."];
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function verificarAuditoria($id)
    {
        $credito = Credito::with(['intereses'])->findOrFail($id);
        
       $totalIntereses = $credito->intereses->where('estado', 'aplicado')->sum('monto_interes');
        
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

    private function obtenerCajaActiva($id_local_request = null): int
    {
        $user = Auth::user();
        
        // Si es admin y envió un id_local desde el modal, usamos ese. Si no, usamos el del usuario.
        if ($user->esAdmin() && $id_local_request) {
            $localId = $id_local_request;
        } else {
            $local = $user ? $user->localActual() : null;
            $localId = $local ? $local->id : ($user->id_local ?? 1);
        }

        $caja = Caja::where('id_local', $localId)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            throw new \Exception('No hay una caja abierta en el local seleccionado para registrar el movimiento.');
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
                    'intereses' => function($q) {
                        $q->where('estado', 'aplicado')
                          ->orderBy('aplicado_en', 'asc'); // Ordenar indexaciones por fecha
                    },
                    'abonos' => function($q) {
                        $q->where('abonos_credito.estado', 'Realizado')
                          ->orderBy('abonos_credito.created_at', 'asc'); // Ordenar abonos por fecha
                    }
                ])
                ->orderBy('created_at', 'asc') // Ordenar créditos principales por fecha
                ->get();
        
        // Asignamos el valor del pivot a la propiedad que la vista ya está consumiendo
        foreach ($creditos as $credito) {
            foreach ($credito->abonos as $abono) {
                $abono->monto_pagado_usd = $abono->pivot->monto_aplicado_usd;
            }
        }
        
        return view('creditos.productos', compact('cliente', 'creditos'));
    }

    public function pdfEstadoCuenta($cliente_id)
    {
        $cliente = Cliente::findOrFail($cliente_id);

        // 1. Obtener créditos vigentes (Pendientes y Anticipos)
        $creditos = Credito::where('id_cliente', $cliente_id)
            ->whereIn('estado', ['pendiente', 'anticipo'])
            ->with([
                'venta.detalles.insumo',
                'intereses' => function($q) {
                    $q->where('estado', 'aplicado');
                },
                'abonos' => function($q) {
                    $q->where('abonos_credito.estado', 'Realizado');
                }
            ])
            ->get();

        $creditosIds = $creditos->pluck('id');

        // 2. Construir Historial Unificado de Movimientos
        $movimientos = collect();

        foreach ($creditos as $credito) {
            $esAnticipo = ($credito->estado === 'anticipo' || $credito->saldo_pendiente < 0);
            
            if ($esAnticipo) {
                $movimientos->push([
                    'fecha'       => $credito->created_at,
                    'tipo'        => 'ANTICIPO',
                    'titulo'      => 'Saldo a Favor / Anticipo',
                    'monto'       => abs($credito->saldo_pendiente),
                    'observacion' => $credito->observacion ?? optional($credito->venta)->observacion ?? 'Dinero disponible a favor del cliente',
                    'detalles'    => []
                ]);
            } else {
                // Registrar la Compra / Crédito tomado
                $detallesProductos = [];
                if ($credito->venta && $credito->venta->detalles) {
                    foreach ($credito->venta->detalles as $det) {
                        $nombreProd = optional($det->insumo)->producto ?? 'Producto / Mercancía';
                        $cant = $det->cantidad;
                        $detallesProductos[] = "{$cant}x {$nombreProd}";
                    }
                }

                $movimientos->push([
                    'fecha'       => $credito->created_at,
                    'tipo'        => 'CREDITO',
                    'titulo'      => !empty($detallesProductos) ? 'Mercancía llevada a crédito' : 'Crédito / Préstamo directo',
                    'monto'       => $credito->monto_inicial,
                    'observacion' => $credito->observacion ?? optional($credito->venta)->observacion ?? '',
                    'detalles'    => $detallesProductos
                ]);

                // Registrar Indexaciones / Ajustes por Inflación
                foreach ($credito->intereses as $interes) {
                    $movimientos->push([
                        'fecha'       => Carbon::parse($interes->aplicado_en),
                        'tipo'        => 'INDEXACION',
                        'titulo'      => "Ajuste por inflación ({$interes->porcentaje}%)",
                        'monto'       => $interes->monto_interes,
                        'observacion' => $interes->observacion ?? 'Ajuste de valor aplicado a la deuda',
                        'detalles'    => []
                    ]);
                }
            }
        }

        // 3. Obtener Abonos globales vinculados
        $abonos = AbonoCredito::where('id_cliente', $cliente_id)
            ->where('estado', 'Realizado')
            ->whereHas('detalles', function($q) use ($creditosIds) {
                $q->whereIn('id_credito', $creditosIds);
            })
            ->get();

        foreach ($abonos as $abono) {
            $movimientos->push([
                'fecha'       => $abono->created_at,
                'tipo'        => 'ABONO',
                'titulo'      => 'Abono / Pago recibido',
                'monto'       => $abono->monto_total_usd,
                'observacion' => $abono->detalles ?? 'Abono realizado a la cuenta',
                'detalles'    => []
            ]);
        }

        // 4. ORDENAR CRONOLÓGICAMENTE: De la fecha más actual a la más antigua
        $movimientos = $movimientos->sortByDesc('fecha')->values();

        // 5. Cálculos para el Resumen Financiero
        $montoInicialTotal = $creditos->where('estado', 'pendiente')->sum('monto_inicial');
        
        $totalIntereses = CreditoInteres::whereIn('id_credito', $creditosIds)
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        $totalAbonado = AbonoDetalle::whereIn('id_credito', $creditosIds)
            ->whereHas('abono', function($q) {
                $q->where('estado', '!=', 'Anulado');
            })
            ->sum('monto_aplicado_usd');

        $saldoPendienteTotal = $creditos->where('estado', 'pendiente')->sum('saldo_pendiente');
        $saldoAFavorTotal = abs($creditos->where('estado', 'anticipo')->sum('saldo_pendiente'));

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
            'movimientos',
            'resumen',
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
            'pin_autorizacion'  => 'nullable|string',
            'id_local'          => auth()->user()->esAdmin() ? 'required|exists:local,id' : 'nullable'
        ]);

        $montoUsd = (float) $request->monto_credito_usd;
        $cliente = Cliente::findOrFail($cliente_id);

        // Validación 1: El crédito individual supera el límite establecido
        if ($cliente->limite_credito > 0 && $montoUsd > $cliente->limite_credito) {
            return redirect()->back()->with('error', 'El monto del crédito ($' . number_format($montoUsd, 2) . ') supera el límite de crédito establecido para el cliente ($' . number_format($cliente->limite_credito, 2) . ').');
        }

        // Validación 2: La deuda actual acumulada + el nuevo crédito superan el límite
        $saldoPendienteActual = $cliente->creditos()->where('estado', 'pendiente')->sum('saldo_pendiente');
        if ($cliente->limite_credito > 0 && ($saldoPendienteActual + $montoUsd) > $cliente->limite_credito) {
            return redirect()->back()->with('error', 'La deuda actual ($' . number_format($saldoPendienteActual, 2) . ') más este nuevo crédito supera el límite permitido del cliente ($' . number_format($cliente->limite_credito, 2) . ').');
        }
        
        if (Gate::denies('gestionar-creditos-avanzado')) {
            $local = auth()->user()->localActual();

            // Buscamos que coincida el PIN y que su estado sea 'usado' (porque el AJAX lo acaba de quemar)
            $auth = AutorizacionPin::where('id_local', $local->id)
                        ->where('pin', trim($request->pin_autorizacion))
                        ->where('estado', 'usado')
                        ->first();

            if (!$auth) {
                return redirect()->back()->with('error', 'No se encontró una autorización válida para esta operación.');
            }

            // Opcional: Si quieres asegurarte de que no reutilicen un PIN viejísimo de hace horas, 
            // puedes verificar que updated_at sea de hace menos de 2 o 5 minutos.
        }

        $montoUsd = (float) $request->monto_credito_usd;
        $tasa_bcv = bcv_rate('USD');
        DB::beginTransaction();

        try {
            $cliente = Cliente::findOrFail($cliente_id);

            $codigoFactura = 'CRD-' . strtoupper(Str::random(6));
            $fechaCredito = Carbon::parse($request->fecha_credito);
            $idCajaActiva = $this->obtenerCajaActiva($request->id_local);

            $venta = new Venta();
            $venta->codigo_factura     = $codigoFactura;
            $venta->id_cliente         = $cliente->id;
            $venta->id_user            = auth()->id();
            $venta->id_local           = auth()->user()->esAdmin() ? $request->id_local : (auth()->user()->id_local ?? 3);
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
                ->where('estado', 'anticipo')
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
                    $anticipo->saldo_a_favor   = 0.00;
                    $anticipo->estado = 'pagado';
                } else {
                    $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
                    $anticipo->saldo_a_favor   = round($nuevoRemanenteAnticipo, 2);
                }
                $anticipo->save();

                // FIX: sincronizar el saldo global del cliente (antes quedaba duplicado
                // y podía gastarse dos veces)
                if ($cliente && $descuento > 0) {
                    // 1. Forzamos el casteo a float. Si es null, se convierte automáticamente en 0.0
                    $saldoActual = (float) $cliente->saldo_a_favor;
                    
                    // 2. Calculamos el monto numérico exacto a descontar
                    $montoADescontar = min($saldoActual, $descuento);

                    // 3. Solo hacemos la consulta a la BD si realmente hay algo que descontar
                    if ($montoADescontar > 0) {
                        $cliente->decrement('saldo_a_favor', $montoADescontar);
                    }
                }
            }

            $gerentes = User::whereIn('role', ['admin', 'encargado'])->get();
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

        $montoUsd = (float) $request->monto_credito_usd;
        $cliente = Cliente::findOrFail($request->cliente_id);

        // Validación 1: El crédito individual supera el límite establecido
        if ($cliente->limite_credito > 0 && $montoUsd > $cliente->limite_credito) {
            return redirect()->back()->with('error', 'El monto del crédito ($' . number_format($montoUsd, 2) . ') supera el límite de crédito establecido para ' . $cliente->nombre . ' ($' . number_format($cliente->limite_credito, 2) . ').');
        }

        // Validación 2: La deuda actual acumulada + el nuevo crédito superan el límite
        $saldoPendienteActual = $cliente->creditos()->where('estado', 'pendiente')->sum('saldo_pendiente');
        if ($cliente->limite_credito > 0 && ($saldoPendienteActual + $montoUsd) > $cliente->limite_credito) {
            return redirect()->back()->with('error', 'La deuda actual ($' . number_format($saldoPendienteActual, 2) . ') más este nuevo crédito supera el límite permitido para ' . $cliente->nombre . ' ($' . number_format($cliente->limite_credito, 2) . ').');
        }
        
        if (Gate::denies('gestionar-creditos-avanzado')) {
            $local = auth()->user()->localActual();
            $localId = $local ? $local->id : (auth()->user()->id_local ?? 1);

            // Verificamos que el PIN exista, pertenezca al local y esté recién marcado como 'usado' por el AJAX
            $auth = AutorizacionPin::where('id_local', $localId)
                        ->where('pin', trim($request->pin_autorizacion))
                        ->where('estado', 'usado')
                        ->first();

            if (!$auth) {
                return redirect()->back()->with('error', 'El PIN de autorización no es válido o no ha sido verificado.');
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
                ->where('estado', 'anticipo')
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
                    $anticipo->saldo_a_favor   = 0.00;
                    $anticipo->estado = 'pagado';
                } else {
                    $anticipo->saldo_pendiente = -round($nuevoRemanenteAnticipo, 2);
                    $anticipo->saldo_a_favor   = round($nuevoRemanenteAnticipo, 2);
                }
                $anticipo->save();

                // FIX: sincronizar el saldo global del cliente (antes quedaba duplicado
                // y podía gastarse dos veces)
                if ($cliente && $descuento > 0) {
                    $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $descuento));
                }
            }

            $gerentes = User::whereIn('role', ['admin', 'encargado'])->get();
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
            DB::transaction(function () use ($id) {
                // 1. BUSCAR EL CRÉDITO POR ID O POR ID DE VENTA (Ya carga una instancia del modelo Eloquent)
                $credito = Credito::with(['venta.detalles', 'cliente'])
                    ->where(function ($q) use ($id) {
                        $q->where('id', $id)->orWhere('id_venta', $id);
                    })
                    ->first();

                if (!$credito) {
                    throw new \Exception("No existe un registro de crédito asociado al identificador [{$id}].");
                }

                $idCreditoReal = $credito->id;
                $idCliente = $credito->id_cliente;
                $cliente = $credito->cliente;
                $venta = $credito->venta;

                // 2. RESTAURAR INVENTARIO SI ES UNA VENTA CON DETALLES DE INSUMOS
                $esVentaConInsumos = $venta && $venta->detalles && $venta->detalles->isNotEmpty();

                if ($esVentaConInsumos) {
                    foreach ($venta->detalles as $detalle) {
                        if ($detalle->id_insumo) {
                            $existencia = InsumosC::where('id_insumo', $detalle->id_insumo)
                                ->where('id_local', $venta->id_local)
                                ->first();

                            if ($existencia) {
                                $existencia->increment('cantidad', $detalle->cantidad);
                            } else {
                                InsumosC::create([
                                    'id_insumo' => $detalle->id_insumo,
                                    'id_local'  => $venta->id_local,
                                    'cantidad'  => $detalle->cantidad,
                                ]);
                            }
                        }
                    }
                } else {
                    if ($cliente && ($credito->estado === 'anticipo' || $credito->saldo_pendiente < 0)) {
                        $montoAnticipo = abs($credito->saldo_pendiente ?? $credito->monto_inicial);
                        $cliente->decrement('saldo_a_favor', min($cliente->saldo_a_favor, $montoAnticipo));
                    }
                }

                // 3. REASIGNAR ABONOS REGISTRADOS
                $detallesAbono = DB::table('abono_detalles')
                    ->where('id_credito', $idCreditoReal)
                    ->get();

                foreach ($detallesAbono as $detalle) {
                    $montoAbonado = (float) $detalle->monto_aplicado_usd;
                    $idAbonoCabecera = $detalle->id_abono;

                    DB::table('abono_detalles')->where('id', $detalle->id)->delete();

                    // FIX: los detalles negativos (reembolsos de saldo a favor) se anulan
                    // junto con el crédito. Reasignarlos sumaba deuda a créditos ajenos
                    if ($montoAbonado <= 0) {
                        continue;
                    }

                    $siguientesCreditos = Credito::where('id_cliente', $idCliente)
                        ->where('id', '!=', $idCreditoReal)
                        ->whereIn('estado', ['pendiente', 'vencido', 'revalorizado'])
                        ->where('saldo_pendiente', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $montoRestanteReasignar = $montoAbonado;

                    foreach ($siguientesCreditos as $creditoDestino) {
                        if ($montoRestanteReasignar <= 0) break;

                        $montoAbonarCredito = min($creditoDestino->saldo_pendiente, $montoRestanteReasignar);

                        DB::table('abono_detalles')->insert([
                            'id_abono'           => $idAbonoCabecera,
                            'id_credito'         => $creditoDestino->id,
                            'monto_aplicado_usd' => $montoAbonarCredito,
                            'created_at'         => now(),
                            'updated_at'         => now()
                        ]);

                        $nuevoSaldoPendiente = $creditoDestino->saldo_pendiente - $montoAbonarCredito;
                        $nuevoEstado = ($nuevoSaldoPendiente <= 0) ? 'pagado' : $creditoDestino->estado;

                        $creditoDestino->update([
                            'saldo_pendiente' => $nuevoSaldoPendiente,
                            'estado'          => $nuevoEstado
                        ]);

                        $montoRestanteReasignar -= $montoAbonarCredito;
                    }

                    if ($montoRestanteReasignar > 0 && $cliente) {
                        // FIX: crear el respaldo relacional del anticipo. Antes solo se
                        // incrementaba clientes.saldo_a_favor y el dinero quedaba
                        // inutilizable (ningún flujo puede aplicarlo sin un Credito.id)
                        $idCajaRespaldo = DB::table('abonos_credito')->where('id', $idAbonoCabecera)->value('id_caja');

                        $ventaAnticipo = new Venta();
                        $ventaAnticipo->codigo_factura     = 'ANT-' . strtoupper(Str::random(6));
                        $ventaAnticipo->id_cliente         = $cliente->id;
                        $ventaAnticipo->id_user            = auth()->id();
                        $ventaAnticipo->id_local           = auth()->user()->id_local ?? 1;
                        $ventaAnticipo->id_caja            = $idCajaRespaldo;
                        $ventaAnticipo->pago_usd_efectivo  = 0.00;
                        $ventaAnticipo->pago_bs_efectivo   = 0.00;
                        $ventaAnticipo->monto_credito_usd  = 0.00;
                        $ventaAnticipo->total_usd          = 0.00;
                        $ventaAnticipo->estado             = 'completada';
                        $ventaAnticipo->observacion        = 'Respaldo de saldo a favor generado al eliminar crédito #' . $idCreditoReal;
                        $ventaAnticipo->save();

                        $creditoAnticipo = Credito::create([
                            'id_cliente'        => $cliente->id,
                            'id_venta'          => $ventaAnticipo->id,
                            'monto_inicial'     => 0.00,
                            'saldo_pendiente'   => -$montoRestanteReasignar,
                            'saldo_a_favor'     => $montoRestanteReasignar,
                            'fecha_vencimiento' => now(),
                            'estado'            => 'anticipo',
                        ]);

                        AbonoDetalle::create([
                            'id_abono'           => $idAbonoCabecera,
                            'id_credito'         => $creditoAnticipo->id,
                            'monto_aplicado_usd' => $montoRestanteReasignar,
                        ]);

                        $cliente->increment('saldo_a_favor', $montoRestanteReasignar);
                    }

                    $tieneDetalles = DB::table('abono_detalles')->where('id_abono', $idAbonoCabecera)->exists();
                    if (!$tieneDetalles) {
                        DB::table('abonos_credito')->where('id', $idAbonoCabecera)->delete();
                    }
                }

                // 4. DESACTIVAR Y REACTIVAR LLAVES FORÁNEAS DE FORMA SEGURA
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // 5. ELIMINACIÓN DE REGISTROS SECUNDARIOS Y PRINCIPAL VÍA ELOQUENT PARA ACTIVAR EL OBSERVER
                DB::table('credito_intereses')->where('id_credito', $idCreditoReal)->delete();
                DB::table('caja_movimientos')->where('id_credito', $idCreditoReal)->delete();
                
                // Reemplazo del Query Builder por el método delete() del modelo cargado ($credito)
                $credito->delete();

                if ($venta) {
                    if ($esVentaConInsumos) {
                        DB::table('detalle_ventas')->where('id_venta', $venta->id)->delete();
                    }
                    
                    DB::table('pago_referencias')->where('id_venta', $venta->id)->delete();
                    DB::table('ventas_info_adicional')->where('id_venta', $venta->id)->delete();
                    
                    if (class_exists(Correlativo::class)) {
                        Correlativo::where('venta_id', $venta->id)->update([
                            'venta_id' => null, 
                            'estado'   => 'disponible'
                        ]);
                    }

                    DB::table('ventas')->where('id', $venta->id)->delete();
                }

                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            });

            return redirect()->back()->with('success', 'Crédito eliminado correctamente y saldos recalculados.');

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->back()->with('error', 'Error al eliminar el crédito: ' . $e->getMessage());
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

        // 1. Abonos realizados en el periodo
        $abonosPeriodo = AbonoCredito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with(['detalles.credito'])
            ->get();

        // 2. Intereses / Indexaciones aplicadas estrictamente en el periodo
        $interesesPeriodo = CreditoInteres::whereHas('credito', function($q) use ($id) {
                $q->where('id_cliente', $id);
            })
            ->whereBetween('aplicado_en', [$fechaInicio, $fechaFin])
            ->with(['credito'])
            ->get();

        // 3. Recopilar IDs de créditos activos en el rango
        $idsCreditosActivos = Credito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->pluck('id')
            ->toArray();

        foreach ($abonosPeriodo as $abono) {
            $detallesRelacion = $abono->detalles()->get();
                
                if ($detallesRelacion->isNotEmpty()) {
                    foreach ($detallesRelacion as $detalle) {
                        if ($detalle->id_credito) {
                            $idsCreditosActivos[] = $detalle->id_credito;
                        }
                    }
                }
        }

        foreach ($interesesPeriodo as $interes) {
            if ($interes->id_credito) $idsCreditosActivos[] = $interes->id_credito;
        }

        $idsCreditosActivos = array_unique($idsCreditosActivos);

        // 4. Obtener créditos con sus relaciones filtradas por fecha
        $creditos = Credito::whereIn('id', $idsCreditosActivos)
            ->with([
                'venta.detalles.insumo',
                'intereses' => function($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('aplicado_en', [$fechaInicio, $fechaFin])
                      ->where('estado', 'aplicado');
                },
                'abonos'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 5. Totales precisos para el Resumen
        $montoTotalCreditos = Credito::where('id_cliente', $id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('monto_inicial');

        // IMPORTANTE: Ajusta aquí 'monto_abonado' al nombre real de la columna en tu BD (ej. monto_aplicado)
        $totalAbonadoPeriodo = $abonosPeriodo->where('estado', 'Realizado')->sum(function($a) {
            return $a->monto_total_usd ?? 0;
        });

        $totalInteresesPeriodo = $interesesPeriodo->where('estado', 'aplicado')->sum('monto_interes');

        $empresa = Local::first();

        $pdf = Pdf::loadView('creditos.historial_fechas', compact(
            'cliente', 'creditos', 'abonosPeriodo', 'interesesPeriodo',
            'fechaInicio', 'fechaFin', 'montoTotalCreditos', 
            'totalAbonadoPeriodo', 'totalInteresesPeriodo', 'empresa'
        ));

        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('estado_cuenta.pdf');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\ModeloVenta;
use App\Models\Categoria;
use App\Models\Credito;
use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Cliente;
use App\Models\Caja;
use App\Models\AbonoCredito;
use App\Models\AbonoDetalle;
use App\Models\PagoReferencia;
use App\Models\AutorizacionPin;
use App\Models\ConfigOfertas;
use App\Models\User;
use App\Models\Configuracion;
use App\Models\Correlativo;
use App\Models\PromocionRegla;
use App\Notifications\StockBajoNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;

class VentaController extends Controller
{
    public function index(Request $request)
{
    if (Gate::denies('ver-historial-ventas')) {
        return redirect()->back()->with('error', 'Acceso denegado.');
    }

    $user = Auth::user();
    
    // Eager Loading estratégico:
    // Cargamos 'cliente' (que ahora sabemos que tiene su propio id_local)
    // y las nuevas tablas de extensión.
    $query = Venta::with([
        'cliente', 
        'usuario', 
        'local', 
        'infoAdicional', 
        'referencias'
    ]);

    // Lógica de Segmentación por Local
    if (Gate::denies('auditar-cajas')) {
        $local = $user->localActual();
        
        if (!$local) {
             return redirect()->back()->with('error', 'Usuario sin local activo asignado.');
        }

        // Filtramos las ventas del local del usuario
        $query->where('id_local', $local->id);
    }

    // Filtros por Fecha
    if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
        $query->whereBetween('created_at', [
            Carbon::parse($request->fecha_desde)->startOfDay(),
            Carbon::parse($request->fecha_hasta)->endOfDay()
        ]);
    }

    // Filtro por Identificación del Cliente (Usando la relación del modelo que pasaste)
    if ($request->filled('cliente_id')) {
        $query->whereHas('cliente', function($q) use ($request) {
            $q->where('identificacion', 'LIKE', "%{$request->cliente_id}%");
        });
    }

    // Ordenamos por lo más reciente y paginamos
    $ventas = $query->orderBy('id', 'desc')->paginate(20);
    
    return view('ventas.index', compact('ventas'));
}

    
public function create()
{
    if (Gate::denies('operar-caja')) {
        return redirect()->back()->with('error', 'No tienes permiso.');
    }
    
   $user = Auth::user();
   $local = $user->localActual(); // Usamos tu método del modelo User

    $oferta = ConfigOfertas::obtenerActiva($local ? $local->id : null);
    
    $ofertasActivas = !is_null($oferta);
    $motivoOferta = $oferta ? $oferta->motivo : '';

     
    if (!$local) {
        return redirect()->route('home')->with('error', 'No tienes un local activo asignado.');
    }

    // Buscamos la caja abierta del local (Sin tocar sesiones)
    $caja = Caja::where('id_local', $local->id)
                ->where('estado', 'abierta')
                ->first();

    if (!$caja) {
        return redirect()->route('cajas.create')->with('error', 'No hay una caja abierta en este local.');
    }

    $tasa_bcv = bcv_rate('USD');
    if (!$tasa_bcv || $tasa_bcv <= 0) {
        return redirect()->route('home')->with('error', 'Actualizando valor de TASA BCV');
    }

    // --- Obtener el correlativo para la vista ---
    $ultimo = DB::table('ventas_info_adicional')
            ->where('tipo_documento', 'nota_entrega')
            ->whereNotNull('correlativo_nota')
            ->orderBy('id', 'desc')
            ->first();

    $siguiente = $ultimo ? (intval($ultimo->correlativo_nota) + 1) : 1;
    $correlativo_sugerido = str_pad($siguiente, 7, '0', STR_PAD_LEFT);

    // --- Definir descuentos permitidos ---
    $descuentos = [10, 15, 20, 25, 30, 35, 40, 45, 50];

    // Carga de productos (Insumos) con stock y cálculo de ofertas activas para el local actual
    $hoy = Carbon::today();
    
    $productos = Insumos::with(['existencias' => function($q) use ($local) {
        $q->where('id_local', $local->id);
    }])
    ->leftJoin('promociones_reglas as pr', function($join) use ($local, $hoy) {
        $join->where('pr.local_id', $local->id)
             ->where('pr.activo', 1)
             ->whereDate('pr.fecha_inicio', '<=', $hoy)
             ->whereDate('pr.fecha_fin', '>=', $hoy)
             ->where(function($q) {
                 $q->where(function($sub) {
                     $sub->where('pr.alcance', 'insumo')
                         ->whereColumn('pr.referencia_id', 'insumos.id');
                 })->orWhere(function($sub) {
                     $sub->where('pr.alcance', 'categoria')
                         ->whereColumn('pr.referencia_id', 'insumos.categoria_id');
                 });
             });
    })
    ->whereHas('existencias', function($q) use ($local) {
        $q->where('id_local', $local->id)->where('cantidad', '>', 0);
    })
    ->select(
        'insumos.*',
        DB::raw('COALESCE(pr.porcentaje_descuento, 0) as porcentaje_descuento'),
        DB::raw('CASE WHEN pr.id IS NOT NULL THEN 1 ELSE 0 END as en_oferta'),
        DB::raw('CASE WHEN pr.id IS NOT NULL THEN insumos.precio_venta_usd - (insumos.precio_venta_usd * pr.porcentaje_descuento / 100) ELSE insumos.precio_venta_usd END as precio_oferta')
    )
    ->get();

    $clientes = Cliente::where('activo', 'activo')
        ->withSum(['creditos as saldo_pendiente_total' => function($q) {
            $q->where('estado', 'pendiente');
        }], 'saldo_pendiente')
        ->get();
    $categorias = Categoria::orderBy('categoria', 'asc')->get();
    $modelosVenta = ModeloVenta::orderBy('modelo', 'asc')->get();

    return view('ventas.create', compact(
        'productos', 
        'clientes', 
        'local', 
        'caja', 
        'tasa_bcv', 
        'correlativo_sugerido', 
        'descuentos',
        'ofertasActivas',
        'motivoOferta',
        'categorias',
        'modelosVenta'
    ));
}

   

    public function store(Request $request)
{
    if (Gate::denies('operar-caja')) {
        return redirect()->back()->with('error', 'No tienes permiso.');
    }

    // 1. VALIDACIONES ESTRICTAS RESTAURADAS
    // No omitimos nada. Si la vista lo manda, lo validamos.
    $request->validate([
        'id_caja'              => 'required|exists:cajas,id',
        'id_cliente'           => 'required|exists:clientes,id',
        'tipo_documento'       => 'required|in:nota_entrega,factura,sin_documento',
        'correlativo_nota'     => 'nullable|string',
        
        // Totales y Descuentos
        'total_usd'            => 'required|numeric|min:0',
        'total_bs'             => 'required|numeric|min:0',
        'descuento_usd'        => 'nullable|numeric|min:0',
        'descuento_bs'         => 'nullable|numeric|min:0',
        'porcentaje_descuento' => 'nullable|numeric|min:0',
        
        // Métodos de Pago
        'pago_usd_efectivo'    => 'nullable|numeric|min:0',
        'pago_bs_efectivo'     => 'nullable|numeric|min:0',
        'pago_zelle_usd'       => 'nullable|numeric|min:0',
        'pago_punto_bs'        => 'nullable|numeric|min:0',
        'pago_pagomovil_bs'    => 'nullable|numeric|min:0',

        // Referencias
        'referencia_zelle'     => 'nullable|string|max:255',
        'referencia_pagomovil' => 'nullable|string|max:255',

        // Crédito y Excedente
        'monto_credito_usd'    => 'nullable|numeric|min:0',
        'monto_excedente'      => 'nullable|numeric|min:0',
        'pago_excedente_abono' => 'nullable',
        
        // Otros
        'observacion'          => 'nullable|string',
        'pin_autorizacion'     => 'nullable|string',

        // Artículos (Array)
        'articulos'            => 'required|array|min:1',
        'articulos.*.id_insumo'=> 'required|exists:insumos,id',
        'articulos.*.cantidad' => 'required|numeric|min:1',
        'articulos.*.precio_unitario' => 'required|numeric|min:0',
        'articulos.*.porcentaje_descuento_aplicado' => 'nullable|numeric|min:0',
        'articulos.*.promocion_regla_id' => 'nullable'
    ]);

    // Validaciones lógicas manuales para las referencias
    if ($request->pago_zelle_usd > 0 && empty($request->referencia_zelle)) {
        return redirect()->back()->withInput()->withErrors(['referencia_zelle' => 'La referencia de Zelle es obligatoria al tener un monto asignado.']);
    }
    if ($request->pago_pagomovil_bs > 0 && empty($request->referencia_pagomovil)) {
        return redirect()->back()->withInput()->withErrors(['referencia_pagomovil' => 'La referencia de Pago Móvil es obligatoria al tener un monto asignado.']);
    }

    $user = Auth::user();
    $local = $user->localActual();
    $id_caja = $request->id_caja; 
    
    $tasa_bcv = bcv_rate('USD');
    if (!$tasa_bcv || $tasa_bcv <= 0) {
        return redirect()->back()->withInput()->with('error', 'Error crítico: No se pudo obtener la tasa BCV del sistema.');
    }

    $pagosRegistrar = [];

    // Efectivo USD
    if ($request->filled('pago_usd_efectivo') && $request->pago_usd_efectivo > 0) {
        $pagosRegistrar[] = [
            'metodo'     => 'Efectivo USD',
            'referencia' => 'S/R',
            'monto_usd'  => (float) $request->pago_usd_efectivo,
            'monto_bs'   => 0,
        ];
    }

    // Efectivo Bs
    if ($request->filled('pago_bs_efectivo') && $request->pago_bs_efectivo > 0) {
        $montoBs = (float) $request->pago_bs_efectivo;
        $pagosRegistrar[] = [
            'metodo'     => 'Efectivo Bs',
            'referencia' => 'S/R',
            'monto_bs'   => $montoBs,
            'monto_usd'  => $montoBs / $tasa_bcv,
        ];
    }

    // Zelle USD
    if ($request->filled('pago_zelle_usd') && $request->pago_zelle_usd > 0) {
        $pagosRegistrar[] = [
            'metodo'     => 'Zelle',
            'referencia' => $request->referencia_zelle,
            'monto_usd'  => (float) $request->pago_zelle_usd,
            'monto_bs'   => 0,
        ];
    }

    // Punto / Biopago Bs
    if ($request->filled('pago_punto_bs') && $request->pago_punto_bs > 0) {
        $montoBs = (float) $request->pago_punto_bs;
        $pagosRegistrar[] = [
            'metodo'     => 'Punto',
            'referencia' => 'S/R',
            'monto_bs'   => $montoBs,
            'monto_usd'  => $montoBs / $tasa_bcv,
        ];
    }

    // Pago Móvil Bs
    if ($request->filled('pago_pagomovil_bs') && $request->pago_pagomovil_bs > 0) {
        $montoBs = (float) $request->pago_pagomovil_bs;
        $pagosRegistrar[] = [
            'metodo'     => 'Pago Móvil',
            'referencia' => $request->referencia_pagomovil,
            'monto_bs'   => $montoBs,
            'monto_usd'  => $montoBs / $tasa_bcv,
        ];
    }

    DB::beginTransaction();
    try {
        // 2. CORRECCIÓN MATEMÁTICA
        // Total a Favor = Todo el dinero físico/virtual que entra + Lo que el cliente queda debiendo (Crédito)
        $totalPagosUsd = array_sum(array_column($pagosRegistrar, 'monto_usd'));
        $montoCredito = (float) ($request->monto_credito_usd ?? 0);
        $totalAFavor = $totalPagosUsd + $montoCredito;

        // Total a Cobrar = El costo final de la venta + Lo que el cliente pagó de más para abonar a deuda
        $totalUsd = (float) $request->total_usd;
        $montoExcedente = (float) ($request->monto_excedente ?? 0);
        $totalACobrar = $totalUsd + $montoExcedente;

        // Tolerancia de 0.05 para absorber diferencias ínfimas por conversiones Bs -> USD
        if (abs($totalAFavor - $totalACobrar) > 0.05) {
            throw new \Exception("Inconsistencia financiera. Dinero ingresado/crédito ($" . round($totalAFavor, 2) . ") no cuadra con el valor de venta/excedente ($" . round($totalACobrar, 2) . ").");
        }

        $correlativoFiscal = null; 
        $correlativoNota = null;

        // Determinar el código del documento
        if ($request->tipo_documento === 'nota_entrega') {
            $ultimoNota = DB::table('ventas_info_adicional')
                ->where('tipo_documento', 'nota_entrega')
                ->whereNotNull('correlativo_nota')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();
        
            $siguiente = $ultimoNota ? (intval($ultimoNota->correlativo_nota) + 1) : 1;
            $correlativoNota = str_pad($siguiente, 7, '0', STR_PAD_LEFT);
            $codigo = 'NE-' . $correlativoNota;

            if (Venta::where('codigo_factura', $codigo)->exists()) {
                throw new \Exception("Conflicto de correlativo en Nota de Entrega, intente nuevamente.");
            }
        } elseif ($request->tipo_documento === 'factura') {
            $correlativoFiscal = Correlativo::where('estado', 'disponible')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$correlativoFiscal) {
                throw new \Exception("No hay correlativos fiscales disponibles en el sistema. Debe cargar un nuevo lote.");
            }

            $codigo = 'FAC-' . $correlativoFiscal->numero_factura;
            if (Venta::where('codigo_factura', $codigo)->exists()) {
                throw new \Exception("Conflicto de correlativo fiscal, intente nuevamente.");
            }
        } else { 
            $codigo = 'V-' . uniqid();
        }

        // Crear la Venta (Cabecera)
        $venta = Venta::create([
            'codigo_factura'    => $codigo,
            'id_cliente'        => $request->id_cliente,
            'id_user'           => $user->id, 
            'id_local'          => $local->id,
            'id_caja'           => $id_caja,
            'pago_usd_efectivo' => $request->pago_usd_efectivo ?? 0,
            'pago_bs_efectivo'  => $request->pago_bs_efectivo ?? 0,
            'monto_credito_usd' => $request->monto_credito_usd ?? 0,
            'total_usd'         => $request->total_usd,
            'estado'            => 'completada',
            'observacion'       => $request->observacion
        ]);

        /*foreach ($pagosRegistrar as $pago) {
            $venta->detalles()->create($pago);
        }*/

        if ($correlativoFiscal) {
            $correlativoFiscal->update([
                'estado'    => 'usado',
                'venta_id'  => $venta->id,
                'fecha_uso' => now()
            ]);
        }

        // 3. CÁLCULO DE IMPUESTOS EN EL BACKEND Y CORRECCIÓN DE CAMPOS
        // Tu request envía 'descuento_usd', no 'monto_descuento_usd'
        $totalBs = (float) $request->total_bs;
        $baseImponible = $totalBs / 1.16;
        $iva = $baseImponible * 0.16;

        $venta->infoAdicional()->create([
            'tipo_documento'       => $request->tipo_documento,
            'correlativo_nota'     => $request->tipo_documento === 'factura' ? $correlativoFiscal->numero_factura : $correlativoNota,
            'numero_control'       => $correlativoFiscal ? $correlativoFiscal->numero_control : null, 
            'porcentaje_descuento' => $request->porcentaje_descuento ?? 0,
            'monto_descuento_usd'  => $request->descuento_usd ?? 0, 
            'base_imponible_bs'    => $baseImponible,
            'iva_bs'               => $iva,
            'aplica_abono'         => $request->has('pago_excedente_abono')
        ]);

        // 4. CORRECCIÓN DE NOMBRES EN REFERENCIAS (Evita que se guarden en $0)
        if ($request->filled('referencia_zelle')) {
            $venta->referencias()->create([
                'metodo'     => 'Zelle',
                'referencia' => $request->referencia_zelle,
                'monto_bs'   => 0,
                'monto_usd'  => $request->pago_zelle_usd ?? 0, 
            ]);
        }

        if ($request->filled('referencia_pagomovil')) {
            $venta->referencias()->create([
                'metodo'     => 'Pago Móvil',
                'referencia' => $request->referencia_pagomovil,
                'monto_bs'   => $request->pago_pagomovil_bs ?? 0, 
                'monto_usd'  => ($request->pago_pagomovil_bs / $tasa_bcv),
            ]);
        }

        $gerentes = User::whereIn('role', ['admin', 'encargado', 'almacenista'])->get();

        foreach ($request->articulos as $item) {
            $insumoBase = Insumos::find($item['id_insumo']);
            $existencia = InsumosC::where('id_insumo', $item['id_insumo'])
                                   ->where('id_local', $local->id)
                                   ->lockForUpdate()
                                   ->first();

            if (!is_numeric($item['cantidad']) || $item['cantidad'] <= 0 || !$existencia || $existencia->cantidad < $item['cantidad']) {
                $nombreProducto = $insumoBase ? "{$insumoBase->producto} ({$insumoBase->descripcion})" : "Producto desconocido";
                throw new \Exception("Stock insuficiente o inválido para: " . $nombreProducto);
            }

            $venta->detalles()->create([
                'id_insumo'                     => $item['id_insumo'],
                'cantidad'                      => $item['cantidad'],
                'precio_unitario'               => $item['precio_unitario'],
                'subtotal'                      => round($item['cantidad'] * $item['precio_unitario'], 2),
                'promocion_regla_id'            => !empty($item['promocion_regla_id']) ? $item['promocion_regla_id'] : null,
                'porcentaje_descuento_aplicado' => $item['porcentaje_descuento_aplicado'] ?? 0,
            ]);

            $existencia->decrement('cantidad', $item['cantidad']);
            $nuevaCantidad = $existencia->fresh()->cantidad;

            if ($insumoBase && $nuevaCantidad <= $insumoBase->stock_min) {
                $detalles = [
                    'titulo'  => '¡Stock Agotándose!',
                    'mensaje' => "{$insumoBase->producto} quedó en {$nuevaCantidad} unidades en {$local->nombre}.",
                    'url'     => route('insumos.index'),
                    'icono'   => 'fas fa-exclamation-triangle text-danger'
                ];

                foreach ($gerentes as $gerente) {
                    $gerente->notify(new StockBajoNotification($detalles));
                }
            }
        }

        // Lógica de ABONO AUTOMÁTICO
        if ($request->has('pago_excedente_abono') && $montoExcedente > 0) {
            $creditoOld = Credito::where('id_cliente', $request->id_cliente)
                                ->where('estado', 'pendiente')
                                ->lockForUpdate()
                                ->first();

            if ($creditoOld) {
                AbonoCredito::create([
                    'id_credito'        => $creditoOld->id,
                    'id_user'           => $user->id,
                    'id_caja'           => $id_caja,
                    'monto_pagado_usd'  => $montoExcedente,
                    'pago_usd_efectivo' => $request->pago_usd_efectivo ?? 0,
                    'pago_bs_efectivo'  => $request->pago_bs_efectivo ?? 0,
                    'detalles'          => "Abono automático desde Venta: " . $codigo,
                    'estado'            => 'Realizado'
                ]);

                $creditoOld->decrement('saldo_pendiente', $montoExcedente);

                if ($creditoOld->fresh()->saldo_pendiente <= 0.01) {
                    $creditoOld->update([
                        'estado'          => 'pagado',
                        'saldo_pendiente' => 0
                    ]);
                }
            }
        }

        
        // Nuevo Crédito
            // Nuevo Crédito
            if ($montoCredito > 0) {
                // 1. Obtener directamente de la BD el ÚNICO anticipo activo del cliente (Sin Eloquent)
                    $anticipo = DB::table('creditos')
                        ->where('id_cliente', $request->id_cliente)
                        ->where('saldo_a_favor', '>', 0)
                        ->latest('id')
                        ->first();

                    $saldoAFavorDisponible = $anticipo ? (float) $anticipo->saldo_a_favor : 0.0;

                // 2. Determinar montos para el cruce
                $montoAbonadoConFavor = min($montoCredito, $saldoAFavorDisponible);
                $nuevoSaldoPendiente  = $montoCredito - $montoAbonadoConFavor;
                $estadoNuevoCredito   = ($nuevoSaldoPendiente <= 0) ? 'pagado' : 'pendiente';

                // 3. Crear el nuevo Crédito de la venta
                $credito = Credito::create([
                    'id_venta'           => $venta->id,
                    'id_cliente'         => $request->id_cliente,
                    'monto_inicial'      => $montoCredito,
                    'saldo_pendiente'    => $nuevoSaldoPendiente,
                    'saldo_a_favor'      => 0,
                    'fecha_vencimiento'  => now()->addDays(15), 
                    'estado'             => $estadoNuevoCredito,
                    'tasa_cambio_origen' => $tasa_bcv
                ]);

                // 4. Descontar progresivamente el saldo a favor de los anticipos del cliente
                if ($montoAbonadoConFavor > 0) {
                    $nuevoSaldoFavor = $saldoAFavorDisponible - $montoAbonadoConFavor;
                    $nuevoEstadoAnt  = ($nuevoSaldoFavor <= 0) ? 'pagado' : 'anticipo';

                    // Actualización atómica en la base de datos
                    $x=DB::table('creditos')
                        ->where('id', $anticipo->id)
                        ->update([
                            'saldo_a_favor' => $nuevoSaldoFavor,
                            'estado'        => $nuevoEstadoAnt,
                            'updated_at'    => now()
                        ]);
                

                    // 5. Registrar el Abono y su Detalle vinculados al nuevo crédito
                    $abono = AbonoCredito::create([
                        'id_cliente'        => $request->id_cliente,
                        'id_user'           => Auth::id(),
                        'id_caja'           => $id_caja ?? null,
                        'monto_total_usd'   => $montoAbonadoConFavor,
                        'pago_usd_efectivo' => 0,
                        'pago_bs_efectivo'  => 0,
                        'pago_punto_bs'     => 0,
                        'pago_pagomovil_bs' => 0,
                        'detalles'          => 'Abono automático por Cruce de Saldo a Favor / Anticipo',
                        'estado'            => 'Realizado'
                    ]);

                    AbonoDetalle::create([
                        'id_abono'           => $abono->id,
                        'id_credito'         => $credito->id,
                        'monto_aplicado_usd' => $montoAbonadoConFavor,
                    ]);
                }
            
                
            // 6. Notificaciones a gerencia
            $mensajeNotificacion = ($nuevoSaldoPendiente > 0) 
                ? "Se otorgó un crédito de {$montoCredito}$ (Cubierto {$montoAbonadoConFavor}$ con saldo a favor. Restante: {$nuevoSaldoPendiente}$)."
                : "Venta a crédito de {$montoCredito}$ saldada completamente con Saldo a Favor previo del cliente.";

            $detalles = [
                'titulo'  => '💸 Nueva Venta a Crédito / Cruce de Saldo',
                'mensaje' => $mensajeNotificacion,
                'url'     => route('creditos.index'),
                'icono'   => 'fas fa-hand-holding-usd text-info'
            ];

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }
        }

        DB::commit();

        if (in_array($request->tipo_documento, ['nota_entrega', 'factura'])) {
            $tipoNombre = $request->tipo_documento === 'factura' ? 'Factura' : 'Nota de Entrega';
            return redirect()->route('ventas.create')
                ->with('success', "Venta {$codigo} guardada exitosamente.")
                ->with('imprimir_documento', [
                    'venta_id' => $venta->id,
                    'codigo'   => $codigo,
                    'tipo'     => $tipoNombre,
                ]);
        }

        return redirect()->route('ventas.create')->with('success', "Venta {$codigo} guardada exitosamente.");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withInput()->with('error', 'Error en transacción: ' . $e->getMessage());
    }
}


    public function show($id)
    {
        // Cargamos 'infoAdicional' para acceder a tipo_documento, correlativo_nota y numero_control
        $venta = Venta::with([
            'cliente', 
            'detalles.insumo', 
            'usuario', 
            'local', 
            'credito', 
            'infoAdicional'
        ])->findOrFail($id);
        
        // Si utilizas la misma vista para ambos documentos, solo pasas $venta
        return view('ventas.show', compact('venta'));
    }

    public function solicitarPin(Request $request)
    {
        $user = Auth::user();
        $local = $user->localActual();

        // 1. Validar que el local exista
        if (!$local) {
            return response()->json([
                'success' => false, 
                'message' => 'No se encontró un local activo para solicitar el PIN.'
            ], 422);
        }

        // 2. Generar PIN de 6 dígitos en texto plano
        $pinPlano = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 3. Guardar el PIN en texto plano (sin Hash::make)
        AutorizacionPin::updateOrCreate(
            [
                'id_local' => $local->id,
            ],
            [
                'vendedor'   => $user->name, 
                'pin'        => $pinPlano, // <-- Se guarda legible (ej: 123456)
                'monto'      => $request->monto_total,
                'cliente'    => $request->cliente_nombre,
                'estado'     => 'activo',
                'updated_at' => now()
            ]
        );

        // 4. Obtener los IDs de usuarios asignados al local
        $usuariosLocalIds = DB::table('users_has_local')
            ->where('id_local', $local->id)
            ->pluck('id_user');

        // 5. Buscar administradores y encargados del local
        $destinatarios = User::where('role', 'admin')
            ->orWhere(function($query) use ($usuariosLocalIds) {
                $query->where('role', 'encargado')
                      ->whereIn('id', $usuariosLocalIds);
            })
            ->get();

        // 6. Preparar mensaje para notificación
        $detalles = [
            'titulo'  => '🔐 Solicitud de PIN de Autorización',
            'mensaje' => "{$user->name} en {$local->nombre} solicita PIN para venta de {$request->monto_total}$. Código PIN: {$pinPlano}",
            'url'     => '#', 
            'icono'   => 'fas fa-key text-warning'
        ];

        try {
            foreach ($destinatarios as $destinatario) {
                $destinatario->notify(new StockBajoNotification($detalles));
            }
        } catch (\Exception $e) {
            \Log::error('Error enviando notificación de PIN: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true, 
            'message' => 'PIN generado y enviado exitosamente.'
        ]);
    }

    public function verificarPin(Request $request)
    {
        $user = Auth::user();
        $local = $user->localActual();

        if (!$local) {
            return response()->json([
                'success' => false, 
                'message' => 'No se encontró un local activo para validar el PIN.'
            ], 422);
        }

        $pinIngresado = trim($request->pin);

        if (empty($pinIngresado)) {
            return response()->json([
                'success' => false, 
                'message' => 'Debe ingresar el código PIN.'
            ], 422);
        }

        return DB::transaction(function () use ($local, $pinIngresado) {
            $auth = AutorizacionPin::where('id_local', $local->id)
                        ->where('estado', 'activo')
                        ->lockForUpdate()
                        ->latest('updated_at')
                        ->first();

            // Compara el PIN ingresado directamente en texto plano
            if ($auth && $auth->pin === $pinIngresado) {
                $auth->update(['estado' => 'usado']); 
                return response()->json(['success' => true]);
            }

            return response()->json([
                'success' => false, 
                'message' => 'El PIN de autorización no es válido, ya fue utilizado o expiró.'
            ], 422);
        });
    }


    public function getDeudaPendiente($id)
{
     
        $credito = DB::table('creditos')
            ->where('id_cliente', $id)
            ->where('estado', 'pendiente') 
            ->select('id', 'saldo_pendiente')
            ->first();

        if ($credito && $credito->saldo_pendiente > 0) {
            return response()->json([
                'tiene_deuda'     => true,
                'saldo_total_usd' => number_format($credito->saldo_pendiente, 2, '.', ''),
                'id_credito'      => $credito->id
            ]);
        }

        return response()->json([
            'tiene_deuda' => false
        ]);
    }

    public function getProximoCorrelativo()
    {
        // Consultamos el último correlativo en nuestra tabla de extensión
        $ultimo = DB::table('ventas_info_adicional')
            ->whereNotNull('correlativo_nota')
            ->orderBy('id', 'desc')
            ->select('correlativo_nota')
            ->first();

        $siguienteNumero = $ultimo ? (intval($ultimo->correlativo_nota) + 1) : 1;
        
        // Formateamos a 7 dígitos (ej: 0000001)
        $correlativo = str_pad($siguienteNumero, 7, '0', STR_PAD_LEFT);

        return response()->json([
            'correlativo' => $correlativo
        ]);
    }

    public function generarPresupuesto(Request $request)
    {
        try {
            // 1. Obtener la información del cliente
            $cliente = Cliente::find($request->id_cliente);
            
            if (!$cliente) {
                // Cliente genérico por si no se selecciona uno específico en el POS
                $cliente = new Cliente([
                    'nombre'       => 'Cliente Ocasional / General',
                    'identificacion' => 'N/A',
                    'telefono'     => 'N/A',
                    'direccion'    => 'No especificada'
                ]);
            }

            // 2. Procesar los artículos enviados desde el carrito del POS
            $articulosEnviados = $request->input('articulos', []);
            $detallesPresupuesto = [];
            $subtotalGeneral = 0;

            foreach ($articulosEnviados as $item) {
                $cantidad        = $item['cantidad'] ?? 1;
                $precioUnitario  = $item['precio_unitario'] ?? 0;
                $subtotal        = $cantidad * $precioUnitario;
                $subtotalGeneral += $subtotal;

                // Buscar el insumo en base de datos para asegurar datos actualizados
                $insumo = Insumos::find($item['id_insumo'] ?? null);

                $detallesPresupuesto[] = [
                    'nombre'          => $insumo->producto ?? $item['nombre'] ?? 'Producto N/A',
                    'serial'          => $insumo->serial ?? null,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal
                ];
            }

            // 3. Cálculos de descuentos y totales
            $porcentajeDescuento = $request->input('porcentaje_descuento', 0);
            $montoDescuento      = ($subtotalGeneral * $porcentajeDescuento) / 100;
            $totalNeto           = $subtotalGeneral - $montoDescuento;

            // 4. Empaquetar variables para la vista Blade
            $data = [
                'cliente'             => $cliente,
                'articulos'           => $detallesPresupuesto,
                'subtotal_general'    => $subtotalGeneral,
                'porcentaje_descuento'=> $porcentajeDescuento,
                'monto_descuento'     => $montoDescuento,
                'total_neto'          => $totalNeto,
                'observacion'         => $request->input('observacion'),
                'fecha_emision'       => Carbon::now(),
                'validez'             => Carbon::now()->addDays(5),
                'generado_por'        => Auth::user()->name ?? 'Sistema'
            ];

            // 5. Renderizar y retornar como PDF optimizado
            $pdf = Pdf::loadView('ventas.presupuesto_pdf', $data);
            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream("Presupuesto_{$cliente->identificacion}.pdf");

        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al generar el presupuesto: ' . $e->getMessage());
        }
    } 

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $venta = Venta::with(['detalles', 'credito', 'infoAdicional'])->findOrFail($id);

            if ($venta->estado === 'anulada') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Esta venta ya se encuentra anulada.'
                ], 422);
            }

            // 1. Cambiar el estado de la venta
            $venta->estado = 'anulada';
            $venta->save();

            // 2. Devolver el stock al local correspondiente
            foreach ($venta->detalles as $detalle) {
                // Validar cantidad antes de procesar
                if (!is_numeric($detalle->cantidad) || $detalle->cantidad <= 0) {
                    Log::warning("Cantidad inválida al anular venta {$venta->id}, insumo {$detalle->id_insumo}");
                    continue;
                }

                $insumoCantidad = DB::table('insumos_has_cantidades')
                    ->where('id_insumo', $detalle->id_insumo)
                    ->where('id_local', $venta->id_local)
                    ->first();

                if ($insumoCantidad) {
                    DB::table('insumos_has_cantidades')
                        ->where('id', $insumoCantidad->id)
                        ->increment('cantidad', $detalle->cantidad);
                } else {
                    // Registrar en logs si el insumo no existe en el local
                    Log::warning("Intento de devolución de stock en insumo inexistente: Venta {$venta->id}, insumo {$detalle->id_insumo}, local {$venta->id_local}");
                }
            }



            // 3. Manejar créditos asociados si existen
            if ($venta->credito) {
                $venta->credito->update([
                    'estado' => 'anulado',
                    'saldo_pendiente' => 0
                ]);
            }

            // 4. Liberar o anular correlativo de factura/nota si aplica
            if ($venta->infoAdicional && $venta->infoAdicional->correlativo_nota) {
                DB::table('correlativos')
                    ->where('venta_id', $venta->id)
                    ->update(['estado' => 'anulado', 'venta_id' => null]);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Venta anulada exitosamente y stock devuelto al inventario.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Ocurrió un error al anular la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}
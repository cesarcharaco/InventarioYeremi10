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
    
    $local = auth()->user()->localActual(); // Usamos tu método del modelo User

    $oferta = ConfigOfertas::obtenerActiva($local ? $local->id : null);
    
    $ofertasActivas = !is_null($oferta);
    $motivoOferta = $oferta ? $oferta->motivo : '';

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
    }

    $tasa_bcv = bcv_rate('USD');
    if ($tasa_bcv == 0) {
        return redirect()->route('home')->with('error', 'Actualizando valor de TASA BCV');
    }

    // --- Obtener el correlativo para la vista ---
    $ultimo = DB::table('ventas_info_adicional')
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
        $join->on('pr.local_id', '=', DB::raw($local->id))
             ->where('pr.activo', '=', 1)
             ->whereDate('pr.fecha_inicio', '<=', $hoy)
             ->whereDate('pr.fecha_fin', '>=', $hoy)
             ->where(function($q) {
                 $q->where(function($sub) {
                     $sub->where('pr.alcance', 'insumo')
                         ->on('pr.referencia_id', '=', 'insumos.id');
                 })->orWhere(function($sub) {
                     $sub->where('pr.alcance', 'categoria')
                         ->on('pr.referencia_id', '=', 'insumos.categoria_id');
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
        $user = Auth::user();
        $local = $user->localActual();
        $id_caja = $request->id_caja; 

        if (!$id_caja) {
            return redirect()->back()->with('error', 'Debe especificar una caja válida para procesar la venta.');
        }

        // Mapeamos los campos individuales del form al array de referencias
        $referenciasProcesadas = [];
        
        if ($request->pago_zelle_usd > 0) {
            $referenciasProcesadas[] = [
                'metodo' => 'Zelle',
                'referencia' => $request->referencia_zelle ?? 'S/R',
                'monto_usd' => $request->pago_zelle_usd,
                'monto_bs' => 0
            ];
        }
        if ($request->pago_punto_bs > 0) {
            $referenciasProcesadas[] = [
                'metodo' => 'Punto',
                'referencia' => $request->referencia_punto ?? 'S/R',
                'monto_bs' => $request->pago_punto_bs,
                'monto_usd' => 0
            ];
        }
        if ($request->pago_pagomovil_bs > 0) {
            $referenciasProcesadas[] = [
                'metodo' => 'Pago Movil',
                'referencia' => $request->referencia_pagomovil ?? 'S/R',
                'monto_bs' => $request->pago_pagomovil_bs,
                'monto_usd' => 0
            ];
        }

        $request->merge(['referencias' => array_merge($request->referencias ?? [], $referenciasProcesadas)]);
        $tasa_bcv = bcv_rate('USD');

        DB::beginTransaction();
        try {
            $correlativoFiscal = null; // Variable para almacenar el objeto en caso de ser factura

            // 1. Determinar el código (Factura, Nota de Entrega o Sin Documento)
            if ($request->tipo_documento === 'nota_entrega') {
                
                $codigo = 'NE-' . $request->correlativo_nota;

            } elseif ($request->tipo_documento === 'factura') {
                
                // Buscar el siguiente correlativo fiscal disponible con bloqueo de fila
                $correlativoFiscal = Correlativo::where('estado', 'disponible')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();

                if (!$correlativoFiscal) {
                    throw new \Exception("No hay correlativos de factura fiscal disponibles en el sistema. Por favor cargue un nuevo lote.");
                }

                // Asignamos el prefijo FAC- + el número de factura
                $codigo = 'FAC-' . $correlativoFiscal->numero_factura;

            } else { // sin_documento
                $codigo = 'V-' . uniqid();
            }

            // 2. Crear la Venta (Cabecera)
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

            // 2.1 Si fue factura fiscal, marcamos el correlativo como usado
            if ($correlativoFiscal) {
                $correlativoFiscal->update([
                    'estado'    => 'usado',
                    'venta_id'  => $venta->id,
                    'fecha_uso' => now()
                ]);
            }

            // 3. Extensión de información (Tabla: ventas_info_adicional)
            $venta->infoAdicional()->create([
                'tipo_documento'       => $request->tipo_documento,
                'correlativo_nota'     => $request->tipo_documento === 'factura' ? $correlativoFiscal->numero_factura : $request->correlativo_nota,
                'numero_control'       => $correlativoFiscal ? $correlativoFiscal->numero_control : null, // Guardamos también el # de control si existe la columna
                'porcentaje_descuento' => $request->porcentaje_descuento ?? 0,
                'monto_descuento_usd'  => $request->monto_descuento_usd ?? 0,
                'base_imponible_bs'    => $request->base_imponible_bs ?? 0,
                'iva_bs'               => $request->iva_bs ?? 0,
                'aplica_abono'         => $request->has('pago_excedente_abono')
            ]);

            // 4. Referencias Bancarias
            if ($request->has('referencias')) {
                foreach ($request->referencias as $ref) {
                    $venta->referencias()->create([
                        'metodo'     => $ref['metodo'],
                        'referencia' => $ref['referencia'],
                        'monto_bs'   => $ref['monto_bs'] ?? 0,
                        'monto_usd'  => $ref['monto_usd'] ?? 0,
                    ]);
                }
            }

            // 5. Detalles de Venta y Descuento de Stock
            foreach ($request->articulos as $item) {
                $venta->detalles()->create([
                    'id_insumo'       => $item['id_insumo'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $item['cantidad'] * $item['precio_unitario']
                ]);

                $existencia = InsumosC::where('id_insumo', $item['id_insumo'])
                                     ->where('id_local', $local->id)
                                     ->first();

                if (!$existencia || $existencia->cantidad < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para: " . $item['nombre']);
                }

                $existencia->decrement('cantidad', $item['cantidad']);

                $insumoBase = Insumos::find($item['id_insumo']);
                $nuevaCantidad = $existencia->fresh()->cantidad;

                if ($nuevaCantidad <= $insumoBase->stock_min) {
                    $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
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

            // 6. Lógica de ABONO AUTOMÁTICO
            if ($request->has('aplica_abono') && $request->monto_excedente > 0) {
                $creditoOld = Credito::where('id_cliente', $request->id_cliente)
                                    ->where('estado', 'pendiente')
                                    ->lockForUpdate()
                                    ->first();

                if ($creditoOld) {
                    AbonoCredito::create([
                        'id_credito'        => $creditoOld->id,
                        'id_user'           => $user->id,
                        'id_caja'           => $id_caja,
                        'monto_pagado_usd'  => $request->monto_excedente,
                        'pago_usd_efectivo' => $request->exc_usd_efectivo ?? 0,
                        'pago_bs_efectivo'  => $request->exc_bs_efectivo ?? 0,
                        'detalles'          => "Abono automático desde Venta: " . $codigo,
                        'estado'            => 'Realizado'
                    ]);

                    $creditoOld->decrement('saldo_pendiente', $request->monto_excedente);

                    if ($creditoOld->fresh()->saldo_pendiente <= 0) {
                        $creditoOld->update(['estado' => 'pagado', 'saldo_pendiente' => 0]);
                    }
                }
            }

            // 7. Si esta venta genera un crédito NUEVO
            if ($request->monto_credito_usd > 0) {
                Credito::create([
                    'id_venta'          => $venta->id,
                    'id_cliente'        => $request->id_cliente,
                    'monto_inicial'     => $request->monto_credito_usd,
                    'saldo_pendiente'   => $request->monto_credito_usd,
                    'fecha_vencimiento' => now()->addDays(15), 
                    'estado'            => 'pendiente',
                    'tasa_cambio_origen'=> $tasa_bcv
                ]);

                $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
                $detalles = [
                    'titulo'  => '💸 Nueva Venta a Crédito',
                    'mensaje' => "Se otorgó un crédito de {$request->monto_credito_usd}$ a {$request->cliente_nombre}.",
                    'url'     => route('creditos.index'),
                    'icono'   => 'fas fa-hand-holding-usd text-info'
                ];

                foreach ($gerentes as $gerente) {
                    $gerente->notify(new StockBajoNotification($detalles));
                }
            }

            DB::commit();

            // 8. Respuesta con modal de impresión
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

            return redirect()->route('ventas.create')->with('success', "Venta {$codigo} guardada.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
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
        $pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user = Auth::user();
        $local = $user->localActual();

        // Guardamos o actualizamos la solicitud del local
        AutorizacionPin::updateOrCreate(
            ['id_local' => $local->id],
            [
                'pin' => $pin,
                'monto' => $request->monto_total,
                'vendedor' => auth()->user()->name,
                'cliente' => $request->cliente_nombre,
                'estado' => 'activo',
                'updated_at' => now()
            ]
        );

        // --- NOTIFICACIÓN AL GERENTE ---
            $gerentes = User::whereIn('role', ['admin'])->get();
            $detalles = [
                'titulo'  => '🔐 Solicitud de PIN',
                'mensaje' => "{$user->name} en {$local->nombre} solicita PIN para una venta de {$request->monto_total}$",
                'url'     => '#', // O al dashboard de autorizaciones si tienes uno
                'icono'   => 'fas fa-key text-warning'
            ];

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }

        return response()->json(['success' => true, 'message' => 'PIN generado en Dashboard']);
    }

    public function verificarPin(Request $request)
    {
        $user = Auth::user();
        $local = $user->localActual();
        $auth = AutorizacionPin::where('id_local', $local->id)
                    ->where('estado', 'activo')
                    ->first();

        if ($auth && $request->pin == $auth->pin) {
            $auth->update(['estado' => 'usado']); // Marcamos como usado
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'PIN incorrecto o expirado'], 422);
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
}
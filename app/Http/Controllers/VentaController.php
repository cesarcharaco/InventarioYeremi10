<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Credito;
use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Cliente;
use App\Models\Caja;
use App\Models\AbonoCredito;
use App\Models\PagoReferencia;
use App\Models\AutorizacionPin;
use App\Models\ConfigOfertas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Notifications\StockBajoNotification;
use App\Models\User;
use App\Models\Configuracion;

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

    $tasa_bcv = Configuracion::getTasa('tasa_bcv');
    if ($tasa_bcv == 0) {
        return redirect()->route('home')->with('error', 'Actualizando valor de TASA BCV');
    }

    // --- NUEVO: Obtener el correlativo para la vista ---
    $ultimo = DB::table('ventas_info_adicional')
                ->whereNotNull('correlativo_nota')
                ->orderBy('id', 'desc')
                ->first();
    
    $siguiente = $ultimo ? (intval($ultimo->correlativo_nota) + 1) : 1;
    $correlativo_sugerido = str_pad($siguiente, 7, '0', STR_PAD_LEFT);

    // --- NUEVO: Definir descuentos permitidos ---
    $descuentos = [10, 15, 20, 25, 30, 35, 40, 45, 50];

    // Carga de productos (Insumos) con stock en el local actual
    /*$productos = Insumos::with(['existencias' => function($q) use ($local) {
        $q->where('id_local', $local->id);
    }])->whereHas('existencias', function($q) use ($local) {
        $q->where('id_local', $local->id)->where('cantidad', '>', 0);
    })->get();*/
    $productos = Insumos::with(['existencias' => function($q) use ($local) {
        $q->where('id_local', $local->id);
    }])
    ->whereHas('existencias', function($q) use ($local) {
        $q->where('id_local', $local->id)->where('cantidad', '>', 0);
    })
    ->get();

    $clientes = Cliente::where('activo', 'activo')
        ->withSum(['creditos as saldo_pendiente_total' => function($q) {
            $q->where('estado', 'pendiente');
        }], 'saldo_pendiente')
        ->get();
    
    return view('ventas.create', compact(
        'productos', 
        'clientes', 
        'local', 
        'caja', 
        'tasa_bcv', 
        'correlativo_sugerido', 
        'descuentos',
        'ofertasActivas',
        'motivoOferta'
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


    //dd($request->all());

    // Mapeamos los campos individuales del form al array de referencias que ya procesa tu store
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
            'monto_usd' => 0 // O el cálculo en USD si lo prefieres
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

    // Fusionamos con las referencias que ya venían (si las hay)
    $request->merge(['referencias' => array_merge($request->referencias ?? [], $referenciasProcesadas)]);

    DB::beginTransaction();
    try {
        // 1. Determinar el código (Factura o Nota de Entrega)
        if ($request->tipo_documento === 'nota_entrega') {
            $codigo = 'NE-' . $request->correlativo_nota;
        } elseif ($request->tipo_documento === 'factura') {
            $codigo = 'FAC-'; // lógica factura
        } else { // sin_documento
            $codigo = 'V-' . uniqid(); // o tu prefijo para ventas sin doc
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
            'total_usd'         => $request->total_usd, // El total neto enviado desde la vista
            'estado'            => 'completada'
        ]);

        // 3. Extensión de información (Tabla: ventas_info_adicional)
        $venta->infoAdicional()->create([
            'tipo_documento'       => $request->tipo_documento,
            'correlativo_nota'     => $request->correlativo_nota,
            'porcentaje_descuento' => $request->porcentaje_descuento ?? 0,
            'monto_descuento_usd'  => $request->monto_descuento_usd ?? 0,
            'base_imponible_bs'    => $request->base_imponible_bs ?? 0,
            'iva_bs'               => $request->iva_bs ?? 0,
            'aplica_abono' => $request->has('pago_excedente_abono')
        ]);


        // 4. Referencias Bancarias (Tabla: pago_referencias)
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

        // 5. Detalles de Venta y Descuento de Stock (Tabla: insumos_has_cantidades)
        foreach ($request->articulos as $item) {
            $venta->detalles()->create([
                'id_insumo'       => $item['id_insumo'],
                'cantidad'        => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal'        => $item['cantidad'] * $item['precio_unitario']
            ]);

            // Descuento de stock usando el modelo InsumosC que me pasaste
            $existencia = InsumosC::where('id_insumo', $item['id_insumo'])
                                 ->where('id_local', $local->id)
                                 ->first();

            if (!$existencia || $existencia->cantidad < $item['cantidad']) {
                throw new \Exception("Stock insuficiente para: " . $item['nombre']);
            }

            $existencia->decrement('cantidad', $item['cantidad']);
            // --- NUEVA LÓGICA DE ALERTA DE STOCK ---
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

        // 6. Lógica de ABONO AUTOMÁTICO (Si el cliente tenía deuda y pagó de más)
        if ($request->has('aplica_abono') && $request->monto_excedente > 0) {
            $creditoOld = Credito::where('id_cliente', $request->id_cliente)
                                ->where('estado', 'pendiente')
                                ->lockForUpdate()
                                ->first();

            if ($creditoOld) {
                // Registramos el abono en la tabla abonos_creditos
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

                // Bajamos el saldo pendiente
                $creditoOld->decrement('saldo_pendiente', $request->monto_excedente);

                // Si se liquidó, cambiamos estado
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
                'tasa_cambio_origen'=> Configuracion::getTasa('tasa_bcv')
            ]);
            /*Notificaciones*/
            $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();
            $detalles = [
                'titulo'  => '💸 Nueva Venta a Crédito',
                'mensaje' => "Se otorgó un crédito de {$request->monto_credito_usd}$ a {$request->cliente_nombre}.",
                'url'     => route('creditos.index'), // Ajusta a tu ruta de créditos
                'icono'   => 'fas fa-hand-holding-usd text-info'
            ];

            foreach ($gerentes as $gerente) {
                $gerente->notify(new StockBajoNotification($detalles));
            }
        }

        DB::commit();
        return redirect()->route('ventas.index')->with('success', "Venta {$codigo} guardada.");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
    }
}

    public function show($id)
    {
        // Cargamos 'usuario' (no user) e 'insumo' (no producto)
        $venta = Venta::with(['cliente', 'detalles.insumo', 'usuario', 'local', 'credito'])->findOrFail($id);

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
}
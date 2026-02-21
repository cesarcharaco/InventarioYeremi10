<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Credito;
use App\Models\Insumos;
use App\Models\Cliente;
use App\Models\Caja;
use App\Models\AutorizacionPin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('ver-historial-ventas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $user = Auth::user();
        $query = Venta::with(['cliente', 'usuario', 'local']);

        // Si no tiene gate para auditar, solo ve su local
        if (Gate::denies('auditar-cajas')) {
            $local = $user->localActual();
            $query->where('id_local', $local->id);
        }

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->fecha_desde)->startOfDay(),
                Carbon::parse($request->fecha_hasta)->endOfDay()
            ]);
        }

        $ventas = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'No tienes permiso para operar caja.');
        }

        $user = Auth::user();
        $local = $user->localActual();
        
        // Verificamos que haya una caja abierta para este usuario y local
        $caja = Caja::where('id_user', $user->id)
                    ->where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            return redirect()->route('cajas.index')->with('error', 'Debe abrir caja antes de vender.');
        }

        $productos = Insumos::with(['existencias' => function($q) use ($local) {
            $q->where('id_local', $local->id);
        }])->whereHas('existencias', function($q) use ($local) {
            $q->where('id_local', $local->id)->where('cantidad', '>', 0);
        })->get();

        $clientes = Cliente::where('activo', true)->get();
        //buscando la tasa actual
        $responseBcv = Http::withOptions(['verify' => false])->get('https://www.bcv.org.ve/');
            if ($responseBcv->successful()) {
                preg_match('/id="dolar".*?<strong>\s*(.*?)\s*<\/strong>/s', $responseBcv->body(), $matches);
                $tasa_bcv = isset($matches[1]) ? (float) str_replace(',', '.', trim($matches[1])) : 0;
            }else{
                $tasa_bcv=0;
            }
        return view('ventas.create', compact('productos', 'clientes', 'local', 'caja','tasa_bcv'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'Permiso denegado.');
        }

        $request->validate([
            'id_cliente'        => 'required|exists:clientes,id',
            'articulos'         => 'required|array|min:1',
            'articulos.*.id_insumo' => 'required|exists:insumos,id',
            'articulos.*.cantidad'  => 'required|numeric|min:0.1',
            // Pagos
            'pago_usd_efectivo' => 'nullable|numeric|min:0',
            'pago_bs_efectivo'  => 'nullable|numeric|min:0',
            'pago_punto_bs'     => 'nullable|numeric|min:0', // Aquí frontend suma Punto + Biopago
            'pago_pagomovil_bs' => 'nullable|numeric|min:0', // Aquí frontend suma Pago Móvil + Transferencia
            'monto_credito_usd' => 'nullable|numeric|min:0',
            'total_usd'         => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $local = $user->localActual();
        
        // Buscamos la caja activa directamente de la DB para evitar manipulaciones de sesión
        $caja = Caja::where('id_user', $user->id)
                    ->where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) return redirect()->back()->with('error', 'No hay una caja abierta detectada.');

        DB::beginTransaction();
        try {
            // 1. Crear la Venta (consolidando los campos acordados)
            $venta = Venta::create([
                'codigo_factura'    => 'V-' . strtoupper(substr(uniqid(), -7)),
                'id_cliente'        => $request->id_cliente,
                'id_user'           => $user->id,
                'id_local'          => $local->id,
                'id_caja'           => $caja->id,
                'pago_usd_efectivo' => $request->pago_usd_efectivo ?? 0,
                'pago_bs_efectivo'  => $request->pago_bs_efectivo ?? 0,
                'pago_punto_bs'     => $request->pago_punto_bs ?? 0,
                'pago_pagomovil_bs' => $request->pago_pagomovil_bs ?? 0,
                'monto_credito_usd' => $request->monto_credito_usd ?? 0,
                'total_usd'         => $request->total_usd,
                'estado'            => 'completada'
            ]);

            // 2. Procesar Artículos y Stock
            foreach ($request->articulos as $art) {
                // Validación de stock de último segundo (Prevención de condiciones de carrera)
                $stockActual = DB::table('insumos_has_cantidades')
                    ->where('id_insumo', $art['id_insumo'])
                    ->where('id_local', $local->id)
                    ->lockForUpdate() // Bloqueamos la fila hasta que termine la transacción
                    ->first();

                if (!$stockActual || $stockActual->cantidad < $art['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto ID: {$art['id_insumo']}");
                }

                DetalleVenta::create([
                    'id_venta'        => $venta->id,
                    'id_insumo'       => $art['id_insumo'],
                    'cantidad'        => $art['cantidad'],
                    'precio_unitario' => $art['precio_unitario'],
                    'subtotal'        => $art['cantidad'] * $art['precio_unitario']
                ]);

                // Descuento de inventario
                DB::table('insumos_has_cantidades')
                    ->where('id_insumo', $art['id_insumo'])
                    ->where('id_local', $local->id)
                    ->decrement('cantidad', $art['cantidad']);
            }

            // 3. Si hay crédito, generamos la deuda
            if ($request->monto_credito_usd > 0) {
                Credito::create([
                    'id_venta'          => $venta->id,
                    'id_cliente'        => $request->id_cliente,
                    'monto_inicial'     => $request->monto_credito_usd,
                    'saldo_pendiente'   => $request->monto_credito_usd,
                    'fecha_vencimiento' => Carbon::now()->addDays(15), // Ejemplo: 15 días crédito
                    'estado'            => 'pendiente'
                ]);
            }

            DB::commit();
            return redirect()->route('ventas.index')->with('success', "Venta {$venta->codigo_factura} procesada.");

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
}
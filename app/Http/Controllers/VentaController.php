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

    // ... dentro de VentaController

    public function create()
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'No tienes permiso.');
        }

        $user = Auth::user();
        $local = $user->localActual();
        
        // Verificación de seguridad por si el usuario no tiene local asignado
        if (!$local) {
            return redirect()->route('home')->with('error', 'No tienes un local activo asignado.');
        }

        // Buscamos la caja abierta del local
        $caja = Caja::where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            return redirect()->route('cajas.create')->with('error', 'No hay una caja abierta en este local.');
        }

        // --- OMISIÓN: Tasa BCV ---
        // Asegúrate de tener esta lógica o la que uses normalmente
       $tasa_bcv = cache('tasa_bcv', 0);
       if ($tasa_bcv == 0) {
            // Opcional: Si el cache expiró, podrías redirigir al home para que se actualice
            return redirect()->route('home')->with('error', 'Actualizando valor de TASA BCV');
        }

        $productos = Insumos::with(['existencias' => function($q) use ($local) {
            $q->where('id_local', $local->id);
        }])->whereHas('existencias', function($q) use ($local) {
            $q->where('id_local', $local->id)->where('cantidad', '>', 0);
        })->get();
        
        $clientes = Cliente::where('activo', true)->get();
        
        return view('ventas.create', compact('productos', 'clientes', 'local', 'caja', 'tasa_bcv'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $local = $user->localActual();
        
        // OMISIÓN: Usar la caja validada por el Middleware (Session)
        // Esto garantiza que la venta se asigne a la caja que el Middleware verificó
        $id_caja = session('id_caja_activa');

        if (!$id_caja) {
            return redirect()->back()->with('error', 'Sesión de caja no encontrada.');
        }

        DB::beginTransaction();
        try {
            $venta = Venta::create([
                'codigo_factura'    => 'V-' . strtoupper(substr(uniqid(), -7)),
                'id_cliente'        => $request->id_cliente,
                'id_user'           => $user->id, 
                'id_local'          => $local->id,
                'id_caja'           => $id_caja, // <--- Asignación directa desde sesión
                'pago_usd_efectivo' => $request->pago_usd_efectivo ?? 0,
                'pago_bs_efectivo'  => $request->pago_bs_efectivo ?? 0,
                'pago_punto_bs'     => $request->pago_punto_bs ?? 0,
                'pago_pagomovil_bs' => $request->pago_pagomovil_bs ?? 0,
                'monto_credito_usd' => $request->monto_credito_usd ?? 0,
                'total_usd'         => $request->total_usd,
                'estado'            => 'completada'
            ]);

            // ... lógica de detalles y stock
            
            DB::commit();
            return redirect()->route('ventas.index')->with('success', "Venta procesada con éxito.");
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
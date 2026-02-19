<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Credito;
use App\Models\Insumos;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query base
        $query = Venta::with(['cliente', 'usuario', 'local']);

        // USO DE TU GATE 'auditar-cajas': 
        // Si NO puede auditar, solo ve las ventas de su local actual
        if (Gate::denies('auditar-cajas')) {
            $local = $user->localActual();
            $query->where('id_local', $local ? $local->id : 0);
        }

        $ventas = $query->orderBy('created_at', 'desc')->get();
        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        // Validación con tu Gate
        if (Gate::denies('operar-caja')) {
            abort(403, 'No tienes permiso para acceder al panel de ventas.');
        }

        $user = Auth::user();
        $local = $user->localActual();

        if (!$local) {
            return redirect()->route('home')->with('error', 'No tienes una sede activa asignada.');
        }

        // Productos con stock en el local usando tu tabla pivote
        $productos = Insumos::with(['existencias' => function($query) use ($local) {
            $query->where('id_local', $local->id);
        }])
        ->whereHas('existencias', function($query) use ($local) {
            $query->where('id_local', $local->id)->where('cantidad', '>', 0);
        })
        ->get();
        //dd($productos->first()->existencias->first()->cantidad);
        $clientes = Cliente::where('activo', true)->get();

        return view('ventas.create', compact('productos', 'clientes', 'local'));
    }

    public function store(Request $request)
{
    // Validación con tus Gates
    if (Gate::denies('operar-caja')) {
        return redirect()->back()->with('error', 'No tiene permisos para vender.');
    }

    $request->validate([
        'id_cliente' => 'required|exists:clientes,id',
        'articulos' => 'required|array|min:1',
        'total_usd' => 'required|numeric', // Total ya calculado según el modelo
    ]);

    $user = Auth::user();
    $local = $user->localActual();
    $id_caja = session('id_caja_activa');

    if (!$id_caja) {
        return redirect()->back()->with('error', 'Debe abrir caja antes de vender.');
    }

    DB::beginTransaction();
    try {
        $venta = Venta::create([
            'codigo_factura' => 'V-' . strtoupper(substr(uniqid(), -7)),
            'id_cliente' => $request->id_cliente,
            'id_user' => $user->id,
            'id_local' => $local->id,
            'id_caja' => $id_caja,
            'total_usd' => $request->total_usd,
            'tasa_dia' => 0, // Como no usamos tasa, lo dejamos en 0 o el valor base del modelo
            'estado' => 'completada'
        ]);

        foreach ($request->articulos as $art) {
            // El precio_unitario ya viene pre-cargado en el frontend 
            // desde la tabla modelo_ventas vinculada al insumo.
            
            DetalleVenta::create([
                'id_venta' => $venta->id,
                'id_insumo' => $art['id_insumo'],
                'cantidad' => $art['cantidad'],
                'precio_unitario' => $art['precio_unitario'], 
                'subtotal' => $art['cantidad'] * $art['precio_unitario']
            ]);

            // Descontar de tu tabla pivot insumos_has_cantidades
            DB::table('insumos_has_cantidades')
                ->where('id_insumo', $art['id_insumo'])
                ->where('id_local', $local->id)
                ->decrement('cantidad', $art['cantidad']);
        }

        DB::commit();
        return redirect()->route('ventas.index')->with('success', 'Venta procesada exitosamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Error en la venta: ' . $e->getMessage());
    }
}
}
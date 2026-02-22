<?php

namespace App\Http\Controllers;

use App\Models\EntradaAlmacen; // Modelo corregido
use App\Models\DetalleEntrada; // Modelo corregido
use App\Models\Proveedor;
use App\Models\Insumos;        // Tu modelo de productos
use App\Models\Local;
use App\Models\InsumosC;       // Tu modelo de stock (insumos_has_cantidades)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class EntradaController extends Controller
{
    public function index()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'No tiene permisos para ver el historial.');
        }

        // Cargamos con las relaciones definidas en EntradaAlmacen
        $entradas = EntradaAlmacen::with(['proveedor', 'usuario', 'local'])
                                   ->orderBy('created_at', 'desc')->get();

        return view('entradas.index', compact('entradas'));
    }

    public function create()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $proveedores = Proveedor::orderBy('nombre', 'asc')->get();
        $insumos = Insumos::where('estado', '!=', 'Suspendido')
                          ->orderBy('producto', 'asc')
                          ->get();
        
        $depositos = Local::where('tipo', 'DEPOSITO')
                          ->where('estado', 'Activo')
                          ->orderBy('nombre', 'asc')
                          ->get();
        
        if($depositos->isEmpty()){
            return redirect()->back()->with('warning', 'No existen locales configurados como DEPOSITO.');
        }

        return view('entradas.create', compact('proveedores', 'insumos', 'depositos'));
    }

    public function store(Request $request)
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id',
            'id_local'     => 'required|exists:local,id',
            'items'        => 'required|array|min:1',
            'items.*.id_insumo'       => 'required|exists:insumos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.01',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear cabecera usando EntradaAlmacen y sus campos reales
            $entrada = EntradaAlmacen::create([
                'id_proveedor'      => $request->id_proveedor,
                'id_local'          => $request->id_local,
                'id_user'           => auth()->id(),
                'nro_orden_entrega' => $request->nro_orden_entrega, // Asegúrate de tener este input en el create
                'fecha_entrada'     => now(),
                'total_costo_usd'   => collect($request->items)->sum(function($item) {
                    return $item['cantidad'] * $item['costo_unitario'];
                }),
                'observaciones'     => $request->observaciones
            ]);

            foreach ($request->items as $item) {
                // 2. Crear detalle usando DetalleEntrada y sus campos reales
                $entrada->detalles()->create([
                    'id_insumo'          => $item['id_insumo'],
                    'cantidad'           => $item['cantidad'],
                    'costo_unitario_usd' => $item['costo_unitario'], // Nombre de columna corregido
                ]);

                // 3. Actualizar stock en InsumosC (tabla insumos_has_cantidades)
                $stock = InsumosC::where('id_insumo', $item['id_insumo'])
                    ->where('id_local', $request->id_local)
                    ->first();

                if ($stock) {
                    $stock->increment('cantidad', $item['cantidad']);
                } else {
                    InsumosC::create([
                        'id_insumo' => $item['id_insumo'],
                        'id_local'  => $request->id_local,
                        'cantidad'  => $item['cantidad']
                    ]);
                }
                
                // 4. Actualizar costo maestro en Insumos
                Insumos::where('id', $item['id_insumo'])->update([
                    'costo' => $item['costo_unitario']
                ]);
            }

            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada de almacén registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Uso de relaciones corregidas: 'usuario' en lugar de 'user'
        $entrada = EntradaAlmacen::with(['proveedor', 'usuario', 'local', 'detalles.insumo'])->findOrFail($id);
        return view('entradas.show', compact('entrada'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $entrada = EntradaAlmacen::with('detalles')->findOrFail($id);

            foreach ($entrada->detalles as $detalle) {
                // Revertir el stock antes de eliminar
                InsumosC::where('id_insumo', $detalle->id_insumo)
                    ->where('id_local', $entrada->id_local)
                    ->decrement('cantidad', $detalle->cantidad);
            }

            $entrada->detalles()->delete();
            $entrada->delete();

            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada anulada y stock revertido.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }
}
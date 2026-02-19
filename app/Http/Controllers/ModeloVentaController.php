<?php
namespace App\Http\Controllers;

use App\Models\ModeloVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModeloVentaController extends Controller
{
    public function index()
    {
        Gate::authorize('gestionar-modelos-venta');
        $modelos = ModeloVenta::withCount('insumos')->paginate(15);
        return view('modelos-venta.index', compact('modelos'));
    }

    public function create()
    {
        Gate::authorize('gestionar-modelos-venta');
        return view('modelos-venta.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('gestionar-modelos-venta');
        $request->validate([
            'modelo' => 'required|string|max:255',
            'tasa_binance' => 'required|numeric|min:0',
            'tasa_bcv' => 'required|numeric|min:0',
        ]);

        $data = $request->all();

        // Lógica de exclusión: Si eligió factor, anulamos porcentaje y viceversa
        if ($request->metodo_calculo == 'porcentaje') {
            $data['factor_bcv'] = null;
            $data['factor_usdt'] = null;
        } else {
            $data['porcentaje_extra'] = null;
        }

        ModeloVenta::create($data);

        return redirect()->route('modelos-venta.index')
            ->with('success', 'Modelo de venta creado exitosamente.');
    }

    // Método para devolver los datos al JavaScript de la vista de Insumos
    public function getDatos($id)
    {
        Gate::authorize('gestionar-modelos-venta');
        $modelo = ModeloVenta::find($id);
        if (!$modelo) {
            return response()->json(['error' => 'Modelo no encontrado'], 404);
        }
        return response()->json($modelo);
    }

    public function edit($id)
    {
        Gate::authorize('gestionar-modelos-venta');
        $modeloVenta = ModeloVenta::findOrFail($id);
        return view('modelos-venta.edit', compact('modeloVenta'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('gestionar-modelos-venta');
        $request->validate([
            'modelo' => 'required|string|max:255',
            'tasa_binance' => 'required|numeric|min:0',
            'tasa_bcv' => 'required|numeric|min:0',
        ]);

        $modeloVenta = ModeloVenta::findOrFail($id);
        $data = $request->all();

        if ($request->metodo_calculo == 'porcentaje') {
            $data['factor_bcv'] = null;
            $data['factor_usdt'] = null;
        } else {
            $data['porcentaje_extra'] = null;
        }

        $modeloVenta->update($data);

        return redirect()->route('modelos-venta.index')
            ->with('success', 'Modelo de venta actualizado correctamente.');
    }

    public function destroy($id)
    {
        Gate::authorize('gestionar-modelos-venta');
        $modeloVenta = ModeloVenta::findOrFail($id);
        
        // Opcional: Validar si hay insumos usando este modelo antes de borrar
        if ($modeloVenta->insumos()->count() > 0) { 
            return redirect()->route('modelos-venta.index')
                ->with('error', 'No se puede eliminar el Modelo debido a que hay Insumos asignados dicho modelo, debe cambiarlos a otro modelo o eliminarlos');    
         }else{
            $modeloVenta->delete();

            return redirect()->route('modelos-venta.index')
                ->with('success', 'Modelo eliminado correctamente.');    
         }

        
    }

}

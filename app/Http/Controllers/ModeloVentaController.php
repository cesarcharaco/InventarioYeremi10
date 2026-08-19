<?php

namespace App\Http\Controllers;

use App\Models\ModeloVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

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

        // Limpieza según el método seleccionado
        if ($request->metodo_calculo == 'porcentaje') {
            $data['factor_bcv'] = null;
            $data['factor_usdt'] = null;
        } else {
            $data['porcentaje_extra'] = null;
        }

        // 1. Actualizamos el Modelo de Venta primero
        $modeloVenta->update($data);

        $mensajeExito = 'Modelo de venta actualizado correctamente.';

        // 2. Verificación y Proceso Masivo si la casilla fue marcada
        if ($request->has('actualizar_precios_insumos') && $request->actualizar_precios_insumos == 1) {
            $insumosActualizados = 0;

            DB::transaction(function () use ($modeloVenta, &$insumosActualizados) {
                // Procesamiento por lotes (Chunks de 200 en 200) para evitar saturar RAM
                $modeloVenta->insumos()->chunkById(200, function ($insumos) use ($modeloVenta, &$insumosActualizados) {
                    foreach ($insumos as $insumo) {
                        $costo = floatval($insumo->costo);

                        if ($modeloVenta->porcentaje_extra !== null) {
                            // Cálculo por Porcentaje Extra
                            $pctExtra = floatval($modeloVenta->porcentaje_extra) / 100;
                            $precioUsd = $costo + ($costo * $pctExtra);

                            // NOMBRES CORREGIDOS SEGÚN EL MODELO INSUMOS
                            $insumo->precio_venta_usd  = $precioUsd;
                            $insumo->precio_venta_usdt = $precioUsd;
                            $insumo->precio_venta_bs   = $precioUsd * floatval($modeloVenta->tasa_bcv);
                        } else {
                            // Cálculo por Factores Divisores
                            $factorBcv = floatval($modeloVenta->factor_bcv);
                            $factorUsdt = floatval($modeloVenta->factor_usdt);

                            $bcvUsd  = $factorBcv > 0 ? ($costo / $factorBcv) : 0;
                            $usdtUsd = $factorUsdt > 0 ? ($costo / $factorUsdt) : 0;

                            // NOMBRES CORREGIDOS SEGÚN EL MODELO INSUMOS
                            $insumo->precio_venta_usd  = $bcvUsd;
                            $insumo->precio_venta_usdt = $usdtUsd;
                            $insumo->precio_venta_bs   = $bcvUsd * floatval($modeloVenta->tasa_bcv);
                        }

                        $insumo->save();
                        $insumosActualizados++;
                    }
                });
            });

            $mensajeExito .= " Se recalcularon y actualizaron $insumosActualizados insumos masivamente.";
        }

        return redirect()->route('modelos-venta.index')
            ->with('success', $mensajeExito);
    }

    public function destroy($id)
    {
        Gate::authorize('gestionar-modelos-venta');
        $modeloVenta = ModeloVenta::findOrFail($id);
        
        // Validar si hay insumos usando este modelo antes de borrar
        if ($modeloVenta->insumos()->count() > 0) { 
            return redirect()->route('modelos-venta.index')
                ->with('error', 'No se puede eliminar el Modelo debido a que hay Insumos asignados a dicho modelo, debe cambiarlos a otro modelo o eliminarlos.');    
        } else {
            $modeloVenta->delete();

            return redirect()->route('modelos-venta.index')
                ->with('success', 'Modelo eliminado correctamente.');    
        }
    }
}
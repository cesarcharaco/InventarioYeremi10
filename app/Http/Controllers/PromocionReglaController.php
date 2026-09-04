<?php

namespace App\Http\Controllers;

use App\Models\PromocionRegla;
use App\Models\Categoria;
use App\Models\Insumos;
use App\Models\local;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PromocionReglaController extends Controller
{
    /**
     * Muestra un listado de las reglas de promoción con su relación dinámica
     * y el conteo de cuántas veces se ha aplicado en ventas.
     */
    public function index()
    {
        $this->authorize('gestionar-promociones');

        $promociones = PromocionRegla::with(['referencia', 'local'])
            ->withCount('detalleVentas')
            ->latest()
            ->paginate(15);
        
        $insumos = Insumos::all();
        $categorias = Categoria::all();
        $locales = Local::where('tipo', 'LOCAL')->get(); // Filtrado estricto para mostrar únicamente los de tipo LOCAL
        
        return view('promociones.index', compact('promociones', 'insumos', 'categorias', 'locales'));
    }

    /**
     * Almacena una nueva regla de promoción en la base de datos.
     */
    public function store(Request $request)
    {
        $this->authorize('gestionar-promociones');

        $validatedData = $this->validatePromocion($request);
        $activo = $request->has('activo') ? $request->activo : 1;
        $localId = $validatedData['local_id'];

        // Se recorre cada ID seleccionado para registrar la promoción de forma múltiple
        foreach ($validatedData['referencia_id'] as $refId) {
            $alcance = $validatedData['alcance'];
            $fechaInicio = $validatedData['fecha_inicio'];
            $fechaFin = $validatedData['fecha_fin'];

            // Consulta base de solapamiento de fechas filtrada estrictamente por el local correspondiente
            $querySolapamiento = PromocionRegla::where('local_id', $localId)
                ->where('activo', 1)
                ->where(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('fecha_inicio', '<=', $fechaFin)
                      ->where('fecha_fin', '>=', $fechaInicio);
                });

            $hayConflicto = false;
            $mensajeError = "";

            if ($alcance === 'insumo') {
                // Verificar si el insumo ya tiene promoción directa O si su categoría matriz tiene promo activa en este local
                $insumoObj = Insumos::find($refId);
                $categoriaId = $insumoObj->categoria_id ?? null;

                $conflictoDirecto = (clone $querySolapamiento)
                    ->where('alcance', 'insumo')
                    ->where('referencia_id', $refId)
                    ->exists();

                $conflictoPorCategoria = false;
                if ($categoriaId) {
                    $conflictoPorCategoria = (clone $querySolapamiento)
                        ->where('alcance', 'categoria')
                        ->where('referencia_id', $categoriaId)
                        ->exists();
                }

                if ($conflictoDirecto || $conflictoPorCategoria) {
                    $hayConflicto = true;
                    $nombreInsumo = $insumoObj->producto ?? "Insumo ID {$refId}";
                    $mensajeError = "El insumo '{$nombreInsumo}' (o su categoría) ya posee una promoción activa o programada dentro del rango de fechas seleccionado para este local.";
                }

            } else {
                // Si el alcance es CATEGORÍA: verificar la categoría o si algún insumo hijo tiene promo individual en este local
                $conflictoCategoria = (clone $querySolapamiento)
                    ->where('alcance', 'categoria')
                    ->where('referencia_id', $refId)
                    ->exists();

                $insumosIdsEnCategoria = Insumos::where('categoria_id', $refId)->pluck('id');
                $conflictoInsumosHijos = false;

                if ($insumosIdsEnCategoria->isNotEmpty()) {
                    $conflictoInsumosHijos = (clone $querySolapamiento)
                        ->where('alcance', 'insumo')
                        ->whereIn('referencia_id', $insumosIdsEnCategoria)
                        ->exists();
                }

                if ($conflictoCategoria || $conflictoInsumosHijos) {
                    $hayConflicto = true;
                    $mensajeError = "La categoría seleccionada (o uno de sus productos internos) ya cuenta con una promoción activa en las fechas indicadas para este local.";
                }
            }

            if ($hayConflicto) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $mensajeError);
            }

            // Si pasa todas las validaciones cruzadas, se crea la regla asignada al local específico
            PromocionRegla::create([
                'local_id' => $localId,
                'nombre' => $validatedData['nombre'],
                'alcance' => $alcance,
                'referencia_id' => $refId,
                'porcentaje_descuento' => $validatedData['porcentaje_descuento'],
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'activo' => $activo,
            ]);
        }

        return redirect()->route('promociones.index')
            ->with('success', 'Reglas de promoción creadas exitosamente.');
    }

   /**
     * Actualiza una regla de promoción existente.
     */
    public function update(Request $request, $id)
    {
        $this->authorize('gestionar-promociones');

        $promocion = PromocionRegla::withCount('detalleVentas')->findOrFail($id);

        // Reglas de validación base para los campos editables
        $rules = [
            'nombre' => 'required|string|max:191',
            'porcentaje_descuento' => 'required|numeric|min:0|max:100',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ];

        // Restricciones de auditoría según el historial de ventas
        if ($promocion->detalle_ventas_count > 0) {
            // Si ya operó en ventas, bloqueamos el cambio de sucursal y de fecha de inicio para proteger los registros
            $request->merge([
                'local_id' => $promocion->local_id,
                'fecha_inicio' => $promocion->fecha_inicio
            ]);
            $rules['local_id'] = 'required|exists:local,id';
            $rules['fecha_inicio'] = 'required|date';
        } else {
            // Si no tiene ventas, permitimos modificar el local y la fecha de inicio libremente
            $rules['local_id'] = 'required|exists:local,id';
            $rules['fecha_inicio'] = 'required|date';
        }

        $validatedData = $request->validate($rules);

        $promocion->update($validatedData);

        return redirect()->route('promociones.index')
            ->with('success', 'Regla de promoción actualizada correctamente.');
    }

    /**
     * Elimina una regla de promoción de forma segura.
     */
    public function destroy($id)
    {
        $this->authorize('gestionar-promociones');

        $promocion = PromocionRegla::findOrFail($id);

        // REGLA DE NEGOCIO CRÍTICA: No permitir borrar si ya se usó en ventas
        if ($promocion->detalleVentas()->exists()) {
            return redirect()->route('promociones.index')
                ->with('error', 'No se puede eliminar esta regla porque ya ha sido aplicada en ventas registradas. Te sugerimos desactivarla.');
        }

        $promocion->delete();

        return redirect()->route('promociones.index')
            ->with('success', 'Regla de promoción eliminada correctamente.');
    }

    /**
     * Cambia de estado rápidamente (Activo/Inactivo) mediante AJAX.
     */
    public function toggleActivo($id)
    {
        $this->authorize('gestionar-promociones');

        $promocion = PromocionRegla::findOrFail($id);
        $nuevoEstado = !$promocion->activo;

        // Si se pretende activar, debemos verificar que no exista solapamiento de fechas u jerarquía en el mismo local
        if ($nuevoEstado == 1) {
            $querySolapamiento = PromocionRegla::where('local_id', $promocion->local_id) // <-- FILTRO CRUCIAL POR LOCAL
                ->where('activo', 1)
                ->where('id', '!=', $promocion->id) // Excluir el registro actual
                ->where(function ($q) use ($promocion) {
                    $q->where('fecha_inicio', '<=', $promocion->fecha_fin)
                      ->where('fecha_fin', '>=', $promocion->fecha_inicio);
                });

            $hayConflicto = false;

            if ($promocion->alcance === 'insumo') {
                $insumoObj = Insumos::find($promocion->referencia_id);
                $categoriaId = $insumoObj->categoria_id ?? null;

                $conflictoDirecto = (clone $querySolapamiento)
                    ->where('alcance', 'insumo')
                    ->where('referencia_id', $promocion->referencia_id)
                    ->exists();

                $conflictoPorCategoria = $categoriaId ? (clone $querySolapamiento)
                    ->where('alcance', 'categoria')
                    ->where('referencia_id', $categoriaId)
                    ->exists() : false;

                if ($conflictoDirecto || $conflictoPorCategoria) {
                    $hayConflicto = true;
                }
            } else {
                $conflictoCategoria = (clone $querySolapamiento)
                    ->where('alcance', 'categoria')
                    ->where('referencia_id', $promocion->referencia_id)
                    ->exists();

                $insumosIdsEnCategoria = Insumos::where('categoria_id', $promocion->referencia_id)->pluck('id');
                $conflictoInsumosHijos = $insumosIdsEnCategoria->isNotEmpty() ? (clone $querySolapamiento)
                    ->where('alcance', 'insumo')
                    ->whereIn('referencia_id', $insumosIdsEnCategoria)
                    ->exists() : false;

                if ($conflictoCategoria || $conflictoInsumosHijos) {
                    $hayConflicto = true;
                }
            }

            if ($hayConflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede activar: existe un conflicto de fechas con otra promoción activa para este elemento en este local.'
                ], 422);
            }
        }

        $promocion->activo = $nuevoEstado;
        $promocion->save();

        return response()->json([
            'success' => true,
            'activo' => $promocion->activo,
            'message' => 'Estado de la promoción actualizado.'
        ]);
    }


    /**
     * Muestra los detalles de una regla de promoción para el modal Show (vía AJAX).
     */
    public function show($id)
    {
        $this->authorize('gestionar-promociones');

        $promocion = PromocionRegla::with(['referencia', 'local'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'local_id' => $promocion->local_id,
            'local_nombre' => $promocion->local->nombre ?? 'N/A',
            'nombre' => $promocion->nombre,
            'alcance' => ucfirst($promocion->alcance),
            'referencia' => $promocion->alcance === 'categoria' 
                ? ($promocion->referencia->categoria ?? 'N/A') 
                : ($promocion->referencia->producto ?? 'N/A'),
            'porcentaje_descuento' => number_format($promocion->porcentaje_descuento, 2),
            'fecha_inicio' => $promocion->fecha_inicio,
            'fecha_fin' => $promocion->fecha_fin,
            'activo' => $promocion->activo ? 'Activo' : 'Inactivo'
        ]);
    }

    /**
     * Retorna los datos de la promoción en JSON para cargarlos en el modal de Edición.
     */
    public function edit($id)
    {
        $this->authorize('gestionar-promociones');

        $promocion = PromocionRegla::findOrFail($id);

        return response()->json([
            'success' => true,
            'id' => $promocion->id,
            'local_id' => $promocion->local_id, 
            'nombre' => $promocion->nombre,
            'alcance' => $promocion->alcance,
            'referencia_id' => $promocion->referencia_id,
            'porcentaje_descuento' => $promocion->porcentaje_descuento,
            'fecha_inicio' => $promocion->fecha_inicio,
            'fecha_fin' => $promocion->fecha_fin,
            'activo' => $promocion->activo
        ]);
    }
    /**
     * Método privado para centralizar y reutilizar las reglas de validación (DRY).
     */
    private function validatePromocion(Request $request)
    {
        return $request->validate([
            'local_id' => [
                'required',
                'integer',
                Rule::exists('local', 'id')->where('tipo', 'LOCAL'),
            ],
            'nombre' => 'required|string|max:191',
            'alcance' => ['required', Rule::in(['categoria', 'insumo'])],
            'referencia_id' => 'required|array',
            'referencia_id.*' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->alcance === 'categoria') {
                        if (!Categoria::where('id', $value)->exists()) {
                            $fail('La categoría seleccionada no existe en el sistema.');
                        }
                    } elseif ($request->alcance === 'insumo') {
                        if (!Insumos::where('id', $value)->exists()) {
                            $fail('El insumo / producto seleccionado no existe en el sistema.');
                        }
                    }
                },
            ],
            'porcentaje_descuento' => 'required|numeric|min:0|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'activo' => 'boolean',
        ]);
    }
}
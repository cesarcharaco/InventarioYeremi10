<?php

namespace App\Http\Controllers;

use App\Models\Incidencias;
use App\Models\Insumos;
use App\Models\InsumosC; // Este representa a insumos_has_cantidades
use App\Models\HistorialIncidencias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidenciasController extends Controller
{
    public function index()
    {
        // Usamos Join para traer los datos del producto y del local en una sola consulta
        $incidencias = DB::table('incidencias')
            ->join('insumos', 'incidencias.id_insumo', '=', 'insumos.id')
            ->join('local', 'incidencias.id_local', '=', 'local.id')
            ->select(
                'incidencias.*', 
                'insumos.producto', 
                'insumos.descripcion', // <--- Asegúrate de incluir esto
                'insumos.serial',
                'local.nombre as nombre_local'
            )
            ->orderBy('incidencias.fecha_incidencia', 'desc')
            ->get();

        
        return view('inventario.incidencias.index', compact('incidencias'));
    }

    public function create()
    {
        // Traemos los insumos con su ubicación y stock actual
        $insumos = DB::table('insumos_has_cantidades')
            ->join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->join('local', 'insumos_has_cantidades.id_local', '=', 'local.id')
            ->select(
                'insumos.id as id_real_insumo', 
                'insumos.producto', 
                'insumos.serial',
                'local.nombre as local_nombre',
                'insumos_has_cantidades.cantidad',
                'insumos_has_cantidades.id as id_insumoc' // El ID de la relación
            )
            ->get();
        
        $hoy = date('Y-m-d');
        return view('inventario.incidencias.create', compact('insumos', 'hoy'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_insumoc' => 'required|exists:insumos_has_cantidades,id',
            'cantidad' => 'required|numeric|min:1',
            'tipo' => 'required',
            'fecha_incidencia' => 'required|date'
        ]);

        return DB::transaction(function () use ($request) {
            $stockRecord = InsumosC::with('insumo')->findOrFail($request->id_insumoc);
            //VALIDACIÓN CRÍTICA:
            if ($request->cantidad > $stockRecord->cantidad) {
                return redirect()->back()->with('warning', 'Stock insuficiente. Solo tienes ' . $stockRecord->cantidad . ' unidades.');
            }
            $codigo = $this->generarCodigoUnico();

            // 1. Descontar Stock
            $stockRecord->decrement('cantidad', $request->cantidad);

            // 2. Crear Incidencia
            $incidencia = Incidencias::create([
                'codigo' => $codigo,
                'id_insumo' => $stockRecord->id_insumo,
                'id_local' => $stockRecord->id_local,
                'cantidad' => $request->cantidad,
                'tipo' => $request->tipo,
                'observacion' => $request->observacion,
                'fecha_incidencia' => $request->fecha_incidencia,
            ]);

            // 3. Auditoría (Snapshot ACTUALIZADO)
            HistorialIncidencias::create([
                'codigo' => $codigo,
                'accion' => 'creacion',
                'user_id' => auth()->id(),
                'observacion_snapshot' => $request->observacion,
                'datos_snapshot' => [
                    'insumo_id' => $stockRecord->id_insumo, // ID del producto (Insumos)
                    'id_insumoc' => $stockRecord->id,      // ID del registro de stock (InsumosC)
                    'insumo' => $stockRecord->insumo->producto,
                    'cantidad' => $request->cantidad,
                    'tipo' => $request->tipo,
                    'local' => $stockRecord->local->nombre ?? 'N/A'
                ]
            ]);

            return redirect()->route('incidencias.index')->with('success', 'Reportada con éxito.');
        });
    }
    public function edit($id)
    {
        $incidencia = Incidencias::findOrFail($id);
        
        // Obtenemos los insumos con su ubicación para el select
        $insumos = InsumosC::join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->join('local', 'insumos_has_cantidades.id_local', '=', 'local.id')
            ->select(
                'insumos_has_cantidades.id as id_insumoc',
                'insumos.producto',
                'insumos.serial',
                'insumos.descripcion',
                'insumos_has_cantidades.cantidad',
                'local.nombre as local_nombre'
            )
            ->get();

        return view('inventario.incidencias.edit', compact('incidencia', 'insumos'));
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:1',
            'tipo' => 'required',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $incidencia = Incidencias::findOrFail($id);
            
            // 1. Ubicamos el registro de stock (InsumosC) relacionado a esta incidencia
            $stockRecord = InsumosC::where('id_insumo', $incidencia->id_insumo)
                                    ->where('id_local', $incidencia->id_local)
                                    ->firstOrFail();

            // 2. REVERSIÓN TEMPORAL (Solo en memoria para validar)
            // Devolvemos la cantidad vieja al stock para saber cuánto tendríamos realmente
            $stockSimulado = $stockRecord->cantidad + $incidencia->cantidad;

            // 3. VALIDACIÓN CRÍTICA
            // ¿La nueva cantidad solicitada supera lo que tenemos + lo que devolvimos?
            if ($request->cantidad > $stockSimulado) {
                return redirect()->back()->with('warning', "Stock insuficiente. Al editar, el máximo disponible para este insumo es: $stockSimulado");
            }

            // 4. APLICAR CAMBIOS FÍSICOS EN BASE DE DATOS
            // Primero devolvemos el stock viejo
            $stockRecord->increment('cantidad', $incidencia->cantidad);
            // Luego descontamos el nuevo stock
            $stockRecord->decrement('cantidad', $request->cantidad);

            // 5. ACTUALIZAR INCIDENCIA
            $incidencia->update([
                'cantidad' => $request->cantidad,
                'tipo' => $request->tipo,
                'observacion' => $request->observacion,
                // Agregamos un campo de fecha si es necesario
            ]);

            // 6. NUEVO SNAPSHOT DE EDICIÓN (Auditoría)
            HistorialIncidencias::create([
                'codigo' => $incidencia->codigo,
                'accion' => 'edicion',
                'user_id' => auth()->id(),
                'observacion_snapshot' => $request->motivo_edicion ?? 'Edición de valores: ' . $request->observacion,
                'datos_snapshot' => [
                    'insumo_id' => $incidencia->id_insumo,
                    'id_insumoc' => $stockRecord->id,
                    'insumo' => $stockRecord->insumo->producto,
                    'cantidad' => $incidencia->cantidad, // La nueva cantidad ya actualizada
                    'tipo' => $incidencia->tipo,
                    'local' => $stockRecord->local->nombre ?? 'N/A'
                ]
            ]);

            return redirect()->route('incidencias.index')->with('success', 'Registro actualizado y stock recalculado.');
        });
    }

    public function destroy(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $incidencia = Incidencias::with('insumo')->findOrFail($request->id_incidencia);

            // 1. Devolver Stock
            $stock = InsumosC::where('id_insumo', $incidencia->id_insumo)
                             ->where('id_local', $incidencia->id_local)
                             ->first();
            if ($stock) {
                $stock->increment('cantidad', $incidencia->cantidad);
            }

            // 2. Snapshot de ANULACIÓN (Antes de borrar la incidencia)
            HistorialIncidencias::create([
                'codigo' => $incidencia->codigo,
                'accion' => 'anulacion',
                'user_id' => auth()->id(),
                'observacion_snapshot' => "Anulación y devolución de stock.",
                'datos_snapshot' => [
                    'insumo' => $incidencia->insumo->producto,
                    'cantidad' => $incidencia->cantidad,
                    'motivo' => $request->motivo ?? 'Eliminado desde el panel'
                ]
            ]);

            // 3. Borrar SOLO la incidencia (El historial se queda para siempre)
            $incidencia->delete();

            return redirect()->back()->with('success', 'Registro anulado correctamente.');
        });
    }

    // --- MÉTODOS DE APOYO ---

    private function generarCodigoUnico()
    {
        do {
            $codigo = $this->generarCodigo();
            $existe = Incidencias::where('codigo', $codigo)->exists();
        } while ($existe);
        return $codigo;
    }

    protected function generarCodigo(){
    
        return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
    }
    
    public function detalles_historial($id)
    {
        try {
            // Buscamos la incidencia con sus relaciones
            $detalles = DB::table('incidencias')
                ->join('insumos', 'incidencias.id_insumo', '=', 'insumos.id')
                ->join('local', 'incidencias.id_local', '=', 'local.id')
                ->leftJoin('historial_incidencias', 'incidencias.id', '=', 'historial_incidencias.id_incidencia')
                ->where('incidencias.id', $id)
                ->select(
                    'insumos.producto',
                    'insumos.descripcion',
                    'insumos.serial',
                    'incidencias.tipo',
                    'incidencias.cantidad',
                    'incidencias.fecha_incidencia',
                    'incidencias.observacion',
                    'local.nombre as nombre_local'
                )
                ->get();

            return response()->json($detalles);
        } catch (\Exception $e) {
            // Si hay un error, devolvemos el mensaje para debuguear (solo en desarrollo)
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

public function deshacer_incidencia(Request $request)
{
    $registro = HistorialIncidencias::where('codigo', $request->codigo)
                ->where('accion', '!=', 'anulacion')
                ->first();

    if (!$registro) {
        return back()->with('error', 'Registro no encontrado o ya anulado.');
    }

    $datos = $registro->datos_snapshot;

    try {
        \DB::beginTransaction();

        // 1. Identificar el ID de la relación de stock (id_insumoc)
        // Buscamos en el snapshot o rescatamos de la tabla Incidencias
        $idInsumoC = $datos['id_insumoc'] ?? null;

        if (!$idInsumoC) {
            $incidenciaOriginal = Incidencias::where('codigo', $request->codigo)->first();
            if ($incidenciaOriginal) {
                // Buscamos el registro en insumos_has_cantidades que coincida con el local e insumo
                $relacion = \App\Models\InsumosC::where('id_insumo', $incidenciaOriginal->id_insumo)
                            ->where('id_local', $incidenciaOriginal->id_local)
                            ->first();
                $idInsumoC = $relacion->id ?? null;
            }
        }

        if (!$idInsumoC) {
            throw new \Exception("No se pudo ubicar el registro de stock para este local/insumo.");
        }

        // 2. BUSCAR EL MODELO DE CANTIDADES (Donde sí existe la columna 'cantidad')
        $stockRecord = \App\Models\InsumosC::find($idInsumoC);
        
        if ($stockRecord) {
            $cantidadMovida = $datos['cantidad'] ?? 0;
            $tipo = strtolower($datos['tipo'] ?? '');

            // REVERSIÓN LÓGICA:
            // Si fue una Salida (se restó), ahora SUMAMOS para devolver.
            // Si fue una Entrada (se sumó), ahora RESTAMOS para quitar.
            if (in_array($tipo, ['salida', 'egreso', 'retiro', 'desincorporacion'])) {
                $stockRecord->cantidad += $cantidadMovida;
            } else {
                // Solo restamos si hay stock suficiente para no quedar en negativo (opcional)
                $stockRecord->cantidad -= $cantidadMovida;
            }
            
            $stockRecord->save();
        }

        // 3. REGISTRAR ANULACIÓN
        HistorialIncidencias::create([
            'codigo' => $registro->codigo,
            'accion' => 'anulacion',
            'datos_snapshot' => $datos,
            'observacion_snapshot' => 'Stock revertido en tabla cantidades por: ' . auth()->user()->name,
            'user_id' => auth()->id()
        ]);

        \DB::commit();
        return back()->with('success', '¡Éxito! Stock devuelto a la tabla de cantidades.');

    } catch (\Exception $e) {
        \DB::rollback();
        return back()->with('error', 'Error al procesar: ' . $e->getMessage());
    }
}

    public function historial()
    {
        // Consulta simplificada: El historial manda.
        $historial = HistorialIncidencias::with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('inventario.incidencias.historial', compact('historial'));
    }

}
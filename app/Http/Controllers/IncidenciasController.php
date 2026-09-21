<?php

namespace App\Http\Controllers;

use App\Models\Incidencias;
use App\Models\Insumos;
use App\Models\Local;
use App\Models\User;
use App\Models\InsumosC; // Representa a insumos_has_cantidades
use App\Models\HistorialIncidencias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IncidenciasController extends Controller
{
    /**
     * PARÁMETRO DE NEGOCIO — Tipos que RESTAN stock (desincorporación/egreso).
     * Ajusta según tu operación. 'Otro' resta por defecto (exige observación en la vista).
     */
    private const TIPOS_RESTA = [
        'Dañado de Fábrica',
        'Dañado en Local',
        'Perdido',
        'Vencido',
        'Salida',
        'Egreso',
        'Retiro',
        'Desincorporacion',
        'Otro',
    ];

    /**
     * PARÁMETRO DE NEGOCIO — Tipos que SUMAN stock (reincorporación/ingreso).
     * Verifica 'Dañado y Devuelto': si significa "devueltO al proveedor" debería
     * estar en TIPOS_RESTA, no aquí.
     */
    private const TIPOS_SUMA = [
        'Dañado y Devuelto',
        'Ingreso',
        'Reincorporacion',
        'Devolucion',
        'Entrada',
    ];

    private function esResta(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_RESTA, true);
    }

    /**
     * Autorización por local: admin/almacenista gestionan todo; el encargado
     * solo sus locales activos en la pivote users_has_local.
     */
    private function puedeGestionarLocal(User $user, int $idLocal): bool
    {
        if ($user->esAdmin() || $user->hasRole(User::ROLE_ALMACENISTA)) {
            return true;
        }

        return DB::table('users_has_local')
            ->where('id_user', $user->id)
            ->where('id_local', $idLocal)
            ->where('status', 'activo')
            ->exists();
    }

    private function reglasValidacionTipo(): array
    {
        return ['required', Rule::in(array_merge(self::TIPOS_RESTA, self::TIPOS_SUMA))];
    }

    public function index()
    {
        Gate::authorize('ver-logistica');

        $incidencias = DB::table('incidencias')
            ->join('insumos', 'incidencias.id_insumo', '=', 'insumos.id')
            ->join('local', 'incidencias.id_local', '=', 'local.id')
            ->select(
                'incidencias.*',
                'insumos.producto',
                'insumos.descripcion',
                'insumos.serial',
                'local.nombre as nombre_local'
            )
            ->orderBy('incidencias.fecha_incidencia', 'desc')
            ->get();

        return view('inventario.incidencias.index', compact('incidencias'));
    }

    public function create()
    {
        Gate::authorize('registrar-incidencia');

        $user = auth()->user();

        if ($user->esAdmin() || $user->hasRole(User::ROLE_ALMACENISTA)) {
            $locales = Local::all();
        } else {
            $locales = $user->local()->wherePivot('status', 'activo')->get();
        }

        $insumos = DB::table('insumos_has_cantidades')
            ->join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->join('local', 'insumos_has_cantidades.id_local', '=', 'local.id')
            ->select(
                'insumos.id as id_real_insumo',
                'insumos.producto',
                'insumos.serial',
                'insumos.descripcion',
                'local.nombre as local_nombre',
                'insumos_has_cantidades.cantidad',
                'insumos_has_cantidades.id as id_insumoc',
                'insumos_has_cantidades.id_local'
            )
            ->get();

        $hoy = date('Y-m-d');
        return view('inventario.incidencias.create', compact('locales', 'insumos', 'hoy'));
    }

    public function store(Request $request)
    {
        Gate::authorize('registrar-incidencia');

        // Lista blanca de tipos: sin esto, cualquier string desconocido
        // sería tratado como SUMA y permitiría fabricar stock libremente.
        $request->validate([
            'id_insumoc'       => 'required|exists:insumos_has_cantidades,id',
            'cantidad'         => 'required|integer|min:1',
            'tipo'             => $this->reglasValidacionTipo(),
            'fecha_incidencia' => 'required|date',
            'observacion'      => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($request) {
            $stockRecord = InsumosC::with('insumo')->lockForUpdate()->findOrFail($request->id_insumoc);

            // Autorización sobre el local real del registro de stock (no sobre lo que diga el formulario)
            if (!$this->puedeGestionarLocal(auth()->user(), (int) $stockRecord->id_local)) {
                abort(403, 'No tienes autorización para gestionar el inventario de este local.');
            }

            $esResta = $this->esResta($request->tipo);

            // Solo las operaciones que restan requieren stock disponible
            if ($esResta && $request->cantidad > $stockRecord->cantidad) {
                return redirect()->back()
                    ->with('warning', 'Stock insuficiente. Solo tienes ' . $stockRecord->cantidad . ' unidades disponibles.')
                    ->withInput();
            }

            $codigo = $this->generarCodigoUnico();

            // 1. Modificar stock según el tipo
            if ($esResta) {
                $stockRecord->decrement('cantidad', $request->cantidad);
            } else {
                $stockRecord->increment('cantidad', $request->cantidad);
            }

            // 2. Crear Incidencia
            $incidencia = Incidencias::create([
                'codigo'           => $codigo,
                'id_insumo'        => $stockRecord->id_insumo,
                'id_local'         => $stockRecord->id_local,
                'cantidad'         => $request->cantidad,
                'tipo'             => $request->tipo,
                'observacion'      => $request->observacion,
                'fecha_incidencia' => $request->fecha_incidencia,
            ]);

            // 3. Auditoría
            HistorialIncidencias::create([
                'codigo'              => $codigo,
                'accion'              => 'creacion',
                'user_id'             => auth()->id(),
                'observacion_snapshot' => $request->observacion,
                'datos_snapshot'      => [
                    'insumo_id'  => $stockRecord->id_insumo,
                    'id_insumoc' => $stockRecord->id,
                    'insumo'     => $stockRecord->insumo->producto ?? 'N/A',
                    'cantidad'   => $request->cantidad,
                    'tipo'       => $request->tipo,
                    'operacion'  => $esResta ? 'decremento' : 'incremento',
                    'local'      => $stockRecord->local->nombre ?? 'N/A',
                ],
            ]);

            return redirect()->route('incidencias.index')->with('success', 'Incidencia reportada y stock actualizado.');
        });
    }

    public function edit($id)
    {
        Gate::authorize('registrar-incidencia');

        $incidencia = Incidencias::findOrFail($id);
        $user = auth()->user();

        if ($user->esAdmin() || $user->hasRole(User::ROLE_ALMACENISTA)) {
            $locales = Local::all();
        } else {
            $locales = $user->local()->wherePivot('status', 'activo')->get();
        }

        $insumos = DB::table('insumos_has_cantidades')
            ->join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->join('local', 'insumos_has_cantidades.id_local', '=', 'local.id')
            ->select(
                'insumos.id as id_real_insumo',
                'insumos.producto',
                'insumos.serial',
                'insumos.descripcion',
                'local.nombre as local_nombre',
                'local.id as id_local',
                'insumos_has_cantidades.cantidad',
                'insumos_has_cantidades.id as id_insumoc'
            )
            ->get();

        return view('inventario.incidencias.edit', compact('incidencia', 'locales', 'insumos'));
    }

    public function update(Request $request, $id)
    {
        // Unificado con el permiso que valida la vista edit (antes: gestionar-insumos)
        Gate::authorize('registrar-incidencia');

        $request->validate([
            'id_insumoc'       => 'nullable|exists:insumos_has_cantidades,id',
            'cantidad'         => 'required|integer|min:1',
            'tipo'             => $this->reglasValidacionTipo(),
            'fecha_incidencia' => 'nullable|date',
            'observacion'      => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $incidencia = Incidencias::lockForUpdate()->findOrFail($id);
            $user = auth()->user();

            // Registro de stock ORIGINAL (siempre debe existir para revertir)
            $stockViejo = InsumosC::where('id_insumo', $incidencia->id_insumo)
                ->where('id_local', $incidencia->id_local)
                ->lockForUpdate()
                ->firstOrFail();

            // Si el form envió OTRO id_insumoc, la incidencia se TRASLADA de ubicación
            $huboCambio = $request->filled('id_insumoc') && (int) $request->id_insumoc !== (int) $stockViejo->id;
            $stockNuevo = $huboCambio
                ? InsumosC::lockForUpdate()->findOrFail($request->id_insumoc)
                : $stockViejo;

            // Autorización sobre AMBAS ubicaciones involucradas
            foreach (collect([$stockViejo, $stockNuevo])->unique('id') as $registro) {
                if (!$this->puedeGestionarLocal($user, (int) $registro->id_local)) {
                    abort(403, 'No tienes autorización para gestionar el inventario de uno de los locales involucrados.');
                }
            }

            $esRestaAnterior = $this->esResta($incidencia->tipo);
            $esRestaNueva    = $this->esResta($request->tipo);

            // 1. Factibilidad de la REVERSIÓN:
            // si el tipo anterior SUMÓ stock, hay que poder quitarlo sin quedar en negativo.
            if (!$esRestaAnterior && $stockViejo->cantidad < $incidencia->cantidad) {
                return redirect()->back()
                    ->with('error', "No se puede editar: el stock actual del registro original ({$stockViejo->cantidad}) es menor que la cantidad registrada ({$incidencia->cantidad}). Anule la incidencia en su lugar.")
                    ->withInput();
            }

            // 2. Stock del NUEVO destino si la nueva operación resta
            if ($esRestaNueva && $request->cantidad > $stockNuevo->cantidad) {
                return redirect()->back()
                    ->with('warning', "Stock insuficiente. Máximo disponible en el destino: {$stockNuevo->cantidad}")
                    ->withInput();
            }

            // 3. Reversión física en el registro ORIGINAL
            if ($esRestaAnterior) {
                $stockViejo->increment('cantidad', $incidencia->cantidad);
            } else {
                $stockViejo->decrement('cantidad', $incidencia->cantidad);
            }

            // 4. Aplicación en el registro DESTINO (puede ser el mismo)
            if ($esRestaNueva) {
                $stockNuevo->decrement('cantidad', $request->cantidad);
            } else {
                $stockNuevo->increment('cantidad', $request->cantidad);
            }

            // 5. Actualizar la incidencia (incluida la ubicación si cambió)
            $incidencia->update([
                'id_insumo'        => $stockNuevo->id_insumo,
                'id_local'         => $stockNuevo->id_local,
                'cantidad'         => $request->cantidad,
                'tipo'             => $request->tipo,
                'observacion'      => $request->observacion,
                'fecha_incidencia' => $request->fecha_incidencia ?? $incidencia->fecha_incidencia,
            ]);

            // 6. Auditoría de la edición
            HistorialIncidencias::create([
                'codigo'              => $incidencia->codigo,
                'accion'              => 'edicion',
                'user_id'             => auth()->id(),
                'observacion_snapshot' => $request->motivo_edicion ?? 'Edición de valores: ' . $request->observacion,
                'datos_snapshot'      => [
                    'insumo_id'  => $stockNuevo->id_insumo,
                    'id_insumoc' => $stockNuevo->id,
                    'insumo'     => $stockNuevo->insumo->producto ?? 'N/A',
                    'cantidad'   => $incidencia->cantidad,
                    'tipo'       => $incidencia->tipo,
                    'operacion'  => $esRestaNueva ? 'decremento' : 'incremento',
                    'local'      => $stockNuevo->local->nombre ?? 'N/A',
                    'traslado'   => $huboCambio,
                ],
            ]);

            return redirect()->route('incidencias.index')->with('success', 'Registro actualizado y stock recalculado.');
        });
    }

    public function destroy(Request $request)
    {
        Gate::authorize('anular-historial');

        return DB::transaction(function () use ($request) {
            $incidencia = Incidencias::with('insumo')->lockForUpdate()->findOrFail($request->id_incidencia);

            $stock = InsumosC::where('id_insumo', $incidencia->id_insumo)
                ->where('id_local', $incidencia->id_local)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $esResta = $this->esResta($incidencia->tipo);
                if ($esResta) {
                    $stock->increment('cantidad', $incidencia->cantidad);
                } else {
                    if ($incidencia->cantidad > $stock->cantidad) {
                        return redirect()->back()
                            ->with('error', "No se puede anular: el stock actual ({$stock->cantidad}) es menor que la cantidad a descontar ({$incidencia->cantidad}).");
                    }
                    $stock->decrement('cantidad', $incidencia->cantidad);
                }
            }

            HistorialIncidencias::create([
                'codigo'              => $incidencia->codigo,
                'accion'              => 'anulacion',
                'user_id'             => auth()->id(),
                'observacion_snapshot' => 'Anulación y reversión de stock.',
                'datos_snapshot'      => [
                    'insumo'   => $incidencia->insumo->producto ?? 'N/A',
                    'cantidad' => $incidencia->cantidad,
                    'motivo'   => $request->motivo ?? 'Eliminado desde el panel',
                ],
            ]);

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

    protected function generarCodigo()
    {
        return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
    }

    public function detalles_historial($id)
    {
        Gate::authorize('ver-logistica');

        // Nota: el leftJoin original con historial_incidencias.id_incidencia estaba
        // muerto (los snapshots guardan 'codigo', nunca 'id_incidencia').
        $detalles = DB::table('incidencias')
            ->join('insumos', 'incidencias.id_insumo', '=', 'insumos.id')
            ->join('local', 'incidencias.id_local', '=', 'local.id')
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
    }

    public function deshacer_incidencia(Request $request)
    {
        Gate::authorize('anular-historial');

        // IDEMPOTENCIA REAL: si ya existe una anulación para este código,
        // no se permite revertir dos veces (antes el guard nunca la detectaba).
        $yaAnulado = HistorialIncidencias::where('codigo', $request->codigo)
            ->where('accion', 'anulacion')
            ->exists();

        if ($yaAnulado) {
            return back()->with('error', 'Este registro ya fue anulado previamente.');
        }

        $registro = HistorialIncidencias::where('codigo', $request->codigo)
            ->where('accion', '!=', 'anulacion')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$registro) {
            return back()->with('error', 'Registro no encontrado.');
        }

        $datos = $registro->datos_snapshot;

        try {
            DB::beginTransaction();

            $idInsumoC = $datos['id_insumoc'] ?? null;

            if (!$idInsumoC) {
                $incidenciaOriginal = Incidencias::where('codigo', $request->codigo)->first();
                if ($incidenciaOriginal) {
                    $relacion = InsumosC::where('id_insumo', $incidenciaOriginal->id_insumo)
                        ->where('id_local', $incidenciaOriginal->id_local)
                        ->first();
                    $idInsumoC = $relacion->id ?? null;
                }
            }

            if (!$idInsumoC) {
                throw new \Exception("No se pudo ubicar el registro de stock para este local/insumo.");
            }

            $stockRecord = InsumosC::lockForUpdate()->find($idInsumoC);

            if ($stockRecord) {
                $cantidadMovida = $datos['cantidad'] ?? 0;

                // Preferir la operación almacenada en el snapshot; fallback por tipo
                $operacion = $datos['operacion']
                    ?? ($this->esResta($datos['tipo'] ?? '') ? 'decremento' : 'incremento');

                if ($operacion === 'decremento') {
                    // La incidencia había restado stock → devolverlo
                    $stockRecord->increment('cantidad', $cantidadMovida);
                } else {
                    // La incidencia había sumado stock → quitarlo (con guard anti-negativo)
                    if ($cantidadMovida > $stockRecord->cantidad) {
                        throw new \Exception("Stock insuficiente para deshacer esta entrada. Stock actual: {$stockRecord->cantidad}");
                    }
                    $stockRecord->decrement('cantidad', $cantidadMovida);
                }
            }

            HistorialIncidencias::create([
                'codigo'              => $registro->codigo,
                'accion'              => 'anulacion',
                'datos_snapshot'      => $datos,
                'observacion_snapshot' => 'Stock revertido en tabla cantidades por: ' . auth()->user()->name,
                'user_id'             => auth()->id(),
            ]);

            DB::commit();
            return back()->with('success', '¡Éxito! Stock revertido en la tabla de cantidades.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en deshacer_incidencia: ' . $e->getMessage(), ['codigo' => $request->codigo]);
            return back()->with('error', 'Ocurrió un error interno al procesar la reversión. Intente nuevamente.');
        }
    }

    public function historial()
    {
        Gate::authorize('ver-historial-total');

        $historial = HistorialIncidencias::with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inventario.incidencias.historial', compact('historial'));
    }
}
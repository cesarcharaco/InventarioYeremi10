<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Despachos;
use App\Models\DespachoDetalles;
use App\Models\Local;
use App\Models\Insumos;
use App\Models\InsumosC;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DespachoNotification;
use Yajra\DataTables\Facades\DataTables;

class DespachoController extends Controller
{
    /**
     * PARCHE #5 — Catálogo único de estados.
     * Idealmente estas constantes deberían vivir en el modelo Despachos
     * y consumirse desde ahí en badges, filtros y validaciones.
     */
    private const ESTADO_PENDIENTE = 'Pendiente';
    private const ESTADO_TRANSITO  = 'En Tránsito';
    private const ESTADO_RECIBIDO  = 'Recibido';
    private const ESTADO_OBS       = 'recibido_con_incidencias';
    private const ESTADO_RECHAZADO = 'Rechazado';
    private const ESTADO_CANCELADO = 'Cancelado';

    /**
     * PARCHE #4 — Generación de código server-side, dentro de la transacción.
     * Recomendación adicional: agregar unique constraint a `despachos.codigo` en BD.
     */
    private function generarCodigo(string $prefijo): string
    {
        $ultimoId = (int) Despachos::max('id') + 1;
        return $prefijo . date('Ymd') . '-' . str_pad($ultimoId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Helper: locales asignados a un encargado (evita repetir el query de la pivote).
     */
    private function localesDelUsuario(User $user)
    {
        return DB::table('users_has_local')
            ->where('id_user', $user->id)
            ->pluck('id_local');
    }

    /**
     * Muestra el historial de despachos
     */
    public function index()
    {
        Gate::authorize('ver-logistica');

        $user = auth()->user();

        if ($user->role === User::ROLE_ENCARGADO) {
            $localesIds = $this->localesDelUsuario($user);
            $locales = Local::whereIn('id', $localesIds)->get();
        } else {
            $locales = Local::all();
        }

        return view('despachos.index', compact('locales'));
    }

    /**
     * Procesa los datos por AJAX con DataTables y aplica los filtros personalizados
     */
    public function getDespachosData(Request $request)
    {
        Gate::authorize('ver-logistica');

        $user = auth()->user();

        $query = Despachos::with(['origen', 'destino'])
            ->select('despachos.*')
            ->orderBy('created_at', 'desc');

        // Blindaje por roles: el encargado solo ve despachos de sus locales
        if ($user->role === User::ROLE_ENCARGADO) {
            $localesIds = $this->localesDelUsuario($user);

            $query->where(function ($q) use ($localesIds) {
                $q->whereIn('id_local_origen', $localesIds)
                  ->orWhereIn('id_local_destino', $localesIds);
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_despacho', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_despacho', '<=', $request->fecha_hasta);
        }

        if ($request->filled('id_local_origen')) {
            $query->where('id_local_origen', $request->id_local_origen);
        }

        if ($request->filled('id_local_destino')) {
            $query->where('id_local_destino', $request->id_local_destino);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return DataTables::of($query)
            ->addColumn('origen_nombre', function ($row) {
                return $row->origen->nombre ?? 'N/D';
            })
            ->addColumn('destino_nombre', function ($row) {
                return $row->destino->nombre ?? 'N/D';
            })
            ->editColumn('codigo', function ($row) {
                return '<strong class="text-primary">' . e($row->codigo) . '</strong>';
            })
            ->editColumn('fecha_despacho', function ($row) {
                return $row->fecha_despacho ? Carbon::parse($row->fecha_despacho)->format('d/m/Y h:i A') : 'N/D';
            })
            ->editColumn('transportado_por', function ($row) {
                $html = e($row->transportado_por);
                if ($row->vehiculo_placa) {
                    $html .= '<small class="text-muted d-block">Placa: ' . e($row->vehiculo_placa) . '</small>';
                }
                return $html;
            })
            ->editColumn('estado', function ($row) {
                // PARCHE #5: badges alineados con el catálogo único de estados
                switch ($row->estado) {
                    case self::ESTADO_TRANSITO:
                        return '<span class="badge badge-warning text-dark p-2"><i class="fa fa-truck"></i> En Tránsito</span>';
                    case self::ESTADO_RECIBIDO:
                        return '<span class="badge badge-success p-2"><i class="fa fa-check-circle"></i> Recibido</span>';
                    case self::ESTADO_OBS:
                        return '<span class="badge badge-info p-2"><i class="fa fa-exclamation-circle"></i> Con Incidencias</span>';
                    case self::ESTADO_RECHAZADO:
                    case self::ESTADO_CANCELADO:
                        return '<span class="badge badge-danger p-2"><i class="fa fa-times-circle"></i> ' . e($row->estado) . '</span>';
                    case self::ESTADO_PENDIENTE:
                        return '<span class="badge badge-secondary p-2"><i class="fa fa-clock"></i> Pendiente</span>';
                    default:
                        return '<span class="badge badge-secondary p-2">' . e($row->estado) . '</span>';
                }
            })
            ->addColumn('acciones', function ($row) {
                $html = '<div class="d-flex justify-content-center align-items-center" style="gap: 5px;">';

                $html .= '<button class="btn btn-info btn-sm text-white" onclick="verDetalle(' . $row->id . ', \'' . $row->codigo . '\')" title="Ver Detalle"><i class="fa fa-eye"></i></button>';

                if ($row->estado === self::ESTADO_PENDIENTE) {
                    if (auth()->user()->can('procesar-solicitud')) {
                        $html .= '<button class="btn btn-primary btn-sm" onclick="procesarEnvioPendienteModal(' . $row->id . ')" title="Procesar y Enviar Solicitud"><i class="fa fa-paper-plane"></i></button>';
                    }
                    if (auth()->user()->can('editar-solicitud')) {
                        $html .= '<a href="' . route('despacho.solicitud.edit', $row->id) . '" class="btn btn-warning btn-sm" title="Editar Solicitud Pendiente"><i class="fa fa-edit"></i></a>';
                        $html .= '<button class="btn btn-danger btn-sm" onclick="eliminarSolicitud(' . $row->id . ')" title="Eliminar Solicitud"><i class="fa fa-trash"></i></button>';
                    }
                }

                if ($row->estado === self::ESTADO_TRANSITO) {
                    if (auth()->user()->can('eliminar-despacho')) {
                        $html .= '<button class="btn btn-danger btn-sm" onclick="eliminarDespacho(' . $row->id . ')" title="Eliminar Despacho"><i class="fa fa-trash"></i></button>';
                    }
                    if (auth()->user()->can('recibir-despacho')) {
                        $html .= '<button class="btn btn-success btn-sm" onclick="confirmarRecepcion(' . $row->id . ')" title="Confirmar Recepción"><i class="fa fa-check-square"></i></button>';
                    }
                }

                $html .= '</div>';
                return $html;
            })
            ->filterColumn('origen_nombre', function ($q, $kw) {
                $q->whereHas('origen', function ($query) use ($kw) {
                    $query->where('nombre', 'LIKE', "%{$kw}%");
                });
            })
            ->filterColumn('destino_nombre', function ($q, $kw) {
                $q->whereHas('destino', function ($query) use ($kw) {
                    $query->where('nombre', 'LIKE', "%{$kw}%");
                });
            })
            ->rawColumns(['codigo', 'fecha_despacho', 'transportado_por', 'estado', 'acciones'])
            ->make(true);
    }

    /**
     * Muestra el formulario para crear un nuevo despacho
     */
    public function create()
    {
        Gate::authorize('crear-despacho');

        $usuario = auth()->user();

        if (Gate::allows('seleccionar-cualquier-origen')) {
            $localesOrigen = Local::all();
        } else {
            $localesOrigen = $usuario->local;
        }

        $localesDestino = Local::all();

        $insumos = Insumos::where('estado', 'En Venta')->get();

        // Valor sugerido para mostrar en el formulario; el definitivo se genera server-side en store()
        $codigo = $this->generarCodigo('DESP-');

        return view('despachos.create', compact('localesOrigen', 'localesDestino', 'insumos', 'codigo'));
    }

    /**
     * Procesa y guarda el despacho en la base de datos (Salida de Depósito)
     */
    public function store(Request $request)
    {
        Gate::authorize('crear-despacho');

        $user = auth()->user();

        // PARCHE #4: validación estricta de FKs y arrays (distinct evita líneas duplicadas)
        $request->validate([
            'id_local_origen'  => 'required|exists:local,id|different:id_local_destino',
            'id_local_destino' => 'required|exists:local,id',
            'transportado_por' => 'required|string|max:100',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => [
                'required',
                Rule::exists('insumos', 'id')->where('estado', 'En Venta')
            ],
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ],[
            // Opcional: Mensaje personalizado para que el usuario sepa por qué falló
            'id_insumo.*.exists' => 'Uno de los insumos seleccionados no existe o está suspendido.'
        ]);

        // Blindaje multi-tienda: el encargado solo despacha desde sus locales
        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocal = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $request->id_local_origen)
                ->exists();

            if (!$esSuLocal) {
                return redirect()->back()->with('error', 'No tienes autorización para despachar mercancía desde este local de origen.')->withInput();
            }
        }

        $tieneUsuariosDestino = DB::table('users_has_local')
            ->where('id_local', $request->id_local_destino)
            ->exists();

        if (!$tieneUsuariosDestino) {
            return redirect()->back()->with('error', 'No es posible generar el despacho porque no hay usuario asignado a dicho local o depósito.')->withInput();
        }

        try {
            DB::beginTransaction();

            // PARCHE #4: código generado server-side dentro de la transacción (nunca desde el request)
            $codigo = $this->generarCodigo('DESP-');

            $despacho = Despachos::create([
                'codigo'           => $codigo,
                'id_local_origen'  => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa'   => $request->vehiculo_placa,
                'observacion'      => $request->observacion,
                'estado'           => self::ESTADO_TRANSITO,
                'fecha_despacho'   => Carbon::now(),
            ]);

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadADespachar = $request->cantidad[$key];

                // PARCHE #3: bloqueo pesimista para eliminar la race condition de stock
                $registroOrigen = InsumosC::where('id_local', $request->id_local_origen)
                    ->where('id_insumo', $insumo_id)
                    ->lockForUpdate()
                    ->first();

                $item = Insumos::find($insumo_id);
                $nombreItem = $item ? $item->producto : "ID: $insumo_id";

                if (!$registroOrigen || $registroOrigen->cantidad < $cantidadADespachar) {
                    throw new \Exception("Stock insuficiente para: $nombreItem en el depósito de origen.");
                }

                $registroOrigen->decrement('cantidad', $cantidadADespachar);

                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadADespachar,
                    'cantidad_recibida' => 0,
                ]);
            }

            DB::commit();

            // PARCHE #7: notificaciones FUERA de la transacción.
            // Si fallan, el despacho ya está persistido y el usuario solo pierde el aviso.
            try {
                $userIdsDestino = DB::table('users_has_local')
                    ->where('id_local', $despacho->id_local_destino)
                    ->pluck('id_user');

                if ($userIdsDestino->isNotEmpty()) {
                    Notification::send(
                        User::whereIn('id', $userIdsDestino)->get(),
                        new DespachoNotification($despacho, 'creado')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Fallo al enviar notificación de despacho creado: ' . $e->getMessage());
            }

            return redirect()->route('despacho.print', $despacho->id)
                ->with('success', 'Despacho emitido con éxito. Puede imprimir el comprobante a continuación.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@store: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'origen'  => $request->id_local_origen,
            ]);
            // PARCHE #8: mensaje controlado al cliente, detalle solo en el log
            $mensaje = str_contains($e->getMessage(), 'Stock insuficiente') || str_contains($e->getMessage(), 'SUSPENDIDO')
                ? $e->getMessage()
                : 'Ocurrió un error interno al generar el despacho. Intente nuevamente.';
            return redirect()->back()->with('error', $mensaje)->withInput();
        }
    }

    /**
     * Función privada para gestionar el stock en la ubicación de destino
     */
    private function gestionarStockDestino($id_local, $id_insumo, $cantidad)
    {
        $registroDestino = InsumosC::where('id_local', $id_local)
            ->where('id_insumo', $id_insumo)
            ->first();

        if ($registroDestino) {
            $registroDestino->increment('cantidad', $cantidad);
        } else {
            InsumosC::create([
                'id_local'  => $id_local,
                'id_insumo' => $id_insumo,
                'cantidad'  => $cantidad,
            ]);
        }
    }

    public function show($id)
    {
        Gate::authorize('ver-logistica');
        try {
            $user = auth()->user();

            $despacho = Despachos::with(['origen', 'destino', 'detalles.insumos'])->findOrFail($id);

            if ($user->role === User::ROLE_ENCARGADO) {
                $localesIds = $this->localesDelUsuario($user);

                $involucrado = $localesIds->contains($despacho->id_local_origen) ||
                               $localesIds->contains($despacho->id_local_destino);

                if (!$involucrado) {
                    abort(403, 'No tienes autorización para ver los detalles de este despacho.');
                }
            }

            $detalles = $despacho->detalles;

            return view('despachos.modal_detalle', compact('despacho', 'detalles'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e; // deja que Laravel renderice el 404 estándar
        } catch (\Exception $e) {
            // PARCHE #8: nunca exponer $e->getMessage() al cliente
            Log::error('Error en DespachoController@show: ' . $e->getMessage(), ['despacho_id' => $id]);
            return response('No se pudo cargar el detalle del despacho.', 500);
        }
    }

    /**
     * Confirma la recepción física de un despacho En Tránsito
     */
    public function confirmarRecepcion(Request $request, $id)
    {
        Gate::authorize('recibir-despacho');

        // PARCHE #5: catálogo de estados unificado (se eliminó la regla duplicada
        // de observacion_recepcion y el valor 'recibido_con_incidencias' inválido)
        $request->validate([
            'estado' => ['required', Rule::in([
                self::ESTADO_RECIBIDO,
                self::ESTADO_OBS,
                self::ESTADO_RECHAZADO,
                self::ESTADO_CANCELADO,
            ])],
            'observacion_recepcion' => 'nullable|string|max:1000',
            'cantidades_recibidas'  => 'required|array',
            'cantidades_recibidas.*'=> 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // PARCHEES #1 y #3: lock sobre la cabecera + guard estricto de estado
            $despacho = Despachos::with('detalles')->lockForUpdate()->findOrFail($id);
            $user = auth()->user();

            if ($despacho->estado !== self::ESTADO_TRANSITO) {
                return response()->json([
                    'error' => 'Este despacho ya fue procesado o aún no ha sido despachado. Recargue la página.'
                ], 422);
            }

            // Blindaje multi-tienda: solo el encargado del local destino puede recibir
            if ($user->role === User::ROLE_ENCARGADO) {
                $esSuLocalDestino = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->where('id_local', $despacho->id_local_destino)
                    ->exists();

                if (!$esSuLocalDestino) {
                    return response()->json(['error' => 'No tienes autorización para recibir despachos dirigidos a este local.'], 403);
                }
            }

            // Validación estricta: las claves deben corresponder EXACTAMENTE a los detalles
            $idsDetalles = $despacho->detalles->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
            $idsRecibidos = collect(array_keys($request->cantidades_recibidas))->map(fn ($v) => (int) $v)->sort()->values()->all();

            if ($idsDetalles !== $idsRecibidos) {
                return response()->json(['error' => 'Las cantidades recibidas no corresponden a los ítems del despacho.'], 422);
            }

            $despacho->estado = $request->estado;
            $despacho->observacion_recepcion = $request->observacion_recepcion;
            $despacho->fecha_recepcion = Carbon::now();
            $despacho->save();

            $esRechazoTotal = in_array($request->estado, [self::ESTADO_CANCELADO, self::ESTADO_RECHAZADO], true);

            foreach ($despacho->detalles as $detalle) {
                $cantidadRecibida = (int) $request->cantidades_recibidas[$detalle->id];

                if ($cantidadRecibida > $detalle->cantidad_enviada) {
                    throw new \Exception("La cantidad recibida no puede ser mayor a la cantidad enviada para el ítem.");
                }

                $detalle->cantidad_recibida = $cantidadRecibida;
                $detalle->save();

                if ($esRechazoTotal) {
                    // PARCHE #2: la mercancía regresa físicamente al origen.
                    // Sin esto, el stock desaparece del sistema (fuga de inventario).
                    $this->gestionarStockDestino(
                        $despacho->id_local_origen,
                        $detalle->id_insumo,
                        $detalle->cantidad_enviada
                    );
                } elseif ($cantidadRecibida > 0) {
                    $this->gestionarStockDestino(
                        $despacho->id_local_destino,
                        $detalle->id_insumo,
                        $cantidadRecibida
                    );
                    // Nota de negocio: si deseas tratar la diferencia
                    // (cantidad_enviada - cantidad_recibida) como merma en tránsito,
                    // descuéntala aquí de un depósito virtual o regístrela en una
                    // tabla de auditoría. Hoy simplemente queda sin contabilizar.
                }
            }

            DB::commit();

            // PARCHE #7: notificación de vuelta al origen, fuera de la transacción
            try {
                $userIdsOrigen = DB::table('users_has_local')
                    ->where('id_local', $despacho->id_local_origen)
                    ->pluck('id_user');

                if ($userIdsOrigen->isNotEmpty()) {
                    Notification::send(
                        User::whereIn('id', $userIdsOrigen)->get(),
                        new DespachoNotification($despacho, 'recibido')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Fallo al enviar notificación de recepción: ' . $e->getMessage());
            }

            return response()->json(['success' => 'La recepción del despacho se ha procesado e inventariado correctamente.']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@confirmarRecepcion: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => auth()->id(),
            ]);
            // PARCHE #8: mensaje genérico controlado
            return response()->json(['error' => 'Ocurrió un error interno al procesar la recepción. Intente nuevamente.'], 500);
        }
    }

    /**
     * PARCHE #6: JSON del despacho con blindaje multi-tienda (se elimina el IDOR)
     */
    public function getJson($id)
    {
        Gate::authorize('recibir-despacho');

        $user = auth()->user();

        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);

        if ($user->role === User::ROLE_ENCARGADO) {
            $localesIds = $this->localesDelUsuario($user);

            $involucrado = $localesIds->contains($despacho->id_local_origen) ||
                           $localesIds->contains($despacho->id_local_destino);

            if (!$involucrado) {
                return response()->json(['error' => 'No tienes autorización para consultar este despacho.'], 403);
            }
        }

        return response()->json($despacho);
    }

    public function edit($id)
    {
        Gate::authorize('editar-despacho');

        $user = auth()->user();

        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                return redirect()->route('despacho.index')
                    ->with('error', 'No tienes autorización para editar despachos que no se originan en tu local.');
            }
        }

        if ($despacho->estado !== self::ESTADO_TRANSITO) {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede editar un despacho que ya ha sido procesado (Recibido, con incidencias o rechazado).');
        }

        if (Gate::allows('seleccionar-cualquier-origen')) {
            $localesOrigen = Local::all();
        } else {
            $localesOrigen = $user->local;
        }

        $localesDestino = Local::all();

        $insumos = Insumos::where('estado', 'En Venta')->get();

        return view('despachos.edit', compact('despacho', 'localesOrigen', 'localesDestino', 'insumos'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('editar-despacho');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                return redirect()->route('despacho.index')->with('error', 'No tienes autorización para modificar este despacho.');
            }
        }

        if ($despacho->estado !== self::ESTADO_TRANSITO) {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede editar un despacho que ya ha sido procesado (Recibido, con incidencias o rechazado).');
        }

        $request->validate([
            'id_local_origen'  => 'required|exists:local,id|different:id_local_destino',
            'id_local_destino' => 'required|exists:local,id',
            'transportado_por' => 'required|string|max:100',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => [
                'required',
                Rule::exists('insumos', 'id')->where('estado', 'En Venta')
            ],
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ],[
            // Opcional: Mensaje personalizado para que el usuario sepa por qué falló
            'id_insumo.*.exists' => 'Uno de los insumos seleccionados no existe o está suspendido.'
        ]);

        try {
            DB::beginTransaction();

            // ============================================================
            // PARCHE: pre-validación completa ANTES de mutar el stock.
            // Se simula el stock disponible (stock actual + lo que este despacho
            // tiene en tránsito) y se valida todo el carrito de una vez.
            // Así, un solo ítem suspendido/sin stock NO destruye la edición
            // completa del usuario con un rollback tardío.
            // ============================================================
            $idsInsumos = array_unique($request->id_insumo);

            // PARCHE #3: bloquear todas las filas de origen involucradas (viejas + nuevas)
            $registrosBloqueados = InsumosC::where('id_local', $despacho->id_local_origen)
                ->whereIn('id_insumo', $idsInsumos)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_insumo');

            $stockSimulado = [];
            foreach ($idsInsumos as $insumoId) {
                $base = $registrosBloqueados->get($insumoId)->cantidad ?? 0;
                // Devolver al simulado lo que este despacho tenía en tránsito
                $base += $despacho->detalles->where('id_insumo', $insumoId)->sum('cantidad_enviada');
                $stockSimulado[$insumoId] = $base;
            }

            foreach ($request->id_insumo as $key => $insumo_id) {
                $item = Insumos::find($insumo_id);
                
                if ($stockSimulado[$insumo_id] < $request->cantidad[$key]) {
                    throw new \Exception("Stock insuficiente en origen para el insumo: {$item->producto}");
                }
                $stockSimulado[$insumo_id] -= $request->cantidad[$key];
            }

            // A partir de aquí todo está validado: ejecutar los cambios.

            // PASO 1: revertir el stock en origen (con filas ya bloqueadas)
            foreach ($despacho->detalles as $detalle) {
                $reg = $registrosBloqueados->get($detalle->id_insumo);
                if ($reg) {
                    $reg->increment('cantidad', $detalle->cantidad_enviada);
                }
            }

            // PASO 2: actualizar cabecera
            $despacho->update([
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa'   => $request->vehiculo_placa,
                'observacion'      => $request->observacion,
            ]);

            // PASO 3: reemplazar detalles
            $despacho->detalles()->delete();

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadNueva = $request->cantidad[$key];

                // Reutilizamos las filas ya bloqueadas para descontar
                $reg = $registrosBloqueados->get($insumo_id);
                $reg->decrement('cantidad', $cantidadNueva);

                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadNueva,
                    'cantidad_recibida' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Despacho actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@update: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => $user->id,
            ]);
            // Mensajes de negocio controlados; error genérico para lo demás (PARCHE #8)
            $mensaje = (str_contains($e->getMessage(), 'suspendido') || str_contains($e->getMessage(), 'Stock insuficiente'))
                ? $e->getMessage()
                : 'Ocurrió un error interno al actualizar el despacho. Intente nuevamente.';
            return redirect()->back()->with('error', $mensaje)->withInput();
        }
    }

    /**
     * Elimina un despacho En Tránsito y devuelve el stock al origen.
     * Ahora responde JSON ante peticiones AJAX (consistencia con destroySolicitud).
     */
    public function destroy(Request $request, $id)
    {
        Gate::authorize('eliminar-despacho');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                $msg = 'No tienes autorización para eliminar despachos que no se originan en tu local.';
                return $request->expectsJson()
                    ? response()->json(['message' => $msg], 403)
                    : redirect()->route('despacho.index')->with('error', $msg);
            }
        }

        if ($despacho->estado !== self::ESTADO_TRANSITO) {
            $msg = 'No se puede eliminar un despacho que ya ha sido procesado (Recibido, con incidencias o rechazado).';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : redirect()->route('despacho.index')->with('error', $msg);
        }

        try {
            DB::beginTransaction();

            foreach ($despacho->detalles as $detalle) {
                $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->lockForUpdate()
                    ->first();

                if ($registroOrigen) {
                    $registroOrigen->increment('cantidad', $detalle->cantidad_enviada);
                }
                // Si no existe la fila en origen (drift de datos), no es silencioso en el log:
                else {
                    Log::warning("destroy(): fila InsumosC ausente al revertir stock", [
                        'despacho_id' => $despacho->id,
                        'local'       => $despacho->id_local_origen,
                        'insumo'      => $detalle->id_insumo,
                    ]);
                }
            }

            $despacho->detalles()->delete();
            $despacho->delete();

            DB::commit();

            $msg = 'Despacho eliminado correctamente. El stock ha sido devuelto al depósito de origen.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 200)
                : redirect()->route('despacho.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@destroy: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => $user->id,
            ]);
            $msg = 'Ocurrió un error interno al eliminar el despacho. Intente nuevamente.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 500)
                : redirect()->back()->with('error', $msg);
        }
    }

    /**
     * Muestra el formulario para crear una nueva solicitud de pedido (Estado Pendiente)
     */
    public function createSolicitud()
    {
        Gate::authorize('crear-solicitud');

        $usuario = auth()->user();

        $localesOrigen = Local::all();

        if ($usuario->role === User::ROLE_ENCARGADO) {
            $localesDestino = $usuario->localActual();
        } else {
            $localesDestino = Local::all();
        }

        $insumos = Insumos::where('estado', 'En Venta')->get();

        // Valor sugerido para el formulario; el definitivo se genera server-side
        $codigo = $this->generarCodigo('SOL-');

        return view('despachos.create_solicitud', compact('localesOrigen', 'localesDestino', 'insumos', 'codigo'));
    }

    /**
     * Procesa y guarda la solicitud con estado 'Pendiente' (sin afectar stock)
     */
    public function storeSolicitud(Request $request)
    {
        Gate::authorize('crear-solicitud');

        $user = auth()->user();

        // PARCHE #4: validación estricta (exists + distinct)
        $request->validate([
            'id_local_origen'  => 'required|exists:local,id|different:id_local_destino',
            'id_local_destino' => 'required|exists:local,id',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => [
                'required',
                Rule::exists('insumos', 'id')->where('estado', 'En Venta')
            ],
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ],[
            'id_insumo.*.exists' => 'El insumo solicitado no existe o se encuentra suspendido actualmente.'
        ]);

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalDestino = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $request->id_local_destino)
                ->exists();

            if (!$esSuLocalDestino) {
                return redirect()->back()->with('error', 'No tienes autorización para generar solicitudes destinadas a este local.')->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // PARCHE #4: código server-side
            $codigo = $this->generarCodigo('SOL-');

            $despacho = Despachos::create([
                'codigo'           => $codigo,
                'id_local_origen'  => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'transportado_por' => 'Pendiente de asignar',
                'estado'           => self::ESTADO_PENDIENTE,
                'observacion'      => $request->observacion,
                'fecha_despacho'   => null,
            ]);

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadSolicitada = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);
   
                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadSolicitada,
                    'cantidad_recibida' => 0,
                ]);
            }

            DB::commit();

            // PARCHE #7: notificación fuera de la transacción
            try {
                $userIdsOrigen = DB::table('users_has_local')
                    ->where('id_local', $despacho->id_local_origen)
                    ->pluck('id_user');

                if ($userIdsOrigen->isNotEmpty()) {
                    Notification::send(
                        User::whereIn('id', $userIdsOrigen)->get(),
                        new DespachoNotification($despacho, 'solicitud_creada')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Fallo al enviar notificación de solicitud: ' . $e->getMessage());
            }

            return redirect()->route('despacho.print', $despacho->id)
                ->with('success', 'Solicitud registrada con éxito. Puede imprimir el comprobante a continuación.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@storeSolicitud: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $mensaje = str_contains($e->getMessage(), 'suspendido')
                ? $e->getMessage()
                : 'Ocurrió un error interno al registrar la solicitud. Intente nuevamente.';
            return redirect()->back()->with('error', $mensaje)->withInput();
        }
    }

    /**
     * Procesa el envío de una solicitud pendiente: verifica stock, descuenta
     * inventario y pasa a 'En Tránsito'
     */
    public function procesarEnvioPendiente(Request $request, $id)
    {
        Gate::authorize('procesar-solicitud');

        $user = auth()->user();

        $request->validate([
            'transportado_por' => 'required|string|max:100',
            'vehiculo_placa'   => 'nullable|string|max:50',
            'observacion'      => 'nullable|string|max:1000',
            'cantidades_enviadas'   => 'required|array',
            'cantidades_enviadas.*' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // PARCHES #1 y #3: lock sobre cabecera + guard de estado
            $despacho = Despachos::with('detalles')->lockForUpdate()->findOrFail($id);

            if ($despacho->estado !== self::ESTADO_PENDIENTE) {
                return response()->json(['error' => 'Esta solicitud ya ha sido procesada o cancelada previamente.'], 422);
            }

            if ($user->role === User::ROLE_ENCARGADO) {
                $esSuLocalOrigen = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->where('id_local', $despacho->id_local_origen)
                    ->exists();

                if (!$esSuLocalOrigen) {
                    return response()->json(['error' => 'No tienes autorización para despachar mercancía desde este origen.'], 403);
                }
            }

            // Validación estricta de claves: deben corresponder exactamente a los detalles
            $idsDetalles = $despacho->detalles->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
            $idsEnviados = collect(array_keys($request->cantidades_enviadas))->map(fn ($v) => (int) $v)->sort()->values()->all();

            if ($idsDetalles !== $idsEnviados) {
                return response()->json(['error' => 'Las cantidades enviadas no corresponden a los ítems de la solicitud.'], 422);
            }

            $algunItemEnviado = false;

            foreach ($despacho->detalles as $detalle) {
                $idDetalle = $detalle->id;
                $cantidadAEnviar = (int) $request->cantidades_enviadas[$idDetalle];

                // No se permite enviar más de lo solicitado originalmente
                if ($cantidadAEnviar > $detalle->cantidad_enviada) {
                    throw new \Exception("No se puede enviar más de lo solicitado para el ítem ID {$detalle->id_insumo}.");
                }

                if ($cantidadAEnviar > 0) {
                    $algunItemEnviado = true;

                    // PARCHE #3: bloqueo pesimista
                    $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                        ->where('id_insumo', $detalle->id_insumo)
                        ->lockForUpdate()
                        ->first();

                    $item = Insumos::find($detalle->id_insumo);
                    $nombreItem = $item ? $item->producto : "ID: {$detalle->id_insumo}";

                    if (!$registroOrigen || $registroOrigen->cantidad < $cantidadAEnviar) {
                        throw new \Exception("Stock insuficiente en origen para despachar el ítem: $nombreItem.");
                    }

                    if ($registroOrigen->estado_local !== 'Disponible') {
                        throw new \Exception("El insumo $nombreItem se encuentra suspendido en el local de origen.");
                    }

                    $registroOrigen->decrement('cantidad', $cantidadAEnviar);

                    $detalle->cantidad_enviada = $cantidadAEnviar;
                    $detalle->save();
                } else {
                    $detalle->cantidad_enviada = 0;
                    $detalle->save();
                }
            }

            // Evita despachos "vacíos": al menos un ítem debe viajar
            if (!$algunItemEnviado) {
                return response()->json(['error' => 'Debe enviar al menos un ítem para procesar la solicitud.'], 422);
            }

            $despacho->update([
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa'   => $request->vehiculo_placa,
                'observacion'      => $request->observacion ?? $despacho->observacion,
                'estado'           => self::ESTADO_TRANSITO,
                'fecha_despacho'   => Carbon::now(),
            ]);

            DB::commit();

            // PARCHE #7: notificación fuera de la transacción
            try {
                $userIdsDestino = DB::table('users_has_local')
                    ->where('id_local', $despacho->id_local_destino)
                    ->pluck('id_user');

                if ($userIdsDestino->isNotEmpty()) {
                    Notification::send(
                        User::whereIn('id', $userIdsDestino)->get(),
                        new DespachoNotification($despacho, 'creado')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Fallo al enviar notificación de solicitud en tránsito: ' . $e->getMessage());
            }

            return response()->json(['success' => 'Solicitud procesada con éxito. El despacho se encuentra ahora En Tránsito.']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@procesarEnvioPendiente: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => $user->id,
            ]);
            $mensaje = (str_contains($e->getMessage(), 'Stock insuficiente') || str_contains($e->getMessage(), 'suspendido'))
                ? $e->getMessage()
                : 'Ocurrió un error interno al procesar el envío. Intente nuevamente.';
            return response()->json(['error' => $mensaje], 500);
        }
    }

    public function editSolicitud($id)
    {
        Gate::authorize('editar-solicitud');

        $user = auth()->user();
        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);

        if ($despacho->estado !== self::ESTADO_PENDIENTE) {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede modificar una solicitud que ya ha sido procesada o despachada.');
        }

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalDestino = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_destino)
                ->exists();

            if (!$esSuLocalDestino) {
                return redirect()->route('despacho.index')
                    ->with('error', 'No tienes autorización para editar solicitudes destinadas a este local.');
            }
        }

        $localesOrigen = Local::all();

        if ($user->role === User::ROLE_ENCARGADO) {
            $localesDestino = $user->local;
        } else {
            $localesDestino = Local::all();
        }

        $insumos = Insumos::where('estado', 'En Venta')->get();

        return view('despachos.edit_solicitud', compact('despacho', 'localesOrigen', 'localesDestino', 'insumos'));
    }

    public function updateSolicitud(Request $request, $id)
    {
        Gate::authorize('editar-solicitud');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        if ($despacho->estado !== self::ESTADO_PENDIENTE) {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede modificar una solicitud que ya ha sido procesada o despachada.');
        }

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalDestino = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $request->id_local_destino)
                ->exists();

            if (!$esSuLocalDestino) {
                return redirect()->route('despacho.index')
                    ->with('error', 'No tienes autorización para modificar solicitudes destinadas a este local.');
            }
        }

        // PARCHE #4: exists + distinct
        $request->validate([
            'id_local_origen'  => 'required|exists:local,id|different:id_local_destino',
            'id_local_destino' => 'required|exists:local,id',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => [
                'required',
                Rule::exists('insumos', 'id')->where('estado', 'En Venta')
            ],
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ],[
            'id_insumo.*.exists' => 'El insumo solicitado no existe o se encuentra suspendido actualmente.'
        ]);

        try {
            DB::beginTransaction();

            // Al estar Pendiente, el stock aún no se había descontado: no hay reversas que hacer.

            $despacho->update([
                'id_local_origen'  => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'observacion'      => $request->observacion,
            ]);

            $despacho->detalles()->delete();

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadSolicitada = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);

                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadSolicitada,
                    'cantidad_recibida' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Solicitud de pedido actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@updateSolicitud: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => $user->id,
            ]);
            $mensaje = str_contains($e->getMessage(), 'suspendido')
                ? $e->getMessage()
                : 'Ocurrió un error interno al actualizar la solicitud. Intente nuevamente.';
            return redirect()->back()->with('error', $mensaje)->withInput();
        }
    }

    /**
     * TODO (requiere migración de permisos): este método debería usar un Gate
     * propio 'eliminar-solicitud' en lugar de reutilizar 'editar-solicitud'.
     * Se mantiene el permiso actual para no romper el sistema de roles existente.
     */
    public function destroySolicitud(Request $request, $id)
    {
        Gate::authorize('editar-solicitud');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        if ($despacho->estado !== self::ESTADO_PENDIENTE) {
            return response()->json([
                'message' => 'No se puede eliminar una solicitud que ya ha sido procesada o despachada.'
            ], 422);
        }

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalDestino = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_destino)
                ->exists();

            if (!$esSuLocalDestino) {
                return response()->json([
                    'message' => 'No tienes autorización para eliminar solicitudes destinadas a este local.'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $despacho->detalles()->delete();
            $despacho->delete();

            DB::commit();

            return response()->json(['message' => 'Solicitud de pedido eliminada correctamente.'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en DespachoController@destroySolicitud: ' . $e->getMessage(), [
                'despacho_id' => $id,
                'user_id'     => $user->id,
            ]);
            return response()->json(['message' => 'Ocurrió un error interno al eliminar la solicitud. Intente nuevamente.'], 500);
        }
    }

    /**
     * Muestra la vista de impresión/comprobante físico del despacho
     */
    public function printComprobante($id)
    {
        Gate::authorize('ver-logistica');

        try {
            $user = auth()->user();

            $despacho = Despachos::with(['origen', 'destino', 'detalles.insumos'])->findOrFail($id);

            if ($user->role === User::ROLE_ENCARGADO) {
                $localesIds = $this->localesDelUsuario($user);

                $involucrado = $localesIds->contains($despacho->id_local_origen) ||
                               $localesIds->contains($despacho->id_local_destino);

                if (!$involucrado) {
                    abort(403, 'No tienes autorización para imprimir este comprobante.');
                }
            }

            return view('despachos.print', compact('despacho'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en DespachoController@printComprobante: ' . $e->getMessage(), ['despacho_id' => $id]);
            return redirect()->route('despacho.index')->with('error', 'No se pudo generar el comprobante. Intente nuevamente.');
        }
    }

    /**
     * Retorna en formato JSON los insumos con stock > 0 para un local específico.
     * PARCHE #6: un encargado solo puede consultar el stock de SUS locales.
     */
    public function getInsumosPorLocal($idLocal)
    {
        Gate::authorize('ver-logistica');

        $user = auth()->user();

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocal = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $idLocal)
                ->exists();

            if (!$esSuLocal) {
                return response()->json(['error' => 'No tienes autorización para consultar el inventario de este local.'], 403);
            }
        }

        try {
            $insumos = DB::table('insumos_has_cantidades as insumos_c')
                ->join('insumos', 'insumos_c.id_insumo', '=', 'insumos.id')
                ->where('insumos_c.id_local', $idLocal)
                ->where('insumos_c.cantidad', '>', 0)
                ->where('insumos.estado', 'En Venta')
                ->select(
                    'insumos.id as id',
                    'insumos.serial',
                    'insumos.producto',
                    'insumos.descripcion',
                    'insumos_c.cantidad as stock'
                )
                ->get();

            return response()->json($insumos);
        } catch (\Exception $e) {
            Log::error('Error en DespachoController@getInsumosPorLocal: ' . $e->getMessage(), ['local' => $idLocal]);
            return response()->json(['error' => 'No se pudo consultar el inventario. Intente nuevamente.'], 500);
        }
    }
}
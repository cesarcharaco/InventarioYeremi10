<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Despachos;       
use App\Models\DespachoDetalles; 
use App\Models\Local;
use App\Models\Insumos;         
use App\Models\InsumosC;        
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification; 
use App\Notifications\DespachoNotification;
use Yajra\DataTables\Facades\DataTables;
class DespachoController extends Controller
{
    /**
     * Muestra el historial de despachos
     */
    public function index()
    {
        Gate::authorize('ver-logistica');

        $user = auth()->user();

        // Cargamos los locales para poblar los selectores de los filtros en la vista
        if ($user->role === User::ROLE_ENCARGADO) {
            $localesIds = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->pluck('id_local');
            
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
        
        // Iniciamos la consulta base con relaciones optimizadas
        $query = Despachos::with(['origen', 'destino'])
            ->select('despachos.*')
            ->orderBy('created_at', 'desc');

        // 1. Blindaje por roles: Si es ENCARGADO, filtramos sus locales permitidos
        if ($user->role === User::ROLE_ENCARGADO) {
            $localesIds = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->pluck('id_local');

            $query->where(function ($q) use ($localesIds) {
                $q->whereIn('id_local_origen', $localesIds)
                  ->orWhereIn('id_local_destino', $localesIds);
            });
        }

        // 2. Aplicación de Filtros Personalizados
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
            ->addColumn('origen_nombre', function($row) {
                return $row->origen->nombre ?? 'N/D';
            })
            ->addColumn('destino_nombre', function($row) {
                return $row->destino->nombre ?? 'N/D';
            })
            ->editColumn('codigo', function($row) {
                return '<strong class="text-primary">' . e($row->codigo) . '</strong>';
            })
            ->editColumn('fecha_despacho', function($row) {
                return $row->fecha_despacho ? \Carbon\Carbon::parse($row->fecha_despacho)->format('d/m/Y h:i A') : 'N/D';
            })
            ->editColumn('transportado_por', function($row) {
                $html = e($row->transportado_por);
                if ($row->vehiculo_placa) {
                    $html .= '<small class="text-muted d-block">Placa: ' . e($row->vehiculo_placa) . '</small>';
                }
                return $html;
            })
            ->editColumn('estado', function($row) {
                if ($row->estado == 'En Tránsito') {
                    return '<span class="badge badge-warning text-dark p-2"><i class="fa fa-truck"></i> En Tránsito</span>';
                } elseif ($row->estado == 'Recibido') {
                    return '<span class="badge badge-success p-2"><i class="fa fa-check-circle"></i> Recibido</span>';
                } elseif ($row->estado == 'Con Observaciones') {
                    return '<span class="badge badge-info p-2"><i class="fa fa-exclamation-circle"></i> Con Observaciones</span>';
                } elseif ($row->estado == 'Rechazado' || $row->estado == 'Cancelado') {
                    return '<span class="badge badge-danger p-2"><i class="fa fa-times-circle"></i> ' . e($row->estado) . '</span>';
                } elseif ($row->estado == 'Pendiente') {
                    return '<span class="badge badge-secondary p-2"><i class="fa fa-clock"></i> Pendiente</span>';
                } else {
                    return '<span class="badge badge-secondary p-2">' . e($row->estado) . '</span>';
                }
            })
            ->addColumn('acciones', function($row) {
                $html = '<div class="d-flex justify-content-center align-items-center" style="gap: 5px;">';
                
                // Botón Ver Detalle (Siempre disponible)
                $html .= '<button class="btn btn-info btn-sm text-white" onclick="verDetalle('.$row->id.', \''.$row->codigo.'\')" title="Ver Detalle"><i class="fa fa-eye"></i></button>';

                // Acciones exclusivas para solicitudes Pendientes
                if ($row->estado == 'Pendiente') {
                    if (auth()->user()->can('procesar-solicitud')) {
                        $html .= '<button class="btn btn-primary btn-sm" onclick="procesarEnvioPendienteModal('.$row->id.')" title="Procesar y Enviar Solicitud"><i class="fa fa-paper-plane"></i></button>';   
                    }
                    if (auth()->user()->can('editar-solicitud')) {
                        $html .= '<a href="'.route('despacho.solicitud.edit', $row->id).'" class="btn btn-warning btn-sm" title="Editar Solicitud Pendiente"><i class="fa fa-edit"></i></a>';
                        $html .= '<button class="btn btn-danger btn-sm" onclick="eliminarSolicitud('.$row->id.')" title="Eliminar Solicitud"><i class="fa fa-trash"></i></button>';
                    }
                }

                // Acciones exclusivas para despachos En Tránsito
                if ($row->estado == 'En Tránsito') {
                    /*if (auth()->user()->can('editar-despacho')) {
                        $html .= '<a href="'.route('despacho.edit', $row->id).'" class="btn btn-warning btn-sm" title="Editar Despacho"><i class="fa fa-edit"></i></a>';
                    }*/
                    if (auth()->user()->can('eliminar-despacho')) {
                        $html .= '<button class="btn btn-danger btn-sm" onclick="eliminarDespacho('.$row->id.')" title="Eliminar Despacho"><i class="fa fa-trash"></i></button>';
                    }
                    if (auth()->user()->can('recibir-despacho')) {
                        $html .= '<button class="btn btn-success btn-sm" onclick="confirmarRecepcion('.$row->id.')" title="Confirmar Recepción"><i class="fa fa-check-square"></i></button>';
                    }
                }   

                $html .= '</div>';
                return $html;
            })
            ->filterColumn('origen_nombre', function($q, $kw) {
                $q->whereHas('origen', function($query) use ($kw) {
                    $query->where('nombre', 'LIKE', "%{$kw}%");
                });
            })
            ->filterColumn('destino_nombre', function($q, $kw) {
                $q->whereHas('destino', function($query) use ($kw) {
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
        
        // Obtenemos el usuario autenticado (¡Esto faltaba para evitar el error de variable indefinida!)
        $usuario = auth()->user();

        // 1. Locales de Origen: Depende de los privilegios del usuario
        if (Gate::allows('seleccionar-cualquier-origen')) {
            $localesOrigen = Local::all();
        } else {
            // El encargado solo puede usar los locales que tiene asignados en su perfil
            $localesOrigen = $usuario->local; // Asegúrate de que la relación en el modelo User sea correcta
        }

        // 2. Locales de Destino: La mercancía puede ser enviada a cualquier local o depósito de la red
        $localesDestino = Local::all();

        // Solo traemos insumos con estado global 'En Venta'
        $insumos = Insumos::where('estado', 'En Venta')->get();
        
        // Generar un código único sugerido: DESP-AñoMesDia-ID
        $ultimoId = Despachos::max('id') + 1;
        $codigo = 'DESP-' . date('Ymd') . '-' . str_pad($ultimoId, 3, '0', STR_PAD_LEFT);

        return view('despachos.create', compact('localesOrigen', 'localesDestino', 'insumos', 'codigo'));
    }

    /**
     * Procesa y guarda el despacho en la base de datos (Salida de Depósito)
     */
    public function store(Request $request)
        {
            Gate::authorize('crear-despacho');

            $user = auth()->user();

            // Validar si el usuario es encargado y está intentando despachar desde un local ajeno[cite: 15]
            if ($user->role === User::ROLE_ENCARGADO) {
                $esSuLocal = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->where('id_local', $request->id_local_origen)
                    ->exists();

                if (!$esSuLocal) {
                    return redirect()->back()->with('error', 'No tienes autorización para despachar mercancía desde este local de origen.')->withInput();
                }
            }
              
            $request->validate([
                'id_local_origen'  => 'required|different:id_local_destino',
                'id_local_destino' => 'required',
                'transportado_por' => 'required|string|max:100',
                'id_insumo'        => 'required|array',
                'id_insumo.*'      => 'required|exists:insumos,id',
                'cantidad'         => 'required|array',
                'cantidad.*'       => 'required|integer|min:1',
            ]);

            // Validación añadida: Verificar que el local o depósito de destino tenga al menos un usuario asignado
            $tieneUsuariosDestino = DB::table('users_has_local')
                ->where('id_local', $request->id_local_destino)
                ->exists();

            if (!$tieneUsuariosDestino) {
                return redirect()->back()->with('error', 'No es posible generar el despacho porque no hay usuario asignado a dicho local o depósito.')->withInput();
            }

            try {
                DB::beginTransaction();

                // 1. Crear la Cabecera del Despacho (En Tránsito)[cite: 15]
                $despacho = Despachos::create([
                    'codigo'           => $request->codigo,
                    'id_local_origen'  => $request->id_local_origen,
                    'id_local_destino' => $request->id_local_destino,
                    'transportado_por' => $request->transportado_por,
                    'vehiculo_placa'   => $request->vehiculo_placa,
                    'observacion'      => $request->observacion,
                    'estado'           => 'En Tránsito',
                    'fecha_despacho'   => Carbon::now(),
                ]);

                // 2. Procesar cada Insumo enviado[cite: 15]
                foreach ($request->id_insumo as $key => $insumo_id) {
                    $cantidadADespachar = $request->cantidad[$key];

                    $registroOrigen = InsumosC::where('id_local', $request->id_local_origen)
                        ->where('id_insumo', $insumo_id)
                        ->first();

                    $item = Insumos::find($insumo_id);
                    $nombreItem = $item ? $item->producto : "ID: $insumo_id";

                    if (!$registroOrigen || $registroOrigen->cantidad < $cantidadADespachar) {
                        throw new \Exception("Stock insuficiente para: $nombreItem en el depósito de origen.");
                    }

                    if ($registroOrigen->estado_local !== 'Disponible') {
                        throw new \Exception("El insumo $nombreItem se encuentra SUSPENDIDO en este local.");
                    }

                    $registroOrigen->decrement('cantidad', $cantidadADespachar);

                    DespachoDetalles::create([
                        'id_despacho'         => $despacho->id,
                        'id_insumo'           => $insumo_id,
                        'cantidad_enviada'    => $cantidadADespachar,
                        'cantidad_recibida'   => 0, 
                    ]);
                }

                // ==========================================
                // 3. ENVÍO DE NOTIFICACIONES A DESTINO[cite: 15]
                // ==========================================
                $userIdsDestino = DB::table('users_has_local')
                    ->where('id_local', $despacho->id_local_destino)
                    ->pluck('id_user');

                if ($userIdsDestino->isNotEmpty()) {
                    $usuariosARecibir = User::whereIn('id', $userIdsDestino)->get();
                    Notification::send($usuariosARecibir, new DespachoNotification($despacho, 'creado'));
                }
                
                DB::commit();
                return redirect()->route('despacho.print', $despacho->id)
                        ->with('success', 'Despacho emitido con éxito. Puede imprimir el comprobante a continuación.');

            } catch (\Exception $e) {
                DB::rollback();
                return redirect()->back()->with('error', $e->getMessage())->withInput();
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
            // Si el producto ya existe en la tienda, aumentamos el stock
            $registroDestino->increment('cantidad', $cantidad);
        } else {
            // Si el producto nunca ha estado en esa tienda, creamos el registro inicial
            InsumosC::create([
                'id_local' => $id_local,
                'id_insumo' => $id_insumo,
                'cantidad' => $cantidad
            ]);
        }
    }

    public function show($id)
    {
        Gate::authorize('ver-logistica');
        try {
            $user = auth()->user();

            // 1. Buscamos el despacho cargando todas sus relaciones de una vez (Eager Loading optimizado)
            $despacho = Despachos::with(['origen', 'destino', 'detalles.insumos'])->findOrFail($id);

            // 2. Blindaje Multi-tienda: Si es encargado, verificar que su local sea origen o destino
            if ($user->role === User::ROLE_ENCARGADO) {
                $localesIds = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->pluck('id_local');

                $involucrado = $localesIds->contains($despacho->id_local_origen) || 
                               $localesIds->contains($despacho->id_local_destino);

                if (!$involucrado) {
                    return response("No tienes autorización para ver los detalles de este despacho.", 403);
                }
            }

            // 3. Como ya usamos 'detalles.insumos' en el with(), podemos pasarlos directo
            $detalles = $despacho->detalles;

            return view('despachos.modal_detalle', compact('despacho', 'detalles'));

        } catch (\Exception $e) {
            return response("Error en Servidor: " . $e->getMessage(), 500);
        }
    }
    
    public function confirmarRecepcion(Request $request, $id)
    {
        Gate::authorize('recibir-despacho');

        $request->validate([
            'estado'                 => 'required|in:Recibido,recibido_con_incidencias,Cancelado',
            'observacion_recepcion'  => 'nullable|string|max:1000',
            'observacion_recepcion' => 'nullable|string|max:1000',
            'cantidades_recibidas'  => 'required|array',
            'cantidades_recibidas.*'=> 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $despacho = Despachos::with('detalles')->findOrFail($id);
            $user = auth()->user();

            // 1. Blindaje Multi-tienda: Validar que el encargado pertenezca al local de destino
            if ($user->role === User::ROLE_ENCARGADO) {
                $esSuLocalDestino = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->where('id_local', $despacho->id_local_destino)
                    ->exists();

                if (!$esSuLocalDestino) {
                    return response()->json(['error' => 'No tienes autorización para recibir despachos dirigidos a este local.'], 403);
                }
            }

            // 2. Actualizar la cabecera del despacho
            $despacho->estado = $request->estado; // 'Recibido', 'Con Observaciones', 'Rechazado'
            $despacho->observacion_recepcion = $request->observacion_recepcion;
            $despacho->fecha_recepcion = Carbon::now();
            $despacho->save();

            // 3. Procesar cada ítem del detalle
            foreach ($despacho->detalles as $detalle) {
                $idDetalle = $detalle->id;
                
                // Tomamos la cantidad que el usuario indicó que llegó físicamente
                $cantidadRecibida = $request->cantidades_recibidas[$idDetalle] ?? 0;

                // Validar lógica física: No puedes recibir más de lo que se despachó originalmente
                if ($cantidadRecibida > $detalle->cantidad_enviada) {
                    throw new \Exception("La cantidad recibida no puede ser mayor a la cantidad enviada para el ítem.");
                }

                // Guardar lo que realmente llegó en el detalle
                $detalle->cantidad_recibida = $cantidadRecibida;
                $detalle->save();

                // 4. Actualizar stock en destino (Solo si el despacho NO fue rechazado por completo)
                if ($request->estado !== 'Cancelado' && $cantidadRecibida > 0) {
                    $this->gestionarStockDestino(
                        $despacho->id_local_destino, 
                        $detalle->id_insumo, 
                        $cantidadRecibida
                    );
                }
            }
            // ==========================================
            // 5. ENVÍO DE NOTIFICACIÓN DE VUELTA AL ORIGEN
            // ==========================================

            $userIdsOrigen = DB::table('users_has_local')
                ->where('id_local', $despacho->id_local_origen)
                ->pluck('id_user');

            if ($userIdsOrigen->isNotEmpty()) {
                $usuariosOrigen = User::whereIn('id', $userIdsOrigen)->get();
                // Le pasamos el despacho y el tipo 'recibido'
                Notification::send($usuariosOrigen, new DespachoNotification($despacho, 'recibido'));
            }

            DB::commit();
            return response()->json(['success' => 'La recepción del despacho se ha procesado e inventariado correctamente.']);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al procesar la recepción: ' . $e->getMessage()], 500);
        }
    }

    public function getJson($id)
    {
        Gate::authorize('recibir-despacho');

        // Usando los nombres reales de tus modelos: origen, destino y detalles.insumos
        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);

        return response()->json($despacho);
    }

    public function edit($id)
    {
        Gate::authorize('editar-despacho');

        $user = auth()->user();

        // 1. Carga del despacho con sus detalles, insumos y relaciones
        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);
        
        // 2. Blindaje Multi-tienda: Si es encargado, verificar que el despacho haya salido de su local
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

        // 3. Validación de estado: Solo se puede editar si sigue en tránsito
        if ($despacho->estado !== 'En Tránsito') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede editar un despacho que ya ha sido procesado (Recibido, con observaciones o rechazado).');
        }

        // 4. Locales para los selects (separados por permisos al igual que en create)
        if (Gate::allows('seleccionar-cualquier-origen')) {
            $localesOrigen = Local::all();
        } else {
            $localesOrigen = $user->local;
        }
        
        $localesDestino = Local::all();

        // 5. Insumos disponibles para venta
        $insumos = Insumos::where('estado', 'En Venta')->get(); 

        // Nota: Revisa si tu carpeta de vistas se llama 'despachos' (plural) o 'despacho' (singular)
        return view('despachos.edit', compact('despacho', 'localesOrigen', 'localesDestino', 'insumos'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('editar-despacho');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        // 1. Blindaje Multi-tienda para el Encargado
        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                return redirect()->route('despacho.index')->with('error', 'No tienes autorización para modificar este despacho.');
            }
        }

        // 2. Validación de estado: Solo se edita si está En Tránsito
        if ($despacho->estado !== 'En Tránsito') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede editar un despacho que ya ha sido procesado (Recibido, con observaciones o rechazado).');
        }

        $request->validate([
            'transportado_por' => 'required|string|max:100',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => 'required|exists:insumos,id',
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // PASO 1: REVERTIR EL STOCK SOLO EN ORIGEN 
            // (El destino no se toca porque la mercancía aún no había sido recibida allí)
            foreach ($despacho->detalles as $detalle) {
                InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->increment('cantidad', $detalle->cantidad_enviada);
            }

            // PASO 2: ACTUALIZAR CABECERA
            $despacho->update([
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa'   => $request->vehiculo_placa,
                'observacion'      => $request->observacion,
            ]);

            // PASO 3: BORRAR DETALLES VIEJOS Y PROCESAR LOS NUEVOS
            $despacho->detalles()->delete();

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadNueva = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);

                // Validar estado del insumo
                if ($item->estado !== 'En Venta') {
                    throw new \Exception("El insumo {$item->producto} se encuentra suspendido.");
                }

                // Validar stock actualizado en origen
                $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $insumo_id)
                    ->first();

                if (!$registroOrigen || $registroOrigen->cantidad < $cantidadNueva) {
                    throw new \Exception("Stock insuficiente en origen para el insumo: {$item->producto}");
                }

                // Descontar la nueva cantidad del origen de inmediato
                $registroOrigen->decrement('cantidad', $cantidadNueva);

                // Crear el nuevo detalle (manteniendo cantidad_enviada y cantidad_recibida en 0)
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
            return redirect()->back()->with('error', 'Error en la actualización: ' . $e->getMessage())->withInput();
        }
    }
    public function destroy($id)
    {
        Gate::authorize('eliminar-despacho');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        // 1. Blindaje Multi-tienda: Si es encargado, verificar que el despacho se originó en su local
        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                return redirect()->route('despacho.index')
                    ->with('error', 'No tienes autorización para eliminar despachos que no se originan en tu local.');
            }
        }

        // 2. Validación de estado: Solo se puede eliminar si la mercancía no ha sido entregada
        if ($despacho->estado !== 'En Tránsito') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede eliminar un despacho que ya ha sido procesado (Recibido, con observaciones o rechazado).');
        }

        try {
            DB::beginTransaction();

            // 3. Revertir el stock exclusivamente en el LOCAL DE ORIGEN
            foreach ($despacho->detalles as $detalle) {
                $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->first();
                
                if ($registroOrigen) {
                    // Devolvemos exactamente lo que se había enviado
                    $registroOrigen->increment('cantidad', $detalle->cantidad_enviada);
                }
                
                // Nota: No tocamos el destino porque, al estar 'En Tránsito', 
                // la mercancía jamás había ingresado al inventario de la tienda receptora.
            }

            // 4. Eliminar los detalles y la cabecera del despacho
            $despacho->detalles()->delete();
            $despacho->delete();

            DB::commit();
            return redirect()->route('despacho.index')
                ->with('success', 'Despacho eliminado correctamente. El stock ha sido devuelto al depósito de origen.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al eliminar el despacho: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para crear una nueva solicitud de pedido (Estado Pendiente)
     */
    public function createSolicitud()
    {
        Gate::authorize('crear-solicitud');
        
        $usuario = auth()->user();

        // 1. Locales de Origen: El depósito central o cualquier almacén que pueda abastecer
        $localesOrigen = Local::all();

        // 2. Locales de Destino: Si es encargado, su local por defecto; si es admin, cualquiera de la red
        if ($usuario->role === User::ROLE_ENCARGADO) {
            // Usamos el método helper que ya tienes definido en el modelo User
            $localesDestino = $usuario->localActual(); 
        } else {
            $localesDestino = Local::all();
        }

        // Insumos disponibles en el sistema
        $insumos = Insumos::where('estado', 'En Venta')->get();
        
        // Generar un código único para la solicitud
        $ultimoId = Despachos::max('id') + 1;
        $codigo = 'SOL-' . date('Ymd') . '-' . str_pad($ultimoId, 3, '0', STR_PAD_LEFT);

        return view('despachos.create_solicitud', compact('localesOrigen', 'localesDestino', 'insumos', 'codigo'));
    }

    /**
     * Procesa y guarda la solicitud en la base de datos con estado 'Pendiente' (Sin afectar stock aún)
     */
    public function storeSolicitud(Request $request)
    {
        Gate::authorize('crear-solicitud');

        $user = auth()->user();

        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalDestino = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $request->id_local_destino)
                ->exists();

            if (!$esSuLocalDestino) {
                return redirect()->back()->with('error', 'No tienes autorización para generar solicitudes destinadas a este local.')->withInput();
            }
        }
          
        $request->validate([
            'id_local_origen'  => 'required|different:id_local_destino',
            'id_local_destino' => 'required',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => 'required|exists:insumos,id',
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $despacho = Despachos::create([
                'codigo'           => $request->codigo,
                'id_local_origen'  => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'transportado_por' => 'Pendiente de asignar',
                'estado'           => 'Pendiente',
                'observacion'      => $request->observacion,
                'fecha_despacho'   => null,
            ]);

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadSolicitada = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);

                // ¡AQUÍ ESTABA EL DETALLE! Validar que el insumo esté disponible para la venta
                if ($item->estado !== 'En Venta') {
                    throw new \Exception("El insumo {$item->producto} se encuentra suspendido y no puede ser solicitado.");
                }

                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadSolicitada, 
                    'cantidad_recibida' => 0, 
                ]);
            }

            $userIdsOrigen = DB::table('users_has_local')
                ->where('id_local', $despacho->id_local_origen)
                ->pluck('id_user');

            if ($userIdsOrigen->isNotEmpty()) {
                $usuariosOrigen = User::whereIn('id', $userIdsOrigen)->get();
                Notification::send($usuariosOrigen, new DespachoNotification($despacho, 'solicitud_creada'));
            }
            
            DB::commit();
            return redirect()->route('despacho.print', $despacho->id)
                                   ->with('success', 'Despacho emitido con éxito. Puede imprimir el comprobante a continuación.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al registrar la solicitud: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Procesa el envío de una solicitud pendiente: verifica stock, descuenta inventario y pasa a 'En Tránsito'
     */
    public function procesarEnvioPendiente(Request $request, $id)
    {
        Gate::authorize('procesar-solicitud'); // O el permiso que use el almacenista

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        // 1. Validar que el despacho se encuentre estrictamente en estado Pendiente
        if ($despacho->estado !== 'Pendiente') {
            return response()->json(['error' => 'Esta solicitud ya ha sido procesada o cancelada previamente.'], 422);
        }

        // 2. Blindaje Multi-tienda: Validar que el almacenista pertenezca al local de origen si es encargado
        if ($user->role === User::ROLE_ENCARGADO) {
            $esSuLocalOrigen = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->where('id_local', $despacho->id_local_origen)
                ->exists();

            if (!$esSuLocalOrigen) {
                return response()->json(['error' => 'No tienes autorización para despachar mercancía desde este origen.'], 403);
            }
        }

        $request->validate([
            'transportado_por' => 'required|string|max:100',
            'vehiculo_placa'   => 'nullable|string|max:50',
            // Permite ajustar cantidades reales enviadas por si el almacén no tiene stock completo de todo
            'cantidades_enviadas'   => 'required|array',
            'cantidades_enviadas.*' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 3. Validar y descontar stock del depósito de origen para cada ítem
            foreach ($despacho->detalles as $detalle) {
                $idDetalle = $detalle->id;
                $cantidadAEnviar = $request->cantidades_enviadas[$idDetalle] ?? 0;

                if ($cantidadAEnviar > 0) {
                    $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                        ->where('id_insumo', $detalle->id_insumo)
                        ->first();

                    $item = Insumos::find($detalle->id_insumo);
                    $nombreItem = $item ? $item->producto : "ID: {$detalle->id_insumo}";

                    if (!$registroOrigen || $registroOrigen->cantidad < $cantidadAEnviar) {
                        throw new \Exception("Stock insuficiente en origen para despachar el ítem: $nombreItem.");
                    }

                    if ($registroOrigen->estado_local !== 'Disponible') {
                        throw new \Exception("El insumo $nombreItem se encuentra suspendido en el local de origen.");
                    }

                    // Descontar inventario físicamente
                    $registroOrigen->decrement('cantidad', $cantidadAEnviar);

                    // Actualizar la cantidad enviada definitiva en el detalle
                    $detalle->cantidad_enviada = $cantidadAEnviar;
                    $detalle->save();
                } else {
                    // Si deciden no enviar nada de este ítem específico
                    $detalle->cantidad_enviada = 0;
                    $detalle->save();
                }
            }

            // 4. Actualizar la cabecera cambiando el estado a En Tránsito
            $despacho->update([
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa'   => $request->vehiculo_placa,
                'observacion'      => $request->observacion ?? $despacho->observacion,
                'estado'           => 'En Tránsito',
                'fecha_despacho'   => Carbon::now(),
            ]);

            // 5. Notificar al local de destino que su pedido ya va en camino
            $userIdsDestino = DB::table('users_has_local')
                ->where('id_local', $despacho->id_local_destino)
                ->pluck('id_user');

            if ($userIdsDestino->isNotEmpty()) {
                $usuariosDestino = User::whereIn('id', $userIdsDestino)->get();
                Notification::send($usuariosDestino, new DespachoNotification($despacho, 'creado'));
            }

            DB::commit();
            return response()->json(['success' => 'Solicitud procesada con éxito. El despacho se encuentra ahora En Tránsito.']);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al procesar el envío: ' . $e->getMessage()], 500);
        }
    }
    public function editSolicitud($id)
    {
        Gate::authorize('editar-solicitud');

        $user = auth()->user();
        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);

        // 1. REGLA CLAVE: Si ya no está pendiente, prohibir la edición
        if ($despacho->estado !== 'Pendiente') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede modificar una solicitud que ya ha sido procesada o despachada.');
        }

        // 2. Blindaje Multi-tienda: Si es encargado, verificar que la solicitud pertenezca a su local de destino
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

        // 3. Cargar los mismos datos que 'createSolicitud' para que los selects y tablas funcionen igual
        $localesOrigen = Local::all();

        if ($user->role === User::ROLE_ENCARGADO) {
            $localesDestino = $user->local;
        } else {
            $localesDestino = Local::all();
        }

        $insumos = Insumos::where('estado', 'En Venta')->get();

        // 4. Retornar la vista enviando todas las variables necesarias
        return view('despachos.edit_solicitud', compact('despacho', 'localesOrigen', 'localesDestino', 'insumos'));
    }

    public function updateSolicitud(Request $request, $id)
    {
        Gate::authorize('editar-solicitud');

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        // 1. REGLA CLAVE: Si ya no está pendiente, prohibir la modificación
        if ($despacho->estado !== 'Pendiente') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede modificar una solicitud que ya ha sido procesada o despachada.');
        }

        // 2. Blindaje Multi-tienda para el Encargado (validando el nuevo local destino)
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

        $request->validate([
            'id_local_origen'  => 'required|different:id_local_destino',
            'id_local_destino' => 'required',
            'id_insumo'        => 'required|array',
            'id_insumo.*'      => 'required|exists:insumos,id',
            'cantidad'         => 'required|array',
            'cantidad.*'       => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Nota: Como la solicitud está 'Pendiente', el stock aún no se había descontado en origen,
            // por lo que NO es necesario hacer operaciones inversas de inventario aquí.

            // PASO 1: ACTUALIZAR CABECERA DE LA SOLICITUD
            $despacho->update([
                'id_local_origen'  => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'observacion'      => $request->observacion,
            ]);

            // PASO 2: BORRAR LOS DETALLES VIEJOS
            $despacho->detalles()->delete();

            // PASO 3: PROCESAR Y REGISTRAR LOS NUEVOS DETALLES
            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadSolicitada = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);

                // Validar que el insumo siga activo para la venta
                if ($item->estado !== 'En Venta') {
                    throw new \Exception("El insumo {$item->producto} se encuentra suspendido.");
                }

                DespachoDetalles::create([
                    'id_despacho'       => $despacho->id,
                    'id_insumo'         => $insumo_id,
                    'cantidad_enviada'  => $cantidadSolicitada, // Al estar pendiente, guarda la cantidad que se está pidiendo
                    'cantidad_recibida' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Solicitud de pedido actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al actualizar la solicitud: ' . $e->getMessage())->withInput();
        }
    }

    public function destroySolicitud(Request $request, $id)
    {
        Gate::authorize('editar-solicitud'); 

        $user = auth()->user();
        $despacho = Despachos::with('detalles')->findOrFail($id);

        // 1. Validar estado Pendiente
        if ($despacho->estado !== 'Pendiente') {
            return response()->json([
                'message' => 'No se puede eliminar una solicitud que ya ha sido procesada o despachada.'
            ], 422);
        }

        // 2. Blindaje Multi-tienda
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

            return response()->json([
                'message' => 'Solicitud de pedido eliminada correctamente.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error al eliminar la solicitud: ' . $e->getMessage()
            ], 500);
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

            // Cargar el despacho con sus relaciones optimizadas
            $despacho = Despachos::with(['origen', 'destino', 'detalles.insumos'])->findOrFail($id);

            // Blindaje multi-tienda para encargados
            if ($user->role === User::ROLE_ENCARGADO) {
                $localesIds = DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->pluck('id_local');

                $involucrado = $localesIds->contains($despacho->id_local_origen) || 
                               $localesIds->contains($despacho->id_local_destino);

                if (!$involucrado) {
                    abort(403, 'No tienes autorización para imprimir este comprobante.');
                }
            }

            // Retorna una vista optimizada exclusivamente para impresión térmica o carta
            return view('despachos.print', compact('despacho'));

        } catch (\Exception $e) {
            return redirect()->route('despacho.index')->with('error', 'Error al generar el comprobante: ' . $e->getMessage());
        }
    }

    /**
     * Retorna en formato JSON los insumos con stock > 0 para un local específico
     */
    public function getInsumosPorLocal($idLocal)
    {
        Gate::authorize('ver-logistica');

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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
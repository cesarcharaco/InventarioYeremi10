<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Despachos;       // Modelo en plural
use App\Models\DespachoDetalles; // Modelo en plural
use App\Models\Local;
use App\Models\Insumos;         // Modelo en plural
use App\Models\InsumosC;        // Modelo para insumos_has_cantidades
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification; 
use App\Notifications\DespachoNotification;

class DespachoController extends Controller
{
    /**
     * Muestra el historial de despachos
     */
    public function index()
    {
        Gate::authorize('ver-logistica');

        $user = auth()->user();
        
        // Iniciamos la consulta cargando las relaciones para evitar N+1
        $query = Despachos::with(['origen', 'destino'])->orderBy('created_at', 'desc');

        // Si el usuario es ENCARGADO, filtramos para que solo vea los despachos de sus locales
        if ($user->role === User::ROLE_ENCARGADO) {
            // Buscamos los IDs de los locales permitidos para este usuario
            $localesIds = DB::table('users_has_local')
                ->where('id_user', $user->id)
                ->pluck('id_local');

            // Un despacho le pertenece si su local es el origen O el destino
            $query->where(function ($q) use ($localesIds) {
                $q->whereIn('id_local_origen', $localesIds)
                  ->orWhereIn('id_local_destino', $localesIds);
            });
        }

        $despachos = $query->get();

        return view('despachos.index', compact('despachos'));
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

        // Validar si el usuario es encargado y está intentando despachar desde un local ajeno[cite: 1]
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

        try {
            DB::beginTransaction();

            // 1. Crear la Cabecera del Despacho (En Tránsito)[cite: 1]
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

            // 2. Procesar cada Insumo enviado[cite: 1]
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
            // 3. ENVÍO DE NOTIFICACIONES A DESTINO
            // ==========================================
            // Buscamos los IDs de todos los usuarios vinculados al local/depósito receptor
            $userIdsDestino = DB::table('users_has_local')
                ->where('id_local', $despacho->id_local_destino)
                ->pluck('id_user');

            if ($userIdsDestino->isNotEmpty()) {
                $usuariosARecibir = User::whereIn('id', $userIdsDestino)->get();
                // Le pasamos el despacho y el tipo 'creado'
                Notification::send($usuariosARecibir, new DespachoNotification($despacho, 'creado'));
            }

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Despacho emitido con éxito. Notificación enviada al personal de destino.');

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
    
}
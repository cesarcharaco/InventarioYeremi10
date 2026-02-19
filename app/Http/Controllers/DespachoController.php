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

class DespachoController extends Controller
{
    /**
     * Muestra el historial de despachos
     */
    public function index()
    {
        Gate::authorize('ver-logistica');
        // Cargamos relaciones para evitar el problema de N+1 consultas
        $despachos = Despachos::with(['origen', 'destino'])->orderBy('created_at', 'desc')->get();
        return view('despachos.index', compact('despachos'));
    }

    /**
     * Muestra el formulario para crear un nuevo despacho
     */
    public function create()
    {
        Gate::authorize('crear-despacho');
            // Si tiene permiso global, trae todos los locales
        if (Gate::allows('seleccionar-cualquier-origen')) {
            $locales = Local::all();
        } else {
            // Si no, solo puede usar el local al que pertenece
            $locales = $usuario->local; // Relación belongsToMany
        }        
        // Solo traemos insumos con estado global 'En Venta'
        $insumos = Insumos::where('estado', 'En Venta')->get();
        
        // Generar un código único sugerido: DESP-AñoMesDia-ID
        $ultimoId = Despachos::max('id') + 1;
        $codigo = 'DESP-' . date('Ymd') . '-' . str_pad($ultimoId, 3, '0', STR_PAD_LEFT);

        return view('despachos.create', compact('locales', 'insumos', 'codigo'));
    }

    /**
     * Procesa y guarda el despacho en la base de datos
     */
    public function store(Request $request)
    {
        Gate::authorize('crear-despacho');
            $request->validate([
            'id_local_origen' => 'required|different:id_local_destino',
            'id_local_destino' => 'required',
            'transportado_por' => 'required|string|max:100',
            'id_insumo' => 'required|array',
            'id_insumo.*' => 'required|exists:insumos,id',
            'cantidad' => 'required|array',
            'cantidad.*' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear la Cabecera del Despacho
            $despacho = Despachos::create([
                'codigo' => $request->codigo,
                'id_local_origen' => $request->id_local_origen,
                'id_local_destino' => $request->id_local_destino,
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa' => $request->vehiculo_placa,
                'observacion' => $request->observacion,
                'estado' => 'En Tránsito',
                'fecha_despacho' => Carbon::now(),
            ]);

            // 2. Procesar cada Insumo enviado
            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadADespachar = $request->cantidad[$key];

                // --- VALIDACIÓN DE STOCK Y ESTADO LOCAL ---
                $registroOrigen = InsumosC::where('id_local', $request->id_local_origen)
                    ->where('id_insumo', $insumo_id)
                    ->first();

                // Buscamos el objeto insumo para obtener su nombre en caso de error
                $item = Insumos::find($insumo_id);
                $nombreItem = $item ? $item->producto : "ID: $insumo_id";

                // Validamos existencia, cantidad Y que el estado en ese local sea 'Disponible'
                if (!$registroOrigen || $registroOrigen->cantidad < $cantidadADespachar) {
                    throw new \Exception("Stock insuficiente para: $nombreItem en el local de origen.");
                }

                if ($registroOrigen->estado_local !== 'Disponible') {
                    throw new \Exception("El insumo $nombreItem se encuentra SUSPENDIDO en este local.");
                }

                // Decrementar cantidad en el local de origen
                $registroOrigen->decrement('cantidad', $cantidadADespachar);

                // --- SUMA O CREACIÓN EN DESTINO ---
                $this->gestionarStockDestino($request->id_local_destino, $insumo_id, $cantidadADespachar);

                // 3. Registrar el Detalle del Despacho
                DespachoDetalles::create([
                    'id_despacho' => $despacho->id,
                    'id_insumo' => $insumo_id,
                    'cantidad' => $cantidadADespachar,
                ]);
            }

            DB::commit();
            return redirect()->route('despacho.create')->with('success', 'Despacho procesado exitosamente. Stock actualizado en origen y destino.');

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
            // 1. Buscamos el despacho solo con origen y destino
            $despacho = \App\Models\Despachos::with(['origen', 'destino'])->findOrFail($id);

            // 2. Cargamos los detalles manualmente para probar la relación
            // Si aquí falla, el problema es la función 'detalles' en el modelo Despachos
            $detalles = \App\Models\DespachoDetalles::where('id_despacho', $id)
                        ->with(['insumos'])
                        ->get();

            return view('despachos.modal_detalle', compact('despacho', 'detalles'));

        } catch (\Exception $e) {
            // Esto hará que el error 500 se convierta en un mensaje de texto que podrás ver en el "Preview" de la consola
            return response("Error en Servidor: " . $e->getMessage(), 500);
        }
    }
    
    public function confirmar($id)
    {
        Gate::authorize('recibir-despacho');
        try {
            $despacho = \App\Models\Despachos::findOrFail($id);
            
            $despacho->estado = 'Recibido';
            $despacho->save();

            return response()->json(['success' => 'El despacho ha sido cerrado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo procesar la recepción.'], 500);
        }
    }

    public function edit($id)
    {
        Gate::authorize('editar-despacho');
        // 1. Verificación de seguridad
        if (Gate::denies('editar-despacho')) {
            return redirect()->back()->with('error', 'No tienes permiso para editar despachos.');
            
        }

        // 2. Carga del despacho con sus detalles e insumos relacionados
        // Usamos eager loading (with) para que la vista cargue rápido y tenga los nombres de los productos
        $despacho = Despachos::with(['detalles.insumos', 'origen', 'destino'])->findOrFail($id);
        
        // 3. Validación de estado
        if ($despacho->estado == 'Recibido') {
            return redirect()->route('despacho.index')
                ->with('error', 'No se puede editar un despacho que ya ha sido recibido.');
        }

        // 4. Datos necesarios para los selects del formulario
        // Necesitas los locales y los insumos para que el usuario pueda cambiar o agregar items
        $locales = Local::all(); // Ajusta al nombre de tu modelo de locales
        $// Filtro semántico gracias al enum
        $insumos = Insumos::where('estado', 'En Venta')->get(); 
        return view('despacho.edit', compact('despacho', 'locales', 'insumos'));
    }

    public function update(Request $request, $id)
    {

        if (Gate::denies('editar-despacho')) {
            return redirect()->back()->with('error', 'No tienes permiso.');
        }

        $despacho = Despachos::with('detalles')->findOrFail($id);

        // Si ya fue recibido, mejor bloquear la edición
        if ($despacho->estado == 'Recibido') {
            return redirect()->back()->with('error', 'No se puede editar un despacho que ya ha sido recibido.');
        }

        try {
            DB::beginTransaction();

            // PASO 1: REVERTIR EL STOCK ACTUAL (Antes de los cambios)
            foreach ($despacho->detalles as $detalle) {
                // Devolver al origen
                InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->increment('cantidad', $detalle->cantidad);
                
                // Quitar del destino
                InsumosC::where('id_local', $despacho->id_local_destino)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->decrement('cantidad', $detalle->cantidad);
            }

            // PASO 2: ACTUALIZAR CABECERA
            $despacho->update([
                'transportado_por' => $request->transportado_por,
                'vehiculo_placa' => $request->vehiculo_placa,
                'observacion' => $request->observacion,
                // Si permites cambiar locales, actualízalos aquí, pero es riesgoso
            ]);

            // PASO 3: PROCESAR NUEVOS INSUMOS (Similar a tu store)
            // Primero borramos los detalles viejos
            $despacho->detalles()->delete();

            foreach ($request->id_insumo as $key => $insumo_id) {
                $cantidadNueva = $request->cantidad[$key];
                $item = Insumos::findOrFail($insumo_id);

                // VALIDACIÓN DE ESTADO EN EDICIÓN
                if ($item->estado !== 'En Venta') {
                    throw new \Exception("El insumo {$item->producto} no puede ser despachado (Estado actual: {$item->estado}).");
                }

                // Validar stock en origen de nuevo
                $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $insumo_id)
                    ->first();

                if (!$registroOrigen || $registroOrigen->cantidad < $cantidadNueva) {
                    throw new \Exception("Stock insuficiente tras la edición para el insumo ID: $insumo_id");
                }

                // Aplicar resta en origen
                $registroOrigen->decrement('cantidad', $cantidadNueva);

                // Aplicar suma en destino (usando tu método del store)
                $this->gestionarStockDestino($despacho->id_local_destino, $insumo_id, $cantidadNueva);

                // Crear nuevo detalle
                DespachoDetalles::create([
                    'id_despacho' => $despacho->id,
                    'id_insumo' => $insumo_id,
                    'cantidad' => $cantidadNueva,
                ]);
            }

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Despacho y stock actualizados correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error en la actualización: ' . $e->getMessage());
        }
    }
    public function destroy($id)
    {
        Gate::authorize('eliminar-despacho');
        /*if (Gate::denies('eliminar-despacho')) {
            return redirect()->back()->with('error', 'No tienes permiso.');
        }*/

        // Cargamos el despacho con sus detalles
        $despacho = Despachos::with('detalles')->findOrFail($id);

        try {
            DB::beginTransaction();

            foreach ($despacho->detalles as $detalle) {
                // 1. Devolver el stock al LOCAL DE ORIGEN
                $registroOrigen = InsumosC::where('id_local', $despacho->id_local_origen)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->first();
                
                if ($registroOrigen) {
                    $registroOrigen->increment('cantidad', $detalle->cantidad);
                }

                // 2. Restar el stock del LOCAL DE DESTINO (porque nunca debió llegar)
                $registroDestino = InsumosC::where('id_local', $despacho->id_local_destino)
                    ->where('id_insumo', $detalle->id_insumo)
                    ->first();

                if ($registroDestino) {
                    // Si por alguna razón el destino ya no tiene stock suficiente para restar, 
                    // podrías decidir si dejarlo en negativo o lanzar una excepción.
                    $registroDestino->decrement('cantidad', $detalle->cantidad);
                }
            }

            // 3. Eliminar detalles y cabecera
            $despacho->detalles()->delete(); // Asegúrate que la relación en el modelo se llame 'detalles'
            $despacho->delete();

            DB::commit();
            return redirect()->route('despacho.index')->with('success', 'Despacho eliminado. El stock ha sido revertido (Sumado en origen y restado en destino).');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
    
}
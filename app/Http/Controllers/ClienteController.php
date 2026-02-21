<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
class ClienteController extends Controller
{
    /**
     * Muestra la lista de clientes.
     */
    public function index()
    {
        Gate::authorize('gestionar-clientes');

        $clientes = Cliente::with('local')->orderBy('nombre', 'asc')->get();
        return view('clientes.index', compact('clientes'));
    }

    /**
     * Formulario para crear un nuevo cliente.
     */
    public function create()
    {
        Gate::authorize('gestionar-clientes');

        $locales = Local::all();
        return view('clientes.create', compact('locales'));
    }

    /**
     * Guarda el cliente en la base de datos.
     */
    public function store(Request $request)
    {
        Gate::authorize('gestionar-clientes');

        $request->validate([
            'identificacion' => 'required|string|unique:clientes,identificacion',
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'required|string',
            'id_local'       => 'required|exists:local,id',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);

        Cliente::create($request->all());

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente registrado exitosamente.');
    }

    /**
     * Registro rápido vía AJAX para el módulo de ventas.
     */
    public function storeRapido(Request $request)
    {
        Gate::authorize('gestionar-clientes');

        $validator = \Validator::make($request->all(), [
            'identificacion' => 'required|string|unique:clientes,identificacion',
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cliente = Cliente::create([
            'identificacion' => $request->identificacion,
            'nombre'         => $request->nombre,
            'telefono'       => $request->telefono,
            'id_local'       => auth()->user()->localActual()->id, // Se vincula al local del vendedor
            'limite_credito' => 0 // Por defecto 0 en registro rápido
        ]);

        return response()->json(['success' => true, 'cliente' => $cliente]);
    }

    /**
     * Ver perfil del cliente y sus deudas.
     */
    public function show($id)
    {
        Gate::authorize('gestionar-clientes');

        $cliente = Cliente::with('local')->findOrFail($id);
        // Aquí luego cargaremos la relación con créditos: $cliente->load('creditos');
        return view('clientes.show', compact('cliente'));
    }

    /**
     * Formulario de edición.
     */
    public function edit($id)
    {
        Gate::authorize('gestionar-clientes');

        $cliente = Cliente::findOrFail($id);
        $locales = Local::all();
        return view('clientes.edit', compact('cliente', 'locales'));
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('gestionar-clientes');

        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'identificacion' => 'required|string|unique:clientes,identificacion,' . $id,
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'required|string',
            'id_local'       => 'required|exists:local,id',
            'limite_credito' => 'required|numeric|min:0',
        ]);

        $cliente->update($request->all());

        return redirect()->route('clientes.index')
            ->with('success', 'Datos del cliente actualizados.');
    }

    /**
     * Elimina (o desactiva) un cliente.
     */
    public function destroy($id)
    {
        Gate::authorize('eliminar-clientes');

        $cliente = Cliente::findOrFail($id);
        
        // Sugerencia: En lugar de borrar, podrías desactivarlo si tiene deudas
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('info', 'Cliente eliminado del sistema.');
    }

    /*public function storeAjax(Request $request) {
        try {
            $request->validate([
                'identificacion' => 'required|unique:clientes,identificacion',
                'nombre'         => 'required|string|max:255',
                'id_local'       => 'required|exists:local,id' // Validación del local
            ]);

            $cliente = Cliente::create([
                'identificacion' => $request->identificacion,
                'nombre'         => $request->nombre,
                'telefono'       => $request->telefono,
                'limite_credito' => $request->limite_credito ?? 0,
                'id_local'       => $request->id_local, 
                'estado'         => 1
            ]);

            return response()->json([
                'success' => true,
                'cliente' => $cliente
            ],200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e.getMessage()], 422);
        }
    }*/

    public function storeAjax(Request $request)
{
    

    $validator = \Validator::make($request->all(), [
        'identificacion' => 'required|unique:clientes,identificacion',
        'nombre'         => 'required|string|max:255',
        'id_local'       => 'required|exists:local,id',
    ]);

    if ($validator->fails()) {
        

        return response()->json([
            'success' => false,
            'message' => 'El cliente ya existe o los datos son inválidos.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    

    try {
        $cliente = DB::transaction(function () use ($request) {
         

            return Cliente::create([
                'identificacion' => trim($request->identificacion),
                'nombre'         => trim($request->nombre),
                'telefono'       => $request->telefono,
                'limite_credito' => $request->limite_credito ?? 0,
                'id_local'       => $request->id_local,
                'activo'         => true,
            ]);
        });

        return response()->json([
            'success' => true,
            'cliente' => $cliente,
        ], 200);

    } catch (\Exception $e) {
        

        return response()->json([
            'success' => false,
            'message' => 'Error al guardar: ' . $e->getMessage(),
        ], 500);
    }
}

}
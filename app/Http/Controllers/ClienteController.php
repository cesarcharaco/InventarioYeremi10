<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Local;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\StockBajoNotification;

class ClienteController extends Controller
{
    public function __construct()
    {
        // Esto aplica auth a todo, excepto al registro y al store
        $this->middleware('auth')->except(['create', 'store']);
    }
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
        // 1. Si está autenticado, verificamos permisos.
        // Si no está autenticado, simplemente saltamos esta parte.
        if (auth()->check()) {
            Gate::authorize('gestionar-clientes');
            $locales = Local::where('tipo','LOCAL')->get();
        } else {
            $locales = Local::where('tipo', 'OFICINA')->get();
        }

        return view('clientes.create', compact('locales'));
    }

    /**
     * Guarda el cliente en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Reglas base para todos los clientes
        $rules = [
            'identificacion' => 'required|string|unique:clientes,identificacion',
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'required|string',
            'id_local'       => 'required|exists:local,id',
            'limite_credito' => 'nullable|numeric|min:0',
            'direccion'      => 'nullable|string',
        ];

        // 2. Si NO está autenticado (Registro de Mayorista), añadimos las reglas de usuario
        if (!auth()->check()) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $datos = $request->validate($rules);

        try {
            DB::beginTransaction();

            // 3. Lógica según tipo de registro
            if (auth()->check()) {
                // REGISTRO DETAL (Administrativo)
                Gate::authorize('gestionar-clientes');
                $datos['activo'] = $request->input('activo', 'activo');
                
                Cliente::create($datos);
                $mensaje = 'Cliente registrado exitosamente.';
                $ruta = 'clientes.index';
            } else {
                // REGISTRO MAYORISTA (Público)
                $datos['activo'] = 'pendiente';
                
                // Creamos usuario
                $user = User::create([
                    'name'     => $datos['nombre'],
                    'cedula'   => $datos['identificacion'],
                    'telefono' => $datos['telefono'],
                    'email'    => $datos['email'],
                    'password' => Hash::make($datos['password']),
                    'role'     => User::ROLE_CMAYORISTA,
                    'activo'   => false, // Inactivo por seguridad hasta que lo activen
                ]);
                
                // Vinculamos local
                $user->locales()->attach($datos['id_local'], ['status' => 'activo']);
                
                // Creamos cliente
                Cliente::create($datos);
                // --- NOTIFICAR A TODOS LOS ADMINISTRATIVOS ---
                $gerentes = User::whereIn('role', ['admin', 'gerente'])->get();

                $detalles = [
                    'titulo'  => '🆕 Nuevo Mayorista Pendiente',
                    'mensaje' => "El cliente {$datos['nombre']} se ha registrado y espera activación.",
                    'url'     => route('clientes.index'), 
                    'icono'   => 'fas fa-user-clock text-info'
                ];

                foreach ($gerentes as $gerente) {
                    $gerente->notify(new StockBajoNotification($detalles));
                }
                // ----------------------------------------------
                auth()->logout(); // cerrando inicio de sesion automatico
                $mensaje = 'Tu registro ha sido enviado. Un administrador lo revisará pronto.';
                $ruta = 'login';
            }

            DB::commit();
            return redirect()->route($ruta)->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar: ' . $e->getMessage()])->withInput();
        }
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
                    'activo'         => 'activo',
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
    public function listaActivar()
    {
        Gate::authorize('gestionar-clientes');
        $clientes = Cliente::where('activo', 'pendiente')->get();
        return view('clientes.lista_activar', compact('clientes'));
    }

    // Para activar el cliente
    public function activar($id)
    {
        Gate::authorize('gestionar-clientes');
        
        // 1. Buscamos el cliente
        $cliente = Cliente::findOrFail($id);

        try {
            DB::beginTransaction();

            // 2. Activamos el modelo Cliente
            $cliente->update(['activo' => 'activo']);

            // 3. Activamos el modelo User vinculado
            // Asumiendo que 'cedula' en User es igual a 'identificacion' en Cliente
            $user = User::where('cedula', $cliente->identificacion)->first();
            
            if ($user) {
                $user->update(['activo' => true]);
            }

            DB::commit();
            return redirect()->route('clientes.pendientes')->with('success', 'Cliente y usuario activados correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al activar: ' . $e->getMessage()]);
        }
    }
}
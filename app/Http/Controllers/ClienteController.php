<?php

  namespace App\Http\Controllers;

use App\Models\Local;
use App\Models\Cliente;
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
        if (auth()->check()) {
            Gate::authorize('gestionar-clientes');
            $locales = Local::where('tipo', 'LOCAL')->get();
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
        $rules = [
            'identificacion' => 'required|string|unique:clientes,identificacion',
            'nombre'         => 'required|string|max:255',
            'alias'          => 'nullable|string|max:255',
            'telefono'       => 'required|string',
            'id_local'       => 'required|exists:local,id',
            'limite_credito' => 'nullable|numeric|min:0',
            'direccion'      => 'nullable|string',
        ];

        if (!auth()->check()) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $datos = $request->validate($rules);

        try {
            DB::beginTransaction();

            if (auth()->check()) {
                Gate::authorize('gestionar-clientes');
                $datos['activo'] = $request->input('activo', 'activo');
                
                Cliente::create($datos);
                $mensaje = 'Cliente registrado exitosamente.';
                $ruta = 'clientes.index';
            } else {
                $datos['activo'] = 'pendiente';
                
                $user = User::create([
                    'name'     => $datos['nombre'],
                    'alias'    => $datos['alias'],
                    'cedula'   => $datos['identificacion'],
                    'telefono' => $datos['telefono'],
                    'email'    => $datos['email'],
                    'password' => Hash::make($datos['password']),
                    'role'     => User::ROLE_CMAYORISTA,
                    'activo'   => false,
                ]);
                
                $user->locales()->attach($datos['id_local'], ['status' => 'activo']);
                Cliente::create($datos);

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

                auth()->logout();
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
            'alias'          => 'nullable|string|max:255',
            'telefono'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cliente = Cliente::create([
            'identificacion' => $request->identificacion,
            'nombre'         => $request->nombre,
            'alias'          => $request->alias,
            'telefono'       => $request->telefono,
            'id_local'       => auth()->user()->localActual()->id,
            'limite_credito' => 0
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
            'alias'          => 'nullable|string|max:255',
            'telefono'       => 'nullable|string',
            'id_local'       => 'required|exists:local,id',
            'limite_credito' => 'required|numeric|min:0',
        ]);

        $cliente->update($request->all());

        return redirect()->route('clientes.index')
            ->with('success', 'Datos del cliente actualizados.');
    }

    /**
     * Elimina cliente limpiando créditos, ventas y regresando inventario a existencias.
     */
    public function destroy($id)
    {
        Gate::authorize('eliminar-clientes');

        try {
            DB::transaction(function () use ($id) {
                $cliente = Cliente::with(['creditos.venta.detalles.insumo.existencias', 'creditos.abonos', 'creditos.intereses'])->findOrFail($id);

                // 1. Limpiar todos los créditos y ventas asociadas al cliente
                foreach ($cliente->creditos as $credito) {
                    $venta = $credito->venta;

                    if ($venta) {
                        if ($venta->detalles->isNotEmpty()) {
                            foreach ($venta->detalles as $detalle) {
                                if ($detalle->insumo) {
                                    $existencia = $detalle->insumo->existencias()->first();
                                    if ($existencia) {
                                        $existencia->increment('cantidad', $detalle->cantidad);
                                    }
                                }
                            }
                            $venta->detalles()->delete();
                        }
                        $venta->delete();
                    }

                    $credito->abonos()->delete();
                    $credito->intereses()->delete();
                    $credito->delete();
                }

                // 2. Limpiar ventas directas asociadas si la relación existe
                if (method_exists($cliente, 'ventas')) {
                    foreach ($cliente->ventas as $ventaDirecta) {
                        if ($ventaDirecta->detalles->isNotEmpty()) {
                            foreach ($ventaDirecta->detalles as $detalle) {
                                if ($detalle->insumo) {
                                    $existencia = $detalle->insumo->existencias()->first();
                                    if ($existencia) {
                                        $existencia->increment('cantidad', $detalle->cantidad);
                                    }
                                }
                            }
                            $ventaDirecta->detalles()->delete();
                        }
                        $ventaDirecta->delete();
                    }
                }

                // 3. Eliminar el registro del cliente
                $cliente->delete();
            });

            return redirect()->route('clientes.index')
                ->with('info', 'Cliente, sus créditos, ventas asociadas e historial fueron eliminados correctamente. El inventario fue actualizado.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ocurrió un error al intentar eliminar el cliente: ' . $e->getMessage());
        }
    }

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
                    'alias'          => trim($request->alias),
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

    public function activar($id)
    {
        Gate::authorize('gestionar-clientes');
        
        $cliente = Cliente::findOrFail($id);

        try {
            DB::beginTransaction();

            $cliente->update(['activo' => 'activo']);

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
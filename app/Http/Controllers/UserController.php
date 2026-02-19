<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('gestionar-usuarios');

        $users = User::with('local')->get();
        return view('usuarios.index', compact('users'));
    }

    public function create()
    {
        Gate::authorize('gestionar-usuarios');

        $locales = Local::where('estado', 1)->get();
        return view('usuarios.create', compact('locales'));
    }

    public function store(Request $request)
    {
        Gate::authorize('gestionar-usuarios');

        $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'required|unique:users,cedula',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,almacenista,encargado,vendedor',
            'id_local' => 'required|exists:local,id'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'cedula' => $request->cedula,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'activo' => true,
            ]);

            // Vincular al local inicial como "Activo" en la pivot
            $user->local()->attach($request->id_local, ['status' => 'Activo']);

            DB::commit();
            return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear usuario: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        Gate::authorize('gestionar-usuarios');

        $user = User::findOrFail($id);
        $locales = Local::where('estado', 1)->get();
        
        // Obtenemos el local que tiene marcado como 'Activo' en la tabla pivot
        $localActual = $user->localActual(); 
        $localActualId = $localActual ? $localActual->id : null;

        // Pasamos la variable localActualId a la vista
        return view('usuarios.edit', compact('user', 'locales', 'localActualId'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('gestionar-usuarios');

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required',
            'id_local' => 'required'
        ]);

        $user->name = $request->name;
        $user->cedula = $request->cedula;
        $user->telefono = $request->telefono;
        $user->email = $request->email;
        $user->role = $request->role;
        
        // Manejo del checkbox 'activo'
        $user->activo = $request->has('activo'); 
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Actualizamos la pivot
        $user->local()->sync([$request->id_local => ['status' => 'Activo']]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        Gate::authorize('gestionar-usuarios');

        $user = User::findOrFail($id);
        
        // 1. Evitar que se desactive a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        // 2. En lugar de $user->delete(), hacemos un borrado lógico
        $user->activo = false;
        $user->save();

        return redirect()->route('usuarios.index')
            ->with('success', "El usuario {$user->name} ha sido desactivado y ya no tiene acceso al sistema.");
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        // Buscamos su local asignado
        $localActual = $user->localActual(); 
        
        return view('usuarios.show', compact('user', 'localActual'));
    }   
}
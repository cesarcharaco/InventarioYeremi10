<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PerfilController extends Controller
{
    // Solo usuarios autenticados entran aquí
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        $user = auth()->user();
        // Reutilizamos la lógica del local actual que ya conoces
        $localActual = $user->localActual();
        $localActualId = $localActual ? $localActual->id : null;

        return view('usuarios.perfil', compact('user', 'localActualId'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'foto'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        // Gestión de la Foto
        if ($request->hasFile('foto')) {
            // 1. Eliminar foto anterior si existe para no llenar el servidor de basura
            if ($user->foto && file_exists(public_path('fotosperfil/' . $user->foto))) {
                unlink(public_path('fotosperfil/' . $user->foto));
            }

            // 2. Guardar la nueva
            $file = $request->file('foto');
            $nombreFoto = time() . '_' . $user->cedula . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('fotosperfil'), $nombreFoto);
            
            $user->foto = $nombreFoto;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        // ... resto de tus campos ...
        
        $user->save();

        return back()->with('success', 'Perfil actualizado con éxito.');
    }
}
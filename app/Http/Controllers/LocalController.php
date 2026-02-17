<?php

namespace App\Http\Controllers;

use App\Models\Local;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
class LocalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== User::ROLE_SUPERADMIN) {
                return redirect('home')->with('error', 'No tienes permisos de administrador.');
            }
            return $next($request);
        });
    }
    
    public function index()
    {
        // Traemos todos los locales para la vista
        $local = Local::all();
        return view('local.index', compact('local'));
    }

    public function create()
    {
        return view('local.create');
    }

    public function store(Request $request)
    {
        // 1. Validamos que el nombre sea único
        $buscar = Local::where('nombre', $request->nombre)->first();

        if ($buscar) {
             return redirect()->route('local.index')->with('warning', '¡El nombre del local ya existe!');
        }

        // 2. Creamos el nuevo registro incluyendo el TIPO
        $local = new Local();
        $local->nombre = $request->nombre;
        $local->tipo = $request->tipo; // ¡IMPORTANTE! Campo nuevo: LOCAL o DEPOSITO
        $local->estado = 1; // Por defecto activo
        $local->save();

        return redirect()->route('local.index')->with('success', '¡Local registrado exitosamente!');
    }

    public function edit($id)
    {
        $local = Local::findOrFail($id);
        return view('local.edit', compact('local'));
    }

    public function update(Request $request, $id)
    {
        $local = Local::findOrFail($id);
        //dd($request->all());
        // Evitar duplicados si se cambia el nombre a uno que ya existe (excepto el propio)
        $duplicado = Local::where('nombre', $request->nombre)->where('id', '!=', $id)->first();
        if ($duplicado) {
            return redirect()->back()->with('warning', 'Ese nombre ya está en uso por otro local.');
        }

        $local->nombre = $request->nombre;
        $local->tipo = $request->tipo;
        $local->estado=$request->estado;
        $local->save();

        return redirect()->route('local.index')->with('success', '¡Local actualizado correctamente!');
    }

    // Función para activar/desactivar locales sin eliminarlos
    public function cambiar_estado(Request $request)
    {
        $local = Local::findOrFail($request->id_local);
        
        // Validamos que lo que venga sea 'Activo' o 'Inactivo'
        // Si el select envía "Activo", guardamos "Activo"
        $local->estado = $request->estado; 
        $local->save();

        return redirect()->back()->with('success', 'Estado actualizado a ' . $request->estado);
    }

    public function destroy($id)
    {
        $local = Local::findOrFail($id);
        
        // Nota: En un sistema de inventario, borrar un local con stock 
        // puede dar problemas. Aquí lo borramos, pero podrías añadir una validación.
        $local->delete();

        return redirect()->route('local.index')->with('danger', 'Local eliminado del sistema.');
    }
}
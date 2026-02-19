<?php
namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoriaController extends Controller
{
    public function index()
    {
        Gate::authorize('crear-configuracion');
        $categorias = Categoria::withCount('insumos')->paginate(15);
        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        Gate::authorize('crear-configuracion');
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('crear-configuracion');
        $request->validate([
            'categoria' => 'required|string|max:100|unique:categorias'
        ]);

        Categoria::create($request->only('categoria'));

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada correctamente');
    }

    public function edit(Categoria $categoria)
    {
        Gate::authorize('crear-configuracion');
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        Gate::authorize('crear-configuracion');
        $request->validate([
            'categoria' => 'required|string|max:100|unique:categorias,categoria,' . $categoria->id
        ]);

        $categoria->update($request->only('categoria'));

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente');
    }

    public function destroy(Categoria $categoria)
    {
        Gate::authorize('crear-configuracion');
        $id = $categoria->id;
        $nombre = $categoria->categoria;
        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría "' . $nombre . '" eliminada correctamente');
    }
}

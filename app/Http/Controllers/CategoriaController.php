<?php
namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::withCount('insumos')->paginate(15);
        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string|max:100|unique:categorias'
        ]);

        Categoria::create($request->only('categoria'));

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada correctamente');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'categoria' => 'required|string|max:100|unique:categorias,categoria,' . $categoria->id
        ]);

        $categoria->update($request->only('categoria'));

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente');
    }

    public function destroy(Categoria $categoria)
    {
        $id = $categoria->id;
        $nombre = $categoria->categoria;
        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría "' . $nombre . '" eliminada correctamente');
    }
}

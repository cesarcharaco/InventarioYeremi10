<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProveedorController extends Controller
{
    public function index()
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado. No tiene permisos para ver la lista de proveedores.');
        }

        $proveedores = Proveedor::orderBy('nombre', 'asc')->get();
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado. No tiene permisos para registrar proveedores.');
        }

        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'rif' => 'required|string|max:20|unique:proveedores,rif',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
        ]);

        Proveedor::create($request->all());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    public function edit($id)
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $proveedor = Proveedor::findOrFail($id);
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, $id)
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $proveedor = Proveedor::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'rif' => 'required|string|max:20|unique:proveedores,rif,' . $id,
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
        ]);

        $proveedor->update($request->all());

        return redirect()->route('proveedores.index')->with('success', 'Datos del proveedor actualizados.');
    }

    public function destroy($id)
    {
        if (Gate::denies('gestionar-proveedores')) {
            return redirect()->back()->with('error', 'Acceso denegado. No tiene permisos para eliminar registros.');
        }

        $proveedor = Proveedor::findOrFail($id);

        // Verificamos si tiene entradas para no romper la integridad referencial
        if ($proveedor->entradas()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar el proveedor: existen órdenes de entrega vinculadas a este registro.');
        }

        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
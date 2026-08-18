<?php

namespace App\Http\Controllers;

use App\Models\Correlativo;
use Illuminate\Http\Request;

class CorrelativoController extends Controller
{
    public function index()
    {
        $correlativos = Correlativo::with('venta')->orderBy('id', 'desc')->paginate(20);
        return view('correlativos.index', compact('correlativos'));
    }

    public function create()
    {
        return view('correlativos.create');
    }

    public function store(Request $request)
    {
        // Si decide ingresar en lote / rango
        if ($request->has('modo_lote') && $request->modo_lote == '1') {
            $request->validate([
                'prefijo_control' => 'required|string', // Ej: 00-
                'desde'           => 'required|integer|min:1',
                'hasta'           => 'required|integer|gte:desde',
            ]);

            $creados = 0;
            for ($i = $request->desde; $i <= $request->hasta; $i++) {
                $numFactura = str_pad($i, 6, '0', STR_PAD_LEFT);
                $numControl = $request->prefijo_control . $numFactura;

                // Evitar duplicados
                if (!Correlativo::where('numero_factura', $numFactura)->exists()) {
                    Correlativo::create([
                        'numero_factura' => $numFactura,
                        'numero_control' => $numControl,
                        'estado'         => 'disponible'
                    ]);
                    $creados++;
                }
            }

            return redirect()->route('correlativos.index')
                ->with('success', "Se cargaron {$creados} correlativos exitosamente.");
        }

        // Registro individual
        $request->validate([
            'numero_factura' => 'required|string|unique:correlativos,numero_factura',
            'numero_control' => 'required|string',
        ]);

        Correlativo::create([
            'numero_factura' => str_pad($request->numero_factura, 6, '0', STR_PAD_LEFT),
            'numero_control' => $request->numero_control,
            'estado'         => 'disponible'
        ]);

        return redirect()->route('correlativos.index')
            ->with('success', 'Correlativo fiscal registrado correctamente.');
    }

    public function edit(Correlativo $correlativo)
    {
        return view('correlativos.edit', compact('correlativo'));
    }

    public function update(Request $request, Correlativo $correlativo)
    {
        $request->validate([
            'numero_factura' => 'required|string|unique:correlativos,numero_factura,' . $correlativo->id,
            'numero_control' => 'required|string',
            'estado'         => 'required|in:disponible,usado,anulado'
        ]);

        $correlativo->update($request->all());

        return redirect()->route('correlativos.index')
            ->with('success', 'Correlativo actualizado correctamente.');
    }

    public function destroy(Correlativo $correlativo)
    {
        if ($correlativo->estado == 'usado') {
            return redirect()->route('correlativos.index')
                ->with('error', 'No se puede eliminar un correlativo asignado a una venta.');
        }

        $correlativo->delete();
        return redirect()->route('correlativos.index')
            ->with('success', 'Correlativo eliminado con éxito.');
    }
}
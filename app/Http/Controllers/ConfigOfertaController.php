<?php

namespace App\Http\Controllers;

use App\Models\ConfigOfertas;
use App\Models\Caja;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConfigOfertaController extends Controller
{
    public function index()
    {
        // Validación de seguridad mediante el Gate
        Gate::authorize('gestionar-ofertas');

        $locales = Local::where('tipo','LOCAL')->get();
        
        // Obtenemos el historial de ofertas
        $ofertas = ConfigOfertas::with(['local', 'administrador'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        // Apuntamos a la carpeta config_ofertas dentro de views
        return view('config_ofertas.index', compact('locales', 'ofertas'));
    }

    public function store(Request $request)
    {
        Gate::authorize('gestionar-ofertas');

        $request->validate([
            'motivo' => 'required|string|max:255',
            'criterio_fin' => 'required|in:manual,cierre_caja,fin_turno',
            'id_local' => 'required|exists:local,id'
        ]);

        // Desactivar ofertas previas en el local antes de crear la nueva
        ConfigOfertas::where('id_local', $request->id_local)
                    ->where('estado', true)
                    ->update(['estado' => false]);

        // Buscamos si hay una caja abierta para vincular la oferta (si el criterio es cierre_caja)
        $cajaActual = Caja::where('id_local', $request->id_local)
                          ->where('estado', 'abierta')
                          ->first();

        ConfigOfertas::create([
            'id_local' => $request->id_local,
            'motivo' => $request->motivo,
            'criterio_fin' => $request->criterio_fin,
            'estado' => true,
            'id_caja_origen' => $cajaActual ? $cajaActual->id : null,
            'id_usuario_admin' => auth()->id(),
        ]);

        return redirect()->route('config-ofertas.index')->with('success', 'Oferta activada correctamente.');
    }

    public function desactivar($id)
    {
        Gate::authorize('gestionar-ofertas');

        $oferta = ConfigOfertas::findOrFail($id);
        $oferta->update(['estado' => false]);

        return redirect()->route('config-ofertas.index')->with('info', 'Oferta finalizada manualmente.');
    }
}
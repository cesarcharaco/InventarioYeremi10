<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Caja; 
use Illuminate\Support\Facades\Auth;

class CheckCajaAbierta
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // 1. Obtenemos el local actual del usuario (Admin, Encargado o Vendedor)
        $local = $user->localActual();

        if (!$local) {
            return redirect()->route('home')
                ->with('error', 'No tienes un local activo asignado.');
        }

        // 2. CAMBIO CLAVE: Buscamos si el LOCAL tiene una caja abierta
        // Ya no filtramos por id_user, sino por id_local
        $caja = Caja::where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            // Si el local no tiene caja abierta, redirigimos
            return redirect()->route('cajas.create')
                ->with('error', 'No hay ninguna caja abierta en este local. Debe iniciar jornada.');
        }

        // 3. Guardamos los datos en la sesión para que el VentaController los use fácilmente
        session([
            'id_caja_activa' => $caja->id,
            'id_local_activo' => $local->id
        ]);

        return $next($request);
    }
}
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
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Buscamos si el usuario tiene una caja abierta
        $caja = Caja::where('id_user', Auth::id())
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            // Si no tiene caja abierta, lo mandamos a la vista de apertura con un aviso
            return redirect()->route('cajas.create')
                ->with('error', 'Debes abrir una jornada de caja para poder facturar.');
        }

        // Si tiene caja, guardamos el ID en la sesión para usarlo en las ventas
        session(['id_caja_activa' => $caja->id]);

        return $next($request);
    }
}

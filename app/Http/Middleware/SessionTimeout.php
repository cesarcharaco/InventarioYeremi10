<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class SessionTimeout
{
    public function handle($request, Closure $next)
    {
        /*// Si no está logueado, seguir flujo normal
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Si no hay last_seen_at, lo inicializamos
        if (is_null($user->last_seen_at)) {
            $user->last_seen_at = Carbon::now();
            $user->save();

            return $next($request);
        }

        $now       = Carbon::now();
        $last_seen = Carbon::parse($user->last_seen_at);
        $minutes   = $now->diffInMinutes($last_seen);

        // Si se pasó del tiempo configurado
        if ($minutes > config('session.lifetime')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warning', 'Tu sesión se cerró por inactividad. Inicia sesión de nuevo.');
        }

        // Actualizamos última actividad
        $user->last_seen_at = $now;
        $user->save();

        return $next($request);*/
        // Verificar inactividad
    if (!$request->session()->has('lastActivity')) {
        $request->session()->put('lastActivity', now());
    }

    $lastActivity = $request->session()->get('lastActivity');
    $timeout = config('session.lifetime') * 60; // En segundos

    if (now()->diffInSeconds($lastActivity) > $timeout) {
        // ❌ SOLO logout, NO invalides sesión completa
        Auth::logout();
        
        // ✅ NO uses invalidate/regenerate aquí
        // $request->session()->invalidate();
        // $request->session()->regenerate();
        
        return redirect()->route('login')
            ->with('status', 'Sesión expirada por inactividad');
    }

    // Actualizar actividad
    $request->session()->put('lastActivity', now());
    
    return $next($request);
        
    }
}

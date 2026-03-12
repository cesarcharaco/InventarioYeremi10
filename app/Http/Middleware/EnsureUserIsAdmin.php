<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Usamos tu método del modelo User que ya revisamos
        if (!auth()->check() || !auth()->user()->esAdmin()) {
            return redirect('/')->with('error', 'No tienes permisos de administrador.');
        }

        return $next($request);
    }
}

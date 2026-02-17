<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Intentamos obtener la ruta 'login', si falla, mandamos al string '/login' directamente
            try {
                return route('login');
            } catch (\Exception $e) {
                return '/login'; 
            }
        }
        //return $request->expectsJson() ? null : route('login');
    }
}

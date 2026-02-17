<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
     /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        // Agrega este bloque para capturar el error 419
        $this->renderable(function (\Throwable $e, $request) {
        // Si la sesión expiró (419) o si se intenta acceder a un objeto nulo estando deslogueado
        if ($e instanceof \Illuminate\Session\TokenMismatchException || 
           ($e instanceof \Error && str_contains($e->getMessage(), 'Attempt to read property "name" on null'))) {
            
            return redirect()->route('login')
                ->with('message', 'Su sesión ha expirado por inactividad. Por favor, ingrese de nuevo.');
        }
    });
    }

    public function render($request, Throwable $exception)
    {
        // ✅ CAPTURAR ERROR DE BASE DE DATOS
        if ($exception instanceof QueryException) {
            $message = $exception->getMessage();
            
            // Detectar errores específicos de MySQL/WAMP no disponible
            if (str_contains($message, 'No se puede establecer una conexión') || 
                str_contains($message, 'Connection refused') ||
                str_contains($message, 'SQLSTATE[HY000] [2002]') ||
                str_contains($message, 'Access denied for user')) {
                
                // Si es AJAX/JSON
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Servidor de base de datos no disponible',
                        'message' => 'Por favor inicia WAMP (Apache + MySQL)',
                        'solution' => '1. Clic WAMP → MySQL → Start<br>2. Espera bandera VERDE'
                    ], 503);
                }
                
                // Redirigir a login con mensaje claro
                return redirect()->route('login')
                    ->with('db_error', '⚠️ Servidor no disponible. <strong>Inicia WAMP (Apache + MySQL)</strong> e inténtalo de nuevo.');
            }
        }

        return parent::render($request, $exception);
    }
}

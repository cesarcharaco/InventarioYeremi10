<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    /*public function login(Request $request)
    {
        \Log::info('Login attempt', [
                    'email' => $request->email,
                    'session_id' => session()->getId(),
                    'session_exists' => session()->exists('lastActivity')
                ]);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate(); // ✅ Regenera DESPUÉS del login
            
            return redirect()->intended('home');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden.',
        ]);
    }*/
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->activo) {
                Auth::logout();
                
                // BUSCAMOS SI EXISTE EN LA TABLA CLIENTES
                // Suponiendo que la relación en el modelo User es public function cliente()
                $esCliente = \App\Models\Cliente::where('identificacion', $user->cedula)->first();

                if ($esCliente) {
                    // CASO MAYORISTA: Si existe en clientes y está inactivo, es por aprobación
                    $mensaje = 'Tu registro como mayorista está siendo evaluado. Te avisaremos por correo al ser aprobado.';
                } else {
                    // CASO STAFF/ADMIN: Si no es cliente, es un usuario interno bloqueado
                    $mensaje = 'Tu acceso al sistema ha sido revocado. Contacta al administrador técnico.';
                }

                return back()->withErrors(['email' => $mensaje]);
            }

            $request->session()->regenerate();
            return redirect()->intended('home');
        }

        return back()->withErrors(['email' => 'Las credenciales no coinciden.']);
    }
    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }
}

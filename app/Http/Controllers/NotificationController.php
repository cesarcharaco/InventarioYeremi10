<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Marcar una notificación y redirigir al destino
     */
    public function read($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        
        // La marcamos como leída
        $notification->markAsRead();

        // Extraemos la URL que guardamos en el array 'data'
        $url = $notification->data['url'] ?? route('home');

        // Redirigimos al usuario a la sección correspondiente (ej: Inventario o Créditos)
        return redirect($url);
    }

    /**
     * Marcar todas las notificaciones del usuario actual como leídas
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }

    /**
     * Listado histórico de notificaciones
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function count()
    {
        if (!auth()->check()) {
            return response()->json(['count' => 0]);
        }
        
        return response()->json([
            'count' => auth()->user()->unreadNotifications->count()
        ]);
    }
}
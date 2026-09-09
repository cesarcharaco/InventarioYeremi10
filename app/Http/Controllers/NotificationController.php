<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
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

        // Redirigir siempre al index de notificaciones para que el usuario las vea ahí
        return redirect()->route('notifications.index');
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
        $user = auth()->user();
        $query = $user->notifications();

        // Filtro adicional opcional por rol si deseas bloquear visualmente ciertas alertas históricas
        if ($user->role === User::ROLE_VENDEDOR) {
            // Los vendedores solo ven notificaciones comerciales o de ventas, excluyendo auditoría interna
            $query->where('data->tipo', '!=', 'auditoria');
        }

        $notifications = $query->paginate(15);
        
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
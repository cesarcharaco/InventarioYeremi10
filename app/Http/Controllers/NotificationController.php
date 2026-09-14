<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
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
       
       return view('notifications.index');
   }

   
  public function getData(Request $request)
    {
        Gate::authorize('ver-logistica'); 
        $user = auth()->user();

        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->select([
                'id',
                'data',
                'read_at',
                'created_at as fecha'
            ]);

        // --- APLICACIÓN DE FILTROS ---
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'no_leidas') {
                $query->whereNull('read_at');
            } elseif ($request->estado === 'leidas') {
                $query->whereNotNull('read_at');
            }
        }
        // -----------------------------

        return DataTables::of($query)
            ->editColumn('estado', function($row) {
                if ($row->read_at) {
                    return '<div class="text-center"><i class="fa fa-envelope-open text-muted" title="Leída"></i></div>';
                } else {
                    return '<div class="text-center"><i class="fa fa-envelope text-primary" title="Nueva"></i></div>';
                }
            })
            ->editColumn('titulo', function($row) {
                $data = json_decode($row->data, true);
                $icono = $data['icono'] ?? 'fa fa-info-circle';
                $titulo = $data['titulo'] ?? ($data['title'] ?? 'Alerta del Sistema');
                return '<i class="' . $icono . ' mr-2"></i><strong>' . e($titulo) . '</strong>';
            })
            ->editColumn('mensaje', function($row) {
                $data = json_decode($row->data, true);
                $mensaje = $data['mensaje'] ?? ($data['message'] ?? 'Sin descripción disponible');
                return e($mensaje);
            })
            ->editColumn('fecha', function($row) {
                return '<span class="text-muted small"><i class="fa fa-clock-o"></i> ' . \Carbon\Carbon::parse($row->fecha)->diffForHumans() . '</span>';
            })
            ->addColumn('acciones', function($row) {
                $url = route('notifications.read', $row->id);
                return '<a href="' . $url . '" class="btn btn-primary btn-sm btn-block"><i class="fa fa-eye"></i> Ver</a>';
            })
            // Redirigimos la búsqueda global para que busque dentro del campo JSON 'data'
            ->filterColumn('titulo', function($query, $keyword) {
                $query->where('data', 'like', "%{$keyword}%");
            })
            ->filterColumn('mensaje', function($query, $keyword) {
                $query->where('data', 'like', "%{$keyword}%");
            })
            ->rawColumns(['estado', 'titulo', 'mensaje', 'fecha', 'acciones'])
            ->make(true);
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
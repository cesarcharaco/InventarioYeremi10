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
     * Marcar una notificación como leída y redirigir al evento correspondiente.
     */
    public function read($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = is_array($notification->data)
            ? $notification->data
            : (json_decode($notification->data, true) ?? []);

        $urlCruda = $data['url'] ?? null;
        $destino = $this->resolverDestinoSeguro(is_string($urlCruda) ? $urlCruda : null);

        return redirect()->to($destino);
    }

    /**
     * Valida que la URL pertenezca a la aplicación actual o sea una ruta relativa interna.
     * Si no es válida o está vacía, regresa al índice de notificaciones.
     */
    private function resolverDestinoSeguro(?string $url): string
    {
        $fallback = route('notifications.index');

        if (!$url) {
            return $fallback;
        }

        $url = trim($url);

        // Bloquear esquemas peligrosos como javascript: o data:
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $url)) {
            $partesUrl = parse_url($url);
            $partesApp = parse_url(config('app.url'));

            $hostUrl = strtolower($partesUrl['host'] ?? '');
            $hostApp = strtolower($partesApp['host'] ?? request()->getHost());

            if ($hostUrl === '' || $hostUrl !== $hostApp) {
                return $fallback;
            }

            $esquema = strtolower($partesUrl['scheme'] ?? '');
            if (!in_array($esquema, ['http', 'https'], true)) {
                return $fallback;
            }

            return $url;
        }

        // Si es ruta relativa interna, asegurar que inicie con una sola barra "/"
        if (str_starts_with($url, '/')) {
            if (str_starts_with($url, '//')) {
                return $fallback;
            }
            return url($url);
        }

        return $fallback;
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
        Gate::authorize('ver-notificaciones'); 
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
                    return '<div class="text-center"><i class="fa fa-envelope text-primary estado-no-leida" title="Nueva"></i></div>';
                }
            })
            ->editColumn('titulo', function($row) {
                $data = is_array($row->data)
                    ? $row->data
                    : (json_decode($row->data, true) ?? []);

                $icono = $data['icono'] ?? 'fa fa-info-circle';
                $titulo = $data['titulo'] ?? ($data['title'] ?? 'Alerta del Sistema');
                $claseTexto = is_null($row->read_at) ? 'font-weight-bold text-dark' : 'text-muted';

                return '<i class="' . e($icono) . ' mr-2"></i><span class="' . $claseTexto . '">' . e($titulo) . '</span>';
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
                $data = is_array($row->data)
                    ? $row->data
                    : (json_decode($row->data, true) ?? []);

                $urlRead = route('notifications.read', $row->id);
                $tieneDestino = !empty($data['url']) && !str_contains($data['url'], '/notifications');
                $esNoLeida = is_null($row->read_at);

                $claseBtn = $esNoLeida ? 'btn-primary' : 'btn-outline-primary';
                $iconoBtn = $tieneDestino ? 'fa fa-external-link-alt' : 'fa fa-eye';
                $textoBtn = $tieneDestino ? 'Ir al evento' : 'Ver';

                return '<a href="' . e($urlRead) . '" class="btn ' . $claseBtn . ' btn-sm text-nowrap">'
                    . '<i class="' . $iconoBtn . ' mr-1"></i> ' . $textoBtn
                    . '</a>';
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
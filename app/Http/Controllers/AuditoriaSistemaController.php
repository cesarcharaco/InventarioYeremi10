<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use App\Models\AuditoriaSistema;

class AuditoriaSistemaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource view.
     */
    public function index()
    {
        Gate::authorize('ver-historial-total');

        return view('auditoria.index');
    }

    /**
     * Process data for Yajra DataTables server-side rendering.
     */
    public function getData(Request $request)
    {
        Gate::authorize('ver-historial-total');

        $query = DB::table('auditoria_sistema')
            ->leftJoin('users', 'auditoria_sistema.id_user', '=', 'users.id')
            ->select([
                'auditoria_sistema.id',
                'auditoria_sistema.accion',
                'auditoria_sistema.tabla_afectada',
                'auditoria_sistema.registro_id',
                'auditoria_sistema.valores_anteriores',
                'auditoria_sistema.valores_nuevos',
                'auditoria_sistema.ejecutado_en',
                'users.name as nombre_usuario'
            ]);

        return DataTables::of($query)
            // --- MAPEADO DE BÚSQUEDA ---
            ->filterColumn('nombre_usuario', function($q, $kw) {
                $q->whereRaw("users.name LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('tabla_afectada', function($q, $kw) {
                $q->whereRaw("auditoria_sistema.tabla_afectada LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('accion', function($q, $kw) {
                $q->whereRaw("auditoria_sistema.accion LIKE ?", ["%{$kw}%"]);
            })

            // --- FORMATEO VISUAL ---
            ->editColumn('accion', function($row) {
                $class = match(strtoupper($row->accion)) {
                    'INSERT' => 'success',
                    'UPDATE' => 'warning',
                    'DELETE' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge badge-' . $class . '">' . e($row->accion) . '</span>';
            })
            ->editColumn('tabla_afectada', function($row) {
                return '<code>' . e($row->tabla_afectada) . ' <small class="text-muted">(ID: ' . $row->registro_id . ')</small></code>';
            })
            ->editColumn('nombre_usuario', function($row) {
                return $row->nombre_usuario ? '<strong>' . e($row->nombre_usuario) . '</strong>' : '<span class="text-muted">Sistema</span>';
            })
            ->editColumn('ejecutado_en', function($row) {
                return $row->ejecutado_en ? date('Y-m-d H:i:s', strtotime($row->ejecutado_en)) : '';
            })
            ->addColumn('acciones', function($row) {
                // Empaquetamos los datos en JSON de manera segura para pasarlos a JavaScript
                $dataJson = htmlspecialchars(json_encode([
                    'tabla' => $row->tabla_afectada,
                    'registro_id' => $row->registro_id,
                    'anteriores' => $row->valores_anteriores,
                    'nuevos' => $row->valores_nuevos
                ]), ENT_QUOTES, 'UTF-8');

                return '
                <div class="btn-group">
                    <button class="btn btn-success btn-sm" onclick=\'detallesAuditoria(' . $dataJson . ')\' data-toggle="modal" data-target="#detallesAuditoriaModal" title="Ver Detalles">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['accion', 'tabla_afectada', 'nombre_usuario', 'acciones'])
            ->make(true);
    }
}
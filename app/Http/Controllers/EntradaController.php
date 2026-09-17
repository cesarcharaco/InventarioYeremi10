<?php

namespace App\Http\Controllers;

use App\Models\EntradaAlmacen; 
use App\Models\DetalleEntrada; 
use App\Models\Proveedor;
use App\Models\InsumoRecepcion;
use App\Models\HistoricoInsumoRecepcion;
use App\Models\Insumos;        
use App\Models\Local;
use App\Models\InsumosC;       
use App\Models\ModeloVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EntradaController extends Controller
{
    public function index()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'No tiene permisos para ver el historial.');
        }

        // Cargamos los proveedores para el nuevo filtro de la vista
        $proveedores = Proveedor::orderBy('nombre', 'asc')->get();

        return view('entradas.index', compact('proveedores'));
    }

    public function getEntradasData(Request $request)
    {
        if (Gate::denies('gestionar-entradas')) {
            return response()->json(['error' => 'Acceso denegado'], 403);
        }

        $query = EntradaAlmacen::with(['proveedor', 'usuario', 'local'])
            ->select('entradas_almacen.*'); // Evitar colisiones de columnas en Joins

        // Aplicar filtros recibidos por AJAX
        if ($request->filled('fecha_desde')) {
            $query->whereDate('entradas_almacen.created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('entradas_almacen.created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('id_proveedor')) {
            $query->where('entradas_almacen.id_proveedor', $request->id_proveedor);
        }

        if ($request->filled('estado')) {
            $query->where('entradas_almacen.estado', $request->estado);
        }

        return DataTables::of($query)
            ->editColumn('created_at', function($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d/m/Y h:i A');
            })
            ->editColumn('proveedor', function($row) {
                return '<span class="text-bold">' . e($row->proveedor->nombre ?? 'N/A') . '</span><br><small class="text-muted">' . e($row->proveedor->rif ?? '') . '</small>';
            })
            ->editColumn('local', function($row) {
                return '<span class="badge badge-info shadow-sm"><i class="fas fa-warehouse mr-1"></i> ' . e($row->local->nombre ?? 'N/A') . '</span>';
            })
            ->editColumn('total_costo_usd', function($row) {
                return '<span class="text-orange">$' . number_format($row->total_costo_usd, 2) . '</span>';
            })
            ->editColumn('usuario', function($row) {
                return '<small><i class="fas fa-user mr-1"></i> ' . e($row->usuario->name ?? 'Sistema') . '</small>';
            })
            ->addColumn('acciones', function($row) {
                $acciones = '<div class="btn-group">
                    <a href="'.route('entradas.show', $row->id).'" class="btn btn-info btn-xs" title="Ver Detalle">
                        <i class="fas fa-eye"></i>
                    </a>';
                
               if ($row->estado === 'PENDIENTE' && Gate::allows('anular-entrada')) {
                   $acciones .= '<button type="button" class="btn btn-danger btn-xs btn-anular" data-id="'.$row->id.'" title="Anular Entrada">
                       <i class="fas fa-ban"></i>
                   </button>';
               }

                $acciones .= '</div>';
                return $acciones;
            })
            ->rawColumns(['proveedor', 'local', 'total_costo_usd', 'usuario', 'acciones'])
            ->make(true);
    }
    public function create()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $proveedores = Proveedor::orderBy('nombre', 'asc')->get();
        $insumos = Insumos::where('estado', '!=', 'Suspendido')
                          ->orderBy('producto', 'asc')
                          ->get();
        
        $depositos = Local::where('tipo', 'DEPOSITO')
                          ->where('estado', 'Activo')
                          ->orderBy('nombre', 'asc')
                          ->get();
        
        if($depositos->isEmpty()){
            return redirect()->back()->with('warning', 'No existen locales configurados como DEPOSITO.');
        }

        return view('entradas.create', compact('proveedores', 'insumos', 'depositos'));
    }

    public function store(Request $request)
        {
            if (Gate::denies('gestionar-entradas')) {
                return redirect()->back()->with('error', 'Acceso denegado.');
            }

            $request->validate([
                'id_proveedor' => 'required|exists:proveedores,id',
                'id_local'     => 'required|exists:local,id',
                'nro_orden_entrega' => 'nullable|string|max:255',
                'observaciones' => 'nullable|string',
                'items'        => 'required|array|min:1',
                'items.*.id_insumo'      => 'required|exists:insumos,id',
                'items.*.cantidad'       => 'required|numeric|min:0.01',
                'items.*.costo_unitario' => 'required|numeric|min:0',
            ]);

            try {
                DB::beginTransaction();

                // 1. Crear cabecera usando EntradaAlmacen. 
                // Se fuerza el estado PENDIENTE para que Almacén sepa que está en tránsito.
                $entrada = EntradaAlmacen::create([
                    'id_proveedor'      => $request->id_proveedor,
                    'id_local'          => $request->id_local,
                    'id_user'           => auth()->id(),
                    'nro_orden_entrega' => $request->nro_orden_entrega,
                    'fecha_entrada'     => now(),
                    'total_costo_usd'   => collect($request->items)->sum(function($item) {
                        return $item['cantidad'] * $item['costo_unitario'];
                    }),
                    'observaciones'     => $request->observaciones,
                    'estado'            => 'PENDIENTE'
                ]);

                foreach ($request->items as $item) {
                    // 2. Crear detalle usando DetalleEntrada (Documento de respaldo)
                    $detalle = $entrada->detalles()->create([
                        'id_insumo'          => $item['id_insumo'],
                        'cantidad'           => $item['cantidad'],
                        'costo_unitario_usd' => $item['costo_unitario'],
                    ]);

                    // 3. Crear registro en el buffer de recepción (Área de cuarentena)
                    // Usamos la relación definida en el modelo DetalleEntrada
                    $detalle->recepcionBuffer()->create([
                        'id_insumo'          => $item['id_insumo'],
                        'id_local'           => $request->id_local, // El local final que espera la mercancía
                        'cantidad'           => $item['cantidad'],
                        'costo_unitario_usd' => $item['costo_unitario'],
                        'origen'             => 'PROVEEDOR',
                        'estado'             => 'PENDIENTE',
                    ]);
                }

                DB::commit();
                return redirect()->route('entradas.index')->with('success', 'Entrada registrada exitosamente. La mercancía ha sido enviada al área de revisión de Almacén.');

            } catch (\Exception $e) {
                DB::rollback();
                return redirect()->back()->with('error', 'Error al procesar: ' . $e->getMessage());
            }
        }

    public function show($id)
    {
        // Uso de relaciones corregidas: 'usuario' en lugar de 'user'
        $entrada = EntradaAlmacen::with(['proveedor', 'usuario', 'local', 'detalles.insumo'])->findOrFail($id);
        return view('entradas.show', compact('entrada'));
    }

    public function destroy($id)
    {
        if (Gate::denies('anular-entrada')) {
                return redirect()->route('entradas.index')->with('error', 'No tiene permisos para anular entradas de almacén.');
            }
            
        try {
            DB::beginTransaction();

            // Cargamos la entrada y sus detalles
            $entrada = EntradaAlmacen::with('detalles')->findOrFail($id);

            // 1. Verificación de seguridad: Solo se pueden eliminar entradas PENDIENTES
            if ($entrada->estado !== 'PENDIENTE') {
                return redirect()->back()->with('error', 'No se puede eliminar una entrada que ya ha sido procesada o aprobada por Almacén. Debe realizar una devolución o ajuste de inventario.');
            }

            // 2. Limpiar el buffer de recepción (área de cuarentena)
            // Aunque la base de datos tenga onDelete('cascade'), es buena práctica limpiar a través del modelo
            foreach ($entrada->detalles as $detalle) {
                // Borramos el registro en insumos_recepcion asociado a este detalle
                $detalle->recepcionBuffer()->delete();
            }

            // 3. Eliminar los detalles (documento) y la cabecera
            $entrada->detalles()->delete();
            $entrada->delete();

            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada anulada exitosamente. Se ha retirado la mercancía del área de revisión.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function pendientesRecepcion()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $proveedores = Proveedor::orderBy('nombre', 'asc')->get();
        $depositos = Local::where('tipo', 'DEPOSITO')->where('estado', 'Activo')->orderBy('nombre', 'asc')->get();
        $modelosVenta = ModeloVenta::all();

        return view('entradas.recepcion', compact('proveedores', 'depositos', 'modelosVenta'));
    }

    public function getDataRecepciones(Request $request)
    {
        if (Gate::denies('gestionar-entradas')) {
            return response()->json(['error' => 'Acceso denegado'], 403);
        }

        // CORREGIDO: Se cambió 'insumo_recepcions' por 'insumos_recepcion'
        $query = InsumoRecepcion::with(['insumo.modeloVenta', 'local', 'detalleEntrada.entrada.proveedor'])
            ->select('insumos_recepcion.*')
            ->orderBy('insumos_recepcion.created_at', 'desc');

        // Filtros dinámicos
        if ($request->filled('fecha_desde')) {
            $query->whereDate('insumos_recepcion.created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('insumos_recepcion.created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('id_proveedor')) {
            $query->whereHas('detalleEntrada.entrada', function($q) use ($request) {
                $q->where('id_proveedor', $request->id_proveedor);
            });
        }

        if ($request->filled('id_local')) {
            $query->where('insumos_recepcion.id_local', $request->id_local);
        }

        if ($request->filled('estado')) {
            $query->where('insumos_recepcion.estado', $request->estado);
        } else {
            $query->whereIn('insumos_recepcion.estado', ['PENDIENTE', 'RETENIDO', 'PROCESADO']);
        }

        $modelosVenta = ModeloVenta::all();

        return DataTables::of($query)
            ->editColumn('created_at', function($row) {
                return $row->created_at ? $row->created_at->format('d/m/Y H:i') : '';
            })
            ->editColumn('orden', function($row) {
                return $row->detalleEntrada->entrada->nro_orden_entrega ?? 'S/N';
            })
            ->editColumn('proveedor', function($row) {
                return $row->detalleEntrada->entrada->proveedor->nombre ?? 'N/D';
            })
            ->editColumn('local', function($row) {
                return '<span class="badge badge-info">' . e($row->local->nombre ?? 'N/D') . '</span>';
            })
            ->editColumn('insumo', function($row) {
                $html = '<strong>' . e($row->insumo->producto ?? 'N/D') . '</strong>';
                $html .= '<br><small class="text-muted">' . e($row->insumo->descripcion ?? '') . '</small>';
                if ($row->observacion_recepcion) {
                    $html .= '<br><small class="text-primary font-italic">Obs: ' . e($row->observacion_recepcion) . '</small>';
                }
                return $html;
            })
            ->editColumn('cantidad', function($row) {
                return '<span class="text-bold" id="total_qty_' . $row->id . '">' . $row->cantidad . '</span>';
            })
            ->editColumn('costo_unitario_usd', function($row) {
                return '$ ' . number_format($row->costo_unitario_usd, 2);
            })
            ->editColumn('estado', function($row) {
                if ($row->estado === 'PENDIENTE') return '<span class="badge badge-warning">PENDIENTE</span>';
                if ($row->estado === 'RETENIDO') return '<span class="badge badge-danger">RETENIDO</span>';
                if ($row->estado === 'PROCESADO') return '<span class="badge badge-success">PROCESADO</span>';
                return '<span class="badge badge-secondary">' . $row->estado . '</span>';
            })
            ->addColumn('acciones', function($row) use ($modelosVenta) {
                $acciones = '';
                
                if ($row->estado === 'PENDIENTE' || $row->estado === 'RETENIDO') {
                    $acciones .= '<button type="button" class="btn btn-success btn-xs btn-block mb-1" data-toggle="modal" data-target="#modalProcesar_' . $row->id . '">
                        <i class="fas fa-check-circle mr-1"></i> Revisar
                    </button>';
                }

                if ($row->estado !== 'PENDIENTE') {
                    $acciones .= '<form action="' . route('entradas.revertir', $row->id_detalle_entrada) . '" method="POST" id="form-revertir-' . $row->id . '">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="button" class="btn btn-danger btn-xs btn-block btn-revertir" data-id="' . $row->id . '" title="Revertir y corregir distribución">
                            <i class="fas fa-undo mr-1"></i> Revertir
                        </button>
                    </form>';
                }

                if ($row->estado === 'PENDIENTE' || $row->estado === 'RETENIDO') {
                    $acciones .= $this->renderModalProcesarHtml($row, $modelosVenta);
                }

                return $acciones;
            })
            ->rawColumns(['local', 'insumo', 'cantidad', 'estado', 'acciones'])
            ->make(true);
    }

    private function renderModalProcesarHtml($rec, $modelosVenta)
    {
        $optionsHtml = '<option value="">Seleccione...</option>';
        foreach ($modelosVenta as $mod) {
            $selected = ($rec->insumo && $rec->insumo->modelo_venta_id == $mod->id) ? 'selected' : '';
            $optionsHtml .= '<option value="'.$mod->id.'" '.$selected.' 
                data-tasa-bcv="'.$mod->tasa_bcv.'"
                data-tasa-binance="'.$mod->tasa_binance.'"
                data-factor-bcv="'.$mod->factor_bcv.'"
                data-factor-usdt="'.$mod->factor_usdt.'"
                data-porcentaje-extra="'.$mod->porcentaje_extra.'">
                '.$mod->modelo.'
            </option>';
        }

        $modeloInfoHtml = '';
        if ($rec->insumo && $rec->insumo->modeloVenta) {
            $modeloInfoHtml = '<div class="alert alert-light border py-2 mb-3">
                <small><i class="fas fa-tag text-primary mr-1"></i> Modelo de venta actual: <strong class="text-dark">'.e($rec->insumo->modeloVenta->modelo).'</strong></small>
            </div>';
        } else {
            $modeloInfoHtml = '<div class="alert alert-light border py-2 mb-3">
                <small><i class="fas fa-exclamation-circle text-warning mr-1"></i> Este producto aún no tiene un modelo de venta asignado previamente.</small>
            </div>';
        }

        return '
        <div class="modal fade" id="modalProcesar_'.$rec->id.'" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="'.route('entradas.procesar', $rec->id).'" method="POST" id="formProcesar_'.$rec->id.'">
                    '.csrf_field().'
                    <div class="modal-content text-left">
                        <div class="modal-header bg-primary">
                            <h5 class="modal-title">Gestionar: '.e($rec->insumo->producto ?? '').'</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-secondary">
                                <small>
                                    <i class="fas fa-info-circle mr-1"></i> 
                                    Cantidad Disponible: <strong class="total-factura">'.$rec->cantidad.'</strong> | 
                                    Costo unitario: <strong>$ '.number_format($rec->costo_unitario_usd, 2).'</strong>
                                </small>
                            </div>
                            '.$modeloInfoHtml.'
                            <h6 class="text-bold text-dark mb-2"><i class="fas fa-sliders-h mr-1"></i> Distribución de Cantidades:</h6>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="text-success">Aprobar (Stock Real)</label>
                                    <input type="number" step="0.01" name="cant_aprobar" id="cant_aprobar_'.$rec->id.'" value="'.$rec->cantidad.'" class="form-control distribucion-input" data-id="'.$rec->id.'" min="0" max="'.$rec->cantidad.'" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="text-warning">Retener (Cuarentena)</label>
                                    <input type="number" step="0.01" name="cant_retenido" id="cant_retenido_'.$rec->id.'" value="0" class="form-control distribucion-input" data-id="'.$rec->id.'" min="0" max="'.$rec->cantidad.'" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="text-danger">Rechazar (Dañado)</label>
                                    <input type="number" step="0.01" name="cant_rechazado" id="cant_rechazado_'.$rec->id.'" value="0" class="form-control distribucion-input" data-id="'.$rec->id.'" min="0" max="'.$rec->cantidad.'" required>
                                </div>
                            </div>
                            <div id="alertaSuma_'.$rec->id.'" class="alert alert-danger py-1 px-2 mb-3" style="display: none; font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle mr-1"></i> La suma de las cantidades distribuidas debe ser exactamente igual a <strong>'.$rec->cantidad.'</strong>.
                            </div>
                            <div id="seccionAprobacion_'.$rec->id.'" class="border p-3 rounded bg-light mb-3">
                                <h6 class="text-bold text-primary mb-3"><i class="fas fa-calculator mr-1"></i> Configuración de Costos y Precios</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Costo Unitario Final ($) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="costo_unitario" id="costo_'.$rec->id.'" value="'.$rec->costo_unitario_usd.'" class="form-control costo-input" data-id="'.$rec->id.'">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Modelo de Venta <span class="text-danger">*</span></label>
                                        <select name="modelo_venta_id" id="modelo_'.$rec->id.'" class="form-control modelo-select" data-id="'.$rec->id.'">
                                            '.$optionsHtml.'
                                        </select>
                                    </div>
                                </div>
                                <div class="row text-center mt-2">
                                    <div class="col-4">
                                        <div class="card p-2 bg-white border">
                                            <small class="text-muted">USD (BCV)</small>
                                            <h6 class="text-bold text-success mb-0" id="prev_usd_'.$rec->id.'">$ 0.00</h6>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card p-2 bg-white border">
                                            <small class="text-muted">Precio Bs</small>
                                            <h6 class="text-bold text-info mb-0" id="prev_bs_'.$rec->id.'">Bs 0.00</h6>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card p-2 bg-white border">
                                            <small class="text-muted">USD (USDT)</small>
                                            <h6 class="text-bold text-warning mb-0" id="prev_usdt_'.$rec->id.'">$ 0.00</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-left">
                                <label>Observaciones de Recepción</label>
                                <textarea name="observacion_recepcion" class="form-control" rows="2" placeholder="Detalles de la recepción...">'.e($rec->observacion_recepcion).'</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btnSubmit_'.$rec->id.'">Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>';
    }

    public function procesarRecepcion(Request $request, $id)
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $request->validate([
            'cant_aprobar' => 'required|numeric|min:0',
            'cant_retenido' => 'required|numeric|min:0',
            'cant_rechazado' => 'required|numeric|min:0',
            'costo_unitario' => 'required_if:cant_aprobar,>,0|nullable|numeric|min:0',
            'modelo_venta_id' => 'required_if:cant_aprobar,>,0|nullable|exists:modelos_venta,id',
            'observacion_recepcion' => 'nullable|string'
        ]);

        try {
            // Usamos la clausura de transacción para asegurar rollback automático ante excepciones
            return DB::transaction(function () use ($request, $id) {
                
                $recepcionOriginal = InsumoRecepcion::with('detalleEntrada.entrada', 'insumo')->findOrFail($id);
                $detalleId = $recepcionOriginal->id_detalle_entrada;
                $idInsumo = $recepcionOriginal->id_insumo;
                $idLocal = $recepcionOriginal->id_local;

                // 🔒 BLOQUEO PESIMISTA MAESTRO: Bloqueamos la fila del insumo para evitar condiciones de carrera en costos/modelos de venta
                $insumoMaestro = Insumos::where('id', $idInsumo)->lockForUpdate()->firstOrFail();

                // Obtener todos los registros previos asociados a este detalle
                $recepcionesAnteriores = InsumoRecepcion::where('id_detalle_entrada', $detalleId)->get();
                $totalFactura = (float) $recepcionesAnteriores->sum('cantidad');

                $cantAprobar = floatval($request->cant_aprobar);
                $cantRetenido = floatval($request->cant_retenido);
                $cantRechazado = floatval($request->cant_rechazado);

                $sumaDistribuida = round($cantAprobar + $cantRetenido + $cantRechazado, 2);
                $sumaTotalFactura = round($totalFactura, 2);

                if ($sumaDistribuida != $sumaTotalFactura) {
                    throw new \Exception('La suma de las cantidades distribuidas no coincide con el total de la factura.');
                }

                // 1. REVERSIÓN DE STOCK PREVIO (Con bloqueo de fila en la tabla pivote de cantidades)[cite: 13, 14]
                foreach ($recepcionesAnteriores as $recAnt) {
                    if ($recAnt->estado === 'PROCESADO') {
                        $stockLocal = InsumosC::where('id_insumo', $recAnt->id_insumo)
                                             ->where('id_local', $recAnt->id_local)
                                             ->lockForUpdate()
                                             ->first();
                        if ($stockLocal) {
                            $stockLocal->decrement('cantidad', $recAnt->cantidad);
                        }
                    }
                }

                // 2. GESTIÓN DEL HISTÓRICO DE AUDITORÍA[cite: 13]
                $historico = HistoricoInsumoRecepcion::where('id_detalle_entrada', $detalleId)->first();
                if (!$historico) {
                    HistoricoInsumoRecepcion::create([
                        'id_detalle_entrada' => $detalleId,
                        'id_insumo' => $idInsumo,
                        'costo_anterior' => $insumoMaestro->costo ?? 0,
                        'id_modelo_venta_anterior' => $insumoMaestro->modelo_venta_id ?? null,
                    ]);
                } else {
                    // Si se está re-procesando, aseguramos partir del costo anterior resguardado
                    $insumoMaestro->update([
                        'costo' => $historico->costo_anterior,
                        'modelo_venta_id' => $historico->id_modelo_venta_anterior
                    ]);
                }

                // 3. ELIMINAR los registros fragmentados anteriores del buffer[cite: 13]
                InsumoRecepcion::where('id_detalle_entrada', $detalleId)->delete();

                $costoFinal = $request->costo_unitario ?? 0;
                $modeloVentaFinal = $request->modelo_venta_id ?? null;

                // 4. CREAR NUEVOS REGISTROS Y ACTUALIZAR MAESTROS (Criterio individual por insumo)[cite: 13, 14]
                if ($cantAprobar > 0) {
                    $stockLocal = InsumosC::firstOrCreate(
                        [
                            'id_insumo' => $idInsumo,
                            'id_local' => $idLocal
                        ],
                        [
                            'cantidad' => 0
                        ]
                    );
                    
                    // Recargar el stock asegurando el bloqueo de la fila
                    $stockLocal = InsumosC::where('id_insumo', $idInsumo)
                                         ->where('id_local', $idLocal)
                                         ->lockForUpdate()
                                         ->first();

                    $stockLocal->increment('cantidad', $cantAprobar);

                    // Asignación directa del costo y modelo de venta configurado para este insumo específico
                    $insumoMaestro->update([
                        'costo' => $costoFinal,
                        'modelo_venta_id' => $modeloVentaFinal
                    ]);

                    InsumoRecepcion::create([
                        'id_detalle_entrada' => $detalleId,
                        'id_insumo' => $idInsumo,
                        'id_local' => $idLocal,
                        'cantidad' => $cantAprobar,
                        'costo_unitario_usd' => $costoFinal,
                        'estado' => 'PROCESADO',
                        'observacion_recepcion' => $request->observacion_recepcion
                    ]);
                }

                if ($cantRetenido > 0) {
                    InsumoRecepcion::create([
                        'id_detalle_entrada' => $detalleId,
                        'id_insumo' => $idInsumo,
                        'id_local' => $idLocal,
                        'cantidad' => $cantRetenido,
                        'costo_unitario_usd' => $costoFinal,
                        'estado' => 'RETENIDO',
                        'observacion_recepcion' => $request->observacion_recepcion
                    ]);
                }

                if ($cantRechazado > 0) {
                    InsumoRecepcion::create([
                        'id_detalle_entrada' => $detalleId,
                        'id_insumo' => $idInsumo,
                        'id_local' => $idLocal,
                        'cantidad' => $cantRechazado,
                        'costo_unitario_usd' => $costoFinal,
                        'estado' => 'RECHAZADO',
                        'observacion_recepcion' => $request->observacion_recepcion
                    ]);
                }

                // 5. Verificar estado general de la cabecera de la entrada[cite: 13]
                $entradaAlmacen = $recepcionOriginal->detalleEntrada->entrada;
                $pendientesRestantes = InsumoRecepcion::whereHas('detalleEntrada', function($q) use ($entradaAlmacen) {
                    $q->where('id_entrada', $entradaAlmacen->id);
                })->whereIn('estado', ['PENDIENTE', 'RETENIDO'])->count();

                if ($pendientesRestantes === 0) {
                    $entradaAlmacen->update(['estado' => 'APROBADO']);
                } else {
                    $entradaAlmacen->update(['estado' => 'PENDIENTE']);
                }

                return redirect()->route('entradas.recepcion')->with('success', 'Recepción procesada y actualizada correctamente.');
            });

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar la recepción: ' . $e->getMessage());
        }
    }

    public function revertirRecepcion($idDetalleEntrada)
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        try {
            return DB::transaction(function () use ($idDetalleEntrada) {
                
                $registrosRecepcion = InsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->get();

                if ($registrosRecepcion->isEmpty()) {
                    throw new \Exception('No se encontraron registros para revertir.');
                }

                $primerRegistro = $registrosRecepcion->first();
                $idInsumo = $primerRegistro->id_insumo;

                // 🔒 BLOQUEO PESIMISTA: Bloquear el registro maestro e inventarios locales involucrados
                $insumoMaestro = Insumos::where('id', $idInsumo)->lockForUpdate()->firstOrFail();

                foreach ($registrosRecepcion as $recepcion) {
                    if ($recepcion->estado === 'PROCESADO') {
                        $stockLocal = InsumosC::where('id_insumo', $recepcion->id_insumo)
                                             ->where('id_local', $recepcion->id_local)
                                             ->lockForUpdate()
                                             ->first();
                        if ($stockLocal) {
                            $stockLocal->decrement('cantidad', $recepcion->cantidad);
                        }
                    }
                }

                // 1. RESTAURAR DATOS MAESTROS DESDE EL HISTÓRICO[cite: 13]
                $historico = HistoricoInsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->first();
                $costoBaseOriginal = $primerRegistro->costo_unitario_usd;

                if ($historico) {
                    $costoBaseOriginal = $historico->costo_anterior;

                    // Devolver el costo y modelo de venta a su estado previo en la tabla maestra
                    $insumoMaestro->update([
                        'costo' => $historico->costo_anterior,
                        'modelo_venta_id' => $historico->id_modelo_venta_anterior
                    ]);

                    // Limpiar el histórico para permitir un nuevo ciclo si se vuelve a procesar
                    $historico->delete();
                }

                // 2. Calcular la cantidad total original y limpiar el buffer fraccionado[cite: 13]
                $cantidadTotalOriginal = $registrosRecepcion->sum('cantidad');
                InsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->delete();

                // 3. Volver a crear un único registro base en estado PENDIENTE[cite: 12, 13]
                InsumoRecepcion::create([
                    'id_detalle_entrada' => $idDetalleEntrada,
                    'id_insumo' => $idInsumo,
                    'id_local' => $primerRegistro->id_local,
                    'cantidad' => $cantidadTotalOriginal,
                    'costo_unitario_usd' => $costoBaseOriginal,
                    'origen' => $primerRegistro->origen ?? null,
                    'estado' => 'PENDIENTE',
                    'observacion_recepcion' => null
                ]);

                // 4. Regresar la cabecera principal a PENDIENTE[cite: 10, 11]
                $detalle = DetalleEntrada::with('entrada')->find($idDetalleEntrada);
                if ($detalle && $detalle->entrada) {
                    $detalle->entrada->update(['estado' => 'PENDIENTE']);
                }

                return redirect()->route('entradas.recepcion')->with('success', 'Recepción revertida con éxito. El insumo ha recuperado su costo y estado original.');
            });

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al revertir la recepción: ' . $e->getMessage());
        }
    }
}
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

class EntradaController extends Controller
{
    public function index()
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'No tiene permisos para ver el historial.');
        }

        // Cargamos con las relaciones definidas en EntradaAlmacen
        $entradas = EntradaAlmacen::with(['proveedor', 'usuario', 'local'])
                                   ->orderBy('created_at', 'desc')->get();

        return view('entradas.index', compact('entradas'));
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

        $recepciones = InsumoRecepcion::with(['insumo', 'local', 'detalleEntrada.entrada.proveedor'])
            ->whereIn('estado', ['PENDIENTE', 'RETENIDO', 'PROCESADO'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Cargamos los modelos de venta disponibles para el selector
        $modelosVenta = ModeloVenta::all();

        return view('entradas.recepcion', compact('recepciones', 'modelosVenta'));
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
            DB::beginTransaction();

            $recepcionOriginal = InsumoRecepcion::with('detalleEntrada.entrada', 'insumo')->findOrFail($id);
            $detalleId = $recepcionOriginal->id_detalle_entrada;
            $idInsumo = $recepcionOriginal->id_insumo;
            $idLocal = $recepcionOriginal->id_local;

            // Obtener todos los registros previos asociados a este detalle para calcular el total exacto de la factura
            $recepcionesAnteriores = InsumoRecepcion::where('id_detalle_entrada', $detalleId)->get();
            $totalFactura = $recepcionesAnteriores->sum('cantidad');

            $cantAprobar = floatval($request->cant_aprobar);
            $cantRetenido = floatval($request->cant_retenido);
            $cantRechazado = floatval($request->cant_rechazado);

            // Validar que la suma coincida exactamente con lo que llegó en la factura
            if (round($cantAprobar + $cantRetenido + $cantRechazado, 2) !== round($totalFactura, 2)) {
                return redirect()->back()->with('error', 'La suma de las cantidades distribuidas no coincide con el total de la factura.');
            }

            // 1. REVERSIÓN DE STOCK: Descontar del inventario real lo que se había aprobado previamente
            foreach ($recepcionesAnteriores as $recAnt) {
                if ($recAnt->estado === 'PROCESADO') {
                    $stock = InsumosC::where('id_insumo', $recAnt->id_insumo)->where('id_local', $recAnt->id_local)->first();
                    if ($stock) {
                        $stock->decrement('cantidad', $recAnt->cantidad);
                    }
                }
            }

            // 2. GESTIÓN DEL HISTÓRICO: Guardar la "foto" original o restaurar el estado base maestro usando Eloquent
            $historico = HistoricoInsumoRecepcion::where('id_detalle_entrada', $detalleId)->first();
            if (!$historico) {
                $insumoMaestro = Insumos::find($idInsumo);
                HistoricoInsumoRecepcion::create([
                    'id_detalle_entrada' => $detalleId,
                    'id_insumo' => $idInsumo,
                    'costo_anterior' => $insumoMaestro->costo ?? 0,
                    'id_modelo_venta_anterior' => $insumoMaestro->modelo_venta_id ?? null,
                ]);
            } else {
                Insumos::where('id', $idInsumo)->update([
                    'costo' => $historico->costo_anterior,
                    'modelo_venta_id' => $historico->id_modelo_venta_anterior
                ]);
            }

            // 3. ELIMINAR los registros fragmentados anteriores de la recepción
            InsumoRecepcion::where('id_detalle_entrada', $detalleId)->delete();

            $costoFinal = $request->costo_unitario ?? 0;

            // 4. CREAR LOS NUEVOS REGISTROS SEGÚN LA DISTRIBUCIÓN ACTUALIZADA
            if ($cantAprobar > 0) {
                $stock = InsumosC::where('id_insumo', $idInsumo)->where('id_local', $idLocal)->first();
                if ($stock) {
                    $stock->increment('cantidad', $cantAprobar);
                } else {
                    InsumosC::create([
                        'id_insumo' => $idInsumo,
                        'id_local' => $idLocal,
                        'cantidad' => $cantAprobar
                    ]);
                }

                Insumos::where('id', $idInsumo)->update([
                    'costo' => $costoFinal,
                    'modelo_venta_id' => $request->modelo_venta_id
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

            // 5. Verificar estado general de la entrada
            $entradaAlmacen = $recepcionOriginal->detalleEntrada->entrada;
            $pendientesRestantes = InsumoRecepcion::whereHas('detalleEntrada', function($q) use ($entradaAlmacen) {
                $q->where('id_entrada', $entradaAlmacen->id);
            })->whereIn('estado', ['PENDIENTE', 'RETENIDO'])->count();

            if ($pendientesRestantes === 0) {
                $entradaAlmacen->update(['estado' => 'APROBADO']);
            } else {
                $entradaAlmacen->update(['estado' => 'PENDIENTE']);
            }

            DB::commit();
            return redirect()->route('entradas.recepcion')->with('success', 'Recepción procesada y actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al procesar la recepción: ' . $e->getMessage());
        }
    }

    public function revertirRecepcion($idDetalleEntrada)
    {
        if (Gate::denies('gestionar-entradas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        try {
            DB::beginTransaction();

            // 1. Buscar todos los registros de recepción derivados de este detalle de entrada
            $registrosRecepcion = InsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->get();

            if ($registrosRecepcion->isEmpty()) {
                return redirect()->back()->with('error', 'No se encontraron registros para revertir.');
            }

            $primerRegistro = $registrosRecepcion->first();
            $idInsumo = $primerRegistro->id_insumo;

            foreach ($registrosRecepcion as $recepcion) {
                // 2. Si alguna porción fue procesada, debemos descontarla del stock real
                if ($recepcion->estado === 'PROCESADO') {
                    $stock = InsumosC::where('id_insumo', $recepcion->id_insumo)
                                     ->where('id_local', $recepcion->id_local)
                                     ->first();
                    if ($stock) {
                        $stock->decrement('cantidad', $recepcion->cantidad);
                    }
                }
            }

            // 3. RESTAURAR DATOS MAESTROS DESDE EL HISTÓRICO DE AUDITORÍA
            $historico = HistoricoInsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->first();
            $costoBaseOriginal = $primerRegistro->costo_unitario_usd;

            if ($historico) {
                $costoBaseOriginal = $historico->costo_anterior;

                // Restaurar los valores originales en la tabla maestra Insumos
                Insumos::where('id', $idInsumo)->update([
                    'costo' => $historico->costo_anterior,
                    'modelo_venta_id' => $historico->id_modelo_venta_anterior
                ]);

                // Eliminar el registro histórico para limpiar la auditoría de este ciclo y permitir futuras capturas
                $historico->delete();
            }

            // 4. Calcular la cantidad total original sumando las porciones fraccionadas
            $cantidadTotalOriginal = $registrosRecepcion->sum('cantidad');

            // 5. Eliminar los registros hijos o adicionales que se crearon en el fraccionamiento
            InsumoRecepcion::where('id_detalle_entrada', $idDetalleEntrada)->delete();

            // 6. Volver a crear un único registro base en estado PENDIENTE con la cantidad total original y costo restaurado
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

            // 7. Si la cabecera (EntradaAlmacen) se había marcado como APROBADO, regresarla a PENDIENTE
            $detalle = DetalleEntrada::with('entrada')->find($idDetalleEntrada);
            if ($detalle && $detalle->entrada) {
                $detalle->entrada->update(['estado' => 'PENDIENTE']);
            }

            DB::commit();
            return redirect()->route('entradas.recepcion')->with('success', 'Recepción revertida con éxito. El insumo ha recuperado su costo y estado original.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al revertir la recepción: ' . $e->getMessage());
        }
    }
}
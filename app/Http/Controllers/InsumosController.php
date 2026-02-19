<?php

namespace App\Http\Controllers;

use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Local;
use App\Models\Categoria;
use App\Models\ModeloVenta;
use App\Models\Gerencias;
use Illuminate\Http\Request;
use App\Http\Requests\InsumosRequest;
use App\Http\Requests\InsumosUpdateRequest;
use Illuminate\Support\Facades\DB; // Añadido al namespace
use Illuminate\Support\Facades\Gate;

class InsumosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('ver-logistica');
        // Se ajustó para obtener stock_min/max desde la tabla insumos
        // y la cantidad física desde la tabla pivot
        $insumos = DB::table('insumos')
            ->join('insumos_has_cantidades', 'insumos.id', '=', 'insumos_has_cantidades.id_insumo')
            ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
            ->select(
                'insumos.id',
                'insumos.serial',
                'insumos.producto',
                'insumos.descripcion',
                'insumos.estado',
                'insumos.stock_min', // Ahora viene de insumos
                'insumos.stock_max', // Ahora viene de insumos
                'insumos_has_cantidades.cantidad', // Columna unificada
                'insumos_has_cantidades.id_local',
                'insumos_has_cantidades.estado_local',
                'local.nombre as nombre_local'
            )
            ->get();

        return view('inventario.insumos.index', compact('insumos'));
    }

    public function precios()
    {
        Gate::authorize('ver-costos');
            $insumos = DB::table('insumos')
        ->join('categorias', 'insumos.categoria_id', '=', 'categorias.id')
        ->join('modelos_venta', 'insumos.modelo_venta_id', '=', 'modelos_venta.id')
        ->select(
            'insumos.id', // Aseguramos el ID del insumo
            'insumos.producto',
            'insumos.serial',
            'insumos.costo',
            'insumos.precio_venta_usd',
            'insumos.precio_venta_bs',
            'insumos.precio_venta_usdt',
            'categorias.categoria as nombre_categoria',
            'modelos_venta.modelo as nombre_modelo',
            'modelos_venta.tasa_bcv',
            'modelos_venta.tasa_binance', // Añadido
            'modelos_venta.factor_bcv',
            'modelos_venta.factor_usdt'
        )
        ->get();

    return view('inventario.insumos.precios', compact('insumos'));
    }

   
    public function actualizarCosto(Request $request) 
    {
        Gate::authorize('editar-datos-maestros');
        try {
            // 1. Validar que lleguen los datos
            if (!$request->id || !$request->costo) {
                return response()->json(['success' => false, 'error' => 'Datos incompletos'], 400);
            }

            // 2. Obtener datos con Join (usando nombres de tablas plurales)
            $insumoData = DB::table('insumos')
                ->join('modelos_venta', 'insumos.modelo_venta_id', '=', 'modelos_venta.id')
                ->where('insumos.id', $request->id)
                ->select(
                    'modelos_venta.tasa_bcv', 
                    'modelos_venta.tasa_binance', 
                    'modelos_venta.factor_bcv', 
                    'modelos_venta.factor_usdt',
                    'modelos_venta.porcentaje_extra'
                )
                ->first();

            if ($insumoData) {
                $costo = (float)$request->costo;
                
                // Convertir a float para evitar errores de división
                $tBcv = (float)$insumoData->tasa_bcv;
                $tBinance = (float)$insumoData->tasa_binance;
                $fBcv = (float)$insumoData->factor_bcv;
                $fUsdt = (float)$insumoData->factor_usdt;
                $extra = (float)$insumoData->porcentaje_extra;

                // --- CÁLCULOS (Corregidos con $) ---
                // Si tBcv es 0, evitamos división por cero
                if ($tBcv <= 0) $tBcv = 1; 

                // Cálculo Venta USD
                $usd = ($fBcv > 0) 
                       ? (($tBinance / $tBcv) / $fBcv) * $costo 
                       : $costo * (1 + $extra);

                // Cálculo Venta USDT
                $usdt = ($fUsdt > 0) 
                        ? $costo / $fUsdt 
                        : $costo * (1 + $extra);

                // Cálculo Venta BS
                $bs = $usd * $tBcv;

                // 3. Actualizar la tabla insumos
                DB::table('insumos')->where('id', $request->id)->update([
                    'costo' => $costo,
                    'precio_venta_usd' => round($usd, 2),
                    'precio_venta_bs' => round($bs, 2),
                    'precio_venta_usdt' => round($usdt, 2),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'precios' => [
                        'usd' => number_format($usd, 2),
                        'bs' => number_format($bs, 2),
                        'usdt' => number_format($usdt, 2)
                    ]
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Insumo no encontrado'], 404);

        } catch (\Exception $e) {
            // Esto devolverá el error real a la consola para que podamos verlo
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function create()
    {
        Gate::authorize('gestionar-insumos');
        $modelos = ModeloVenta::all();
        $locales = Local::all(); 
        $categorias = Categoria::orderBy('categoria', 'asc')->get();

        return view('inventario.insumos.create', compact('modelos', 'locales', 'categorias'));
    }

    public function store(Request $request)
    {
        $this->authorize('gestionar-insumos');
        $request->validate([
            'producto' => 'required',
            'categoria_id' => 'required|exists:categorias,id',
            'costo' => 'required|numeric|min:0',
            'modelo_venta_id' => 'required|exists:modelos_venta,id',
        ]);

        $serial = $this->generarSerialInsumo($request->categoria_id);
        $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
        $precios = $modelo->calcularPrecios($request->costo);

        DB::beginTransaction();
        try {
            $insumo = Insumos::create([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion,
                'serial'            => $serial,
                'categoria_id'      => $request->categoria_id,
                'stock_min'         => $request->stock_min ?? 0, // Guardado en insumos
                'stock_max'         => $request->stock_max ?? 0, // Guardado en insumos
                'costo'             => $request->costo,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'precio_venta_usd'  => $precios['precio_venta_usd'],
                'precio_venta_bs'   => $precios['precio_venta_bs'],
                'precio_venta_usdt' => $precios['precio_venta_usdt']
            ]);

            if ($request->has('id_local')) {
                foreach ($request->id_local as $local_id) {
                    InsumosC::create([
                        'id_insumo' => $insumo->id,
                        'id_local'  => $local_id,
                        'cantidad'  => $request->cantidad[$local_id] ?? 0, // Usamos la nueva columna cantidad
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('insumos.index')->with('success', 'Insumo registrado con éxito');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->authorize('gestionar-insumos');
        // Se lee directamente de la tabla insumos ahora que centralizamos stock_min/max
        $insumo = Insumos::findOrFail($id);

        $categorias = Categoria::pluck('categoria', 'id');
        $modelos = ModeloVenta::pluck('modelo', 'id');

        return view('inventario.insumos.edit', compact('insumo', 'categorias', 'modelos'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('gestionar-insumos');
        try {
            DB::beginTransaction();

            $insumoActual = Insumos::findOrFail($id);
            $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
            
            $serialFinal = ($insumoActual->categoria_id != $request->categoria_id) 
                ? $this->generarSerialInsumo($request->categoria_id) 
                : $insumoActual->serial;

            $costo = $insumoActual->costo;
            $p_usd  = $costo / $modelo->factor_bcv;
            $p_bs   = $p_usd * $modelo->tasa_bcv;
            $p_usdt = $costo / $modelo->factor_usdt;

            $insumoActual->update([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion,
                'categoria_id'      => $request->categoria_id,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'serial'            => $serialFinal,
                'precio_venta_usd'  => $p_usd,
                'precio_venta_bs'   => $p_bs,
                'precio_venta_usdt' => $p_usdt,
                'stock_min'         => $request->stock_min, // Actualizado en insumos
                'stock_max'         => $request->stock_max, // Actualizado en insumos
            ]);

            DB::commit();
            
            $mensaje = "Insumo actualizado correctamente.";
            if ($serialFinal != $insumoActual->serial) {
                $mensaje .= " Se ha generado un nuevo serial: " . $serialFinal;
            }

            return redirect()->route('insumos.index')->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        Gate::authorize('editar-datos-maestros');
        $this->authorize('gestionar-insumos');
        $insumo = Insumos::find($request->id_insumo);

        if ($insumo && $insumo->delete()) {
            return redirect()->back()->with('success', 'El Insumo fue eliminado exitosamente!');
        } else {
            return redirect()->back()->with('error', 'El Insumo no pudo ser eliminado!');
        }
    }

    private function generarSerialInsumo($categoriaId)
    {
        $prefix = str_pad($categoriaId, 3, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return "INS-{$prefix}-{$random}";
    }
    public function listarPorLocal($id_local)
    {
        $local = \App\Models\Local::findOrFail($id_local);

        // Consultamos los insumos filtrados por el ID del local
        $stock = DB::table('insumos_has_cantidades')
            ->join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->select(
                'insumos.producto',
                'insumos.serial',
                'insumos.descripcion',
                'insumos.stock_min', // Corregido: pertenece a la tabla insumos
                'insumos.stock_max', // Corregido: pertenece a la tabla insumos
                'insumos_has_cantidades.cantidad' // Stock actual en ese local
            )
            ->where('insumos_has_cantidades.id_local', $id_local)
            ->get();

        return view('inventario.insumos.por_local', compact('stock', 'local'));
    }
    public function cambiarEstadoInsumo(Request $request)
    {
        $id_local = $request->id_local;
        
        if ($request->tipo === 'global') {
            Gate::authorize('gestionar-estado-global');
        } else {
            Gate::authorize('gestionar-estado-local', $id_local);
        }
        try {
        $idInsumo = $request->id;
        $nuevoEstado = $request->estado;
        $tipoCambio = $request->tipo; // 'global' o 'local'
        $idLocal = $request->id_local; // El local actual donde estamos parados

        if ($tipoCambio === 'global') {
            $insumo = Insumos::findOrFail($idInsumo);
            $insumo->estado = $nuevoEstado;
            $insumo->save();
            $mensaje = "Estado global actualizado.";
        } else {
            // Actualizamos solo para el local actual
            $x=InsumosC::where('id_insumo', $idInsumo)
                ->where('id_local', $idLocal)
                ->update(['estado_local' => $nuevoEstado]);

            $mensaje = "Estado actualizado solo para este local.";
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error al procesar el cambio.'], 500);
    }
    }
}
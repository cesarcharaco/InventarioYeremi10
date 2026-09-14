<?php

namespace App\Http\Controllers;

use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\InsumoFoto;
use App\Models\Local;
use App\Models\Categoria;
use App\Models\ModeloVenta;
use App\Models\Gerencias;
use Illuminate\Http\Request;
use App\Http\Requests\InsumosRequest;
use App\Http\Requests\InsumosUpdateRequest;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use App\Imports\InsumosImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Notifications\StockBajoNotification;
use App\Models\User;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

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
            'insumos.descripcion',
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
            'producto'        => 'required',
            'categoria_id'    => 'required|exists:categorias,id',
            'costo'           => 'required|numeric|min:0',
            'modelo_venta_id' => 'required|exists:modelos_venta,id',
        ]);

        $serial = $request->filled('serial') 
                ? $request->serial 
                : $this->generarSerialInsumo($request->categoria_id);

        $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
        $precios = $modelo->calcularPrecios($request->costo);
        
        DB::beginTransaction();
        try {
            $insumo = Insumos::create([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion,
                'serial'            => $serial,
                'categoria_id'      => $request->categoria_id,
                'stock_min'         => $request->stock_min ?? 0,
                'stock_max'         => $request->stock_max ?? 0,
                'costo'             => $request->costo,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'precio_venta_usd'  => $precios['precio_venta_usd'],
                'precio_venta_bs'   => $precios['precio_venta_bs'],
                'precio_venta_usdt' => $precios['precio_venta_usdt']
            ]);

            if ($request->has('id_local') && is_array($request->id_local)) {
                foreach ($request->id_local as $local_id) {
                    // Definimos explícitamente la cantidad asignada a este local
                    $cantidadLocal = $request->cantidad[$local_id] ?? 0;

                    InsumosC::create([
                        'id_insumo' => $insumo->id,
                        'id_local'  => $local_id,
                        'cantidad'  => $cantidadLocal,
                    ]);

                    // Evaluamos contra la variable recién asignada
                    if ($cantidadLocal <= ($request->stock_min ?? 0)) {
                        $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                        $detalles = [
                            'titulo'  => '¡Stock Inicial Bajo!',
                            'mensaje' => "El producto {$insumo->producto} inició con stock crítico ({$cantidadLocal}) en un local.",
                            'url'     => route('insumos.index'),
                            'icono'   => 'fas fa-exclamation-triangle'
                        ];
                        
                        foreach ($gerentes as $gerente) {
                            $gerente->notify(new StockBajoNotification($detalles));
                        }
                    }
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
                
                if ($request->filled('serial') && $request->serial !== $insumoActual->serial) {
                    // 1. Prioridad: Serial manual escrito/escaneado por el usuario
                    $serialFinal = $request->serial;
                } elseif ($insumoActual->categoria_id != $request->categoria_id) {
                    // 2. Cambió la categoría y no escribió manual: Regenera por categoría
                    $serialFinal = $this->generarSerialInsumo($request->categoria_id);
                } else {
                    // 3. Sin cambios en categoría ni serial manual: Mantiene el existente
                    $serialFinal = $insumoActual->serial;
                }

                $costo = $insumoActual->costo;
                
                // Reemplazamos la división manual por el método seguro del modelo
                $precios = $modelo->calcularPrecios($costo);

                $insumoActual->update([
                    'producto'          => $request->producto,
                    'descripcion'       => $request->descripcion,
                    'categoria_id'      => $request->categoria_id,
                    'modelo_venta_id'   => $request->modelo_venta_id,
                    'serial'            => $serialFinal,
                    'precio_venta_usd'  => $precios['precio_venta_usd'],
                    'precio_venta_bs'   => $precios['precio_venta_bs'],
                    'precio_venta_usdt' => $precios['precio_venta_usdt'],
                    'stock_min'         => $request->stock_min, // Actualizado en insumos
                    'stock_max'         => $request->stock_max, // Actualizado en insumos
                ]);

                // --- LÓGICA DE NOTIFICACIÓN AUTOMÁTICA AL ACTUALIZAR ---
                // Consultamos las cantidades actuales en todos los locales para este insumo
                $stocksLocales = DB::table('insumos_has_cantidades')
                    ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
                    ->where('id_insumo', $id)
                    ->get();

                foreach ($stocksLocales as $stockLocal) {
                    if ($stockLocal->cantidad <= $request->stock_min) {
                        // Notificar a los administradores/gerentes
                        $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                        $detalles = [
                            'titulo'  => 'Stock Crítico tras Actualización',
                            'mensaje' => "El producto {$request->producto} está por debajo del nuevo mínimo en {$stockLocal->nombre}.",
                            'url'     => route('insumos.index'),
                            'icono'   => 'fas fa-sync-alt'
                        ];

                        foreach ($gerentes as $gerente) {
                            $gerente->notify(new StockBajoNotification($detalles));
                        }
                    }
                }
                // -------------------------------------------------------
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


    public function getInsumosData(Request $request)
    {
        Gate::authorize('ver-logistica');

        $query = DB::table('insumos')
            ->join('insumos_has_cantidades', 'insumos.id', '=', 'insumos_has_cantidades.id_insumo')
            ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
            ->select([
                'insumos.id',
                'insumos.serial',
                'insumos.producto',
                'insumos.descripcion',
                'insumos.estado as estado_global',
                'insumos.stock_min',
                'insumos.stock_max',
                'insumos_has_cantidades.cantidad',
                'insumos_has_cantidades.id_local',
                'insumos_has_cantidades.estado_local',
                'local.nombre as nombre_local'
            ]);
        // --- APLICACIÓN DE FILTROS PERSONALIZADOS ---
        if ($request->filled('filtro_producto')) {
            $query->where('insumos.producto', 'LIKE', '%' . $request->filtro_producto . '%');
        }

        if ($request->filled('filtro_estado_general')) {
            $query->where('insumos.estado', $request->filtro_estado_general);
        }

        if ($request->filled('filtro_estado_local')) {
            $query->where('insumos_has_cantidades.estado_local', $request->filtro_estado_local);
        }

        if ($request->filled('filtro_ubicacion')) {
            $query->where('local.nombre', $request->filtro_ubicacion);
        }
        // ------------------------------------------
        return DataTables::of($query)
            // --- MAPEADO DE BÚSQUEDA: ESTO ELIMINA LOS ERRORES DE LAS IMÁGENES ---
            ->filterColumn('estado_global', function($q, $kw) {
                $q->whereRaw("insumos.estado LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('estado_local', function($q, $kw) {
                $q->whereRaw("insumos_has_cantidades.estado_local LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('cantidad', function($q, $kw) {
                $q->whereRaw("insumos_has_cantidades.cantidad LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('nombre_local', function($q, $kw) {
                $q->whereRaw("local.nombre LIKE ?", ["%{$kw}%"]);
            })

            // --- FORMATEO VISUAL: IGUAL A TU IMAGEN ORIGINAL ---
            ->editColumn('serial', function($row) {
                return '<span class="badge badge-secondary">' . e($row->serial) . '</span>';
            })
            ->editColumn('producto', function($row) {
                return '<strong>' . e($row->producto) . '</strong>';
            })
            ->editColumn('estado_global', function($row) {
                $class = $row->estado_global === 'En Venta' ? 'success' : 'dark';
                return '<span class="badge badge-'.$class.'"><i class="fas fa-globe"></i> ' . e($row->estado_global) . '</span>';
            })
            ->editColumn('estado_local', function($row) {
                $class = $row->estado_local === 'Disponible' ? 'success' : 'danger';
                return '<span class="badge badge-'.$class.'"><i class="fas fa-store"></i> ' . e($row->estado_local) . '</span>';
            })
            // Colores de stock amarillos y oscuros como pediste
            ->editColumn('stock_min', function($row) {
                return '<span class="badge badge-warning" style="background-color: #ffe066; color: #000;">' . $row->stock_min . '</span>';
            })
            ->editColumn('stock_max', function($row) {
                return '<span class="badge badge-dark">' . $row->stock_max . '</span>';
            })
            ->editColumn('cantidad', function($row) {
                return '<span class="text-primary font-weight-bold" style="font-size: 1.1em;">' . $row->cantidad . '</span>';
            })
            ->editColumn('nombre_local', function($row) {
                return '<i class="fa fa-map-marker-alt text-danger"></i> ' . e($row->nombre_local);
            })
            ->addColumn('acciones', function($row) {
                return '
                <div class="btn-group">
                    <a href="'.route('insumos.edit', $row->id).'" class="btn btn-info btn-sm"><i class="fa fa-edit"></i></a>
                    <a href="'.route('insumos.album', $row->id).'" class="btn btn-warning btn-sm" title="Álbum de Fotos"><i class="fa fa-image"></i></a>
                    <button class="btn btn-success btn-sm" onclick="detalles(\''.$row->producto.'\',\''.addslashes($row->descripcion).'\',\''.$row->serial.'\','.$row->stock_min.','.$row->stock_max.','.$row->cantidad.',\''.$row->nombre_local.'\')" data-toggle="modal" data-target="#detalles"><i class="fa fa-eye"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="eliminar('.$row->id.')" data-toggle="modal" data-target="#eliminar_insumo"><i class="fa fa-trash"></i></button>
                    <a href="'.route('insumos.barcode_pdf', $row->id).'" target="_blank" class="btn btn-dark btn-sm" title="Imprimir Código de Barras">
                        <i class="fa fa-barcode"></i>
                    </a>
                </div>';
            })
            ->rawColumns(['serial', 'producto', 'estado_global', 'estado_local', 'stock_min', 'stock_max', 'cantidad', 'nombre_local', 'acciones'])
            ->make(true);
    }

    public function storeRapido(Request $request)
    {
        // 1. Autorización y Validación
        $this->authorize('gestionar-insumos');
        
        $request->validate([
            'producto'        => 'required|string|max:255',
            'descripcion'     => 'nullable|string',
            'categoria_id'    => 'required|exists:categorias,id',
            'costo'           => 'required|numeric|min:0',
            'modelo_venta_id' => 'required|exists:modelos_venta,id',
            'id_local'        => 'required|exists:local,id',
            'cantidad'        => 'required|integer|min:1',
        ]);

        // 2. Serial y Cálculo de Precios
        $serial = $request->filled('serial') 
                ? $request->serial 
                : $this->generarSerialInsumo($request->categoria_id);

        $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
        $precios = $modelo->calcularPrecios($request->costo);

        $stockMinimo = 10;
        $stockMaximo = 100;

        DB::beginTransaction();
        try {
            // 3. Creación del Insumo
            $insumo = Insumos::create([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion ?? $request->producto,
                'serial'            => $serial,
                'categoria_id'      => $request->categoria_id,
                'stock_min'         => $stockMinimo,
                'stock_max'         => $stockMaximo,
                'costo'             => $request->costo,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'precio_venta_usd'  => $precios['precio_venta_usd'],
                'precio_venta_bs'   => $precios['precio_venta_bs'],
                'precio_venta_usdt' => $precios['precio_venta_usdt']
            ]);

            // 4. Existencia Inicial en InsumosC
            $cantidadInicial = (int) $request->cantidad;

            InsumosC::create([
                'id_insumo' => $insumo->id,
                'id_local'  => $request->id_local,
                'cantidad'  => $cantidadInicial,
            ]);

            // 5. Notificación Protegida
            if ($cantidadInicial <= $stockMinimo) {
                try {
                    $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                    $detalles = [
                        'titulo'  => '¡Stock Inicial Bajo!',
                        'mensaje' => "El producto {$insumo->producto} inició con stock crítico ({$cantidadInicial}) en el local.",
                        'url'     => route('insumos.index'),
                        'icono'   => 'fas fa-exclamation-triangle'
                    ];
                    
                    foreach ($gerentes as $gerente) {
                        $gerente->notify(new StockBajoNotification($detalles));
                    }
                } catch (\Exception $eNotif) {
                    // Silenciamos fallo puntual de correo/notificación
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Insumo creado y asignado con éxito',
                'insumo'  => $insumo,
                'stock'   => $cantidadInicial
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar insumo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarCodigoBarrasPdf($id)
    {
        // 1. Buscar el insumo por su ID
        $insumo = DB::table('insumos')->where('id', $id)->first();

        if (!$insumo) {
            abort(404, 'El insumo no existe.');
        }

        // 2. Generar la imagen del código de barras (CODE 128) en Base64
        $generator = new BarcodeGeneratorPNG();
        $barcodeBase64 = base64_encode(
            $generator->getBarcode($insumo->serial, $generator::TYPE_CODE_128)
        );

        // 3. Definir cuántas etiquetas entran por hoja (ej. 24 etiquetas en grilla 3x8)
        $cantidadEtiquetas = 24;

        // 4. Cargar la vista y renderizar el PDF en tamaño Carta
        $pdf = Pdf::loadView('inventario.insumos.pdf_barcode', compact('insumo', 'barcodeBase64', 'cantidadEtiquetas'))
                  ->setPaper('letter', 'portrait');

        // 5. Retornar el PDF en el navegador para vista previa/impresión
        return $pdf->stream("etiquetas_{$insumo->serial}.pdf");
    }

    // 1. Cargar la vista principal del carrito
    public function etiquetasView()
    {
        return view('inventario.insumos.etiquetas');
    }

    // 2. Buscador en tiempo real para el selector
    public function buscarInsumosAjax(Request $request)
    {
        $search = trim($request->get('q'));

        $insumos = DB::table('insumos')
            ->where(function ($query) use ($search) {
                $query->where('serial', 'LIKE', "%{$search}%")
                      ->orWhere('producto', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
            })
            ->select('id', 'serial', 'producto', 'descripcion')
            ->limit(20)
            ->get();

        return response()->json($insumos);
    }

    // 3. Procesar el formulario del carrito y generar el PDF
    public function generarCodigosBarrasPdfMultiple(Request $request)
    {
        $items = $request->input('items', []); // Array de ['id' => X, 'hojas' => Y]

        if (empty($items)) {
            return back()->with('error', 'No hay insumos en la cola de impresión.');
        }

        $generator = new BarcodeGeneratorPNG();
        $listaImpresion = [];

        foreach ($items as $item) {
            $insumo = DB::table('insumos')->where('id', $item['id'])->first();
            if ($insumo) {
                $barcodeBase64 = base64_encode(
                    $generator->getBarcode($insumo->serial, $generator::TYPE_CODE_128)
                );

                // Cada "hoja" contiene 24 stickers del mismo insumo
                $hojas = max(1, intval($item['hojas'] ?? 1));

                for ($h = 0; $h < $hojas; $h++) {
                    $listaImpresion[] = [
                        'insumo' => $insumo,
                        'barcodeBase64' => $barcodeBase64
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('inventario.insumos.pdf_barcode_multiple', compact('listaImpresion'))
                  ->setPaper('letter', 'portrait');

        return $pdf->stream("etiquetas_lote.pdf");
    }

    public function albumIndex($id)
    {
        Gate::authorize('ver-logistica');
        
        $insumo = Insumos::with('fotos')->findOrFail($id);
        
        return view('inventario.insumos.album', compact('insumo'));
    }

    // Actualización del método en InsumosController.php para recibir el campo de título opcional
    public function albumStoreMultiple(Request $request, $id)
    {
        Gate::authorize('gestionar-insumos');
        
        // Aumentamos el límite de validación a 20MB (20480 KB) para permitir fotos profesionales de alta calidad
        $request->validate([
            'fotos'   => 'required',
            'fotos.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480',
            'titulo'  => 'nullable|string|max:150'
        ]);

        $insumo = Insumos::with('fotos')->findOrFail($id);

        if ($request->hasFile('fotos')) {
            $files = $request->file('fotos');
            $tienePrincipal = $insumo->fotos->where('es_principal', true)->count() > 0;
            $randomIndex = (!$tienePrincipal) ? rand(0, count($files) - 1) : -1;
            $tituloBase = $request->input('titulo');

            // Directorio para miniaturas de carga rápida
            $thumbPathDir = public_path('albumes/thumbs');
            if (!file_exists($thumbPathDir)) {
                mkdir($thumbPathDir, 0755, true);
            }

            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    continue;
                }

                // Nombre único compartido para el original y su miniatura
                $nombreArchivo = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // 1. GUARDAR LA FOTO ORIGINAL INTACTA en public/albumes
                $file->move(public_path('albumes'), $nombreArchivo);
                $pathOriginal = 'albumes/' . $nombreArchivo;

                // 2. GENERAR MINIATURA LIGERA (400x400px) para que la galería vuele sin afectar el original
                $this->generarMiniatura(public_path($pathOriginal), $thumbPathDir . '/' . $nombreArchivo, 400, 400, 75);

                $esPrincipal = (!$tienePrincipal && $index === $randomIndex);
                
                if ($esPrincipal) {
                    $tienePrincipal = true;
                }

                if (!empty($tituloBase)) {
                    $tituloFinal = count($files) > 1 ? "{$tituloBase} (" . ($index + 1) . ")" : $tituloBase;
                } else {
                    $tituloFinal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                }

                $insumo->fotos()->create([
                    'ruta'         => $pathOriginal,
                    'titulo'       => $tituloFinal,
                    'es_principal' => $esPrincipal
                ]);
            }
        }

        return redirect()->route('insumos.album', $id)->with('success', 'Fotografías de alta calidad agregadas con éxito.');
    }

    public function albumUpdateFoto(Request $request, $fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $request->validate(['titulo' => 'nullable|string|max:150']);
        
        $foto = InsumoFoto::findOrFail($fotoId);
        $foto->update(['titulo' => $request->titulo]);

        return response()->json(['success' => true, 'message' => 'Título actualizado correctamente.']);
    }

    public function albumDestroyFoto($fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $foto = InsumoFoto::findOrFail($fotoId);
        
        // 1. Eliminar archivo original físico
        if (file_exists(public_path($foto->ruta))) {
            unlink(public_path($foto->ruta));
        }

        // 2. Eliminar miniatura asociada si existe
        $nombreArchivo = basename($foto->ruta);
        $rutaThumb = public_path('albumes/thumbs/' . $nombreArchivo);
        if (file_exists($rutaThumb)) {
            unlink($rutaThumb);
        }

        $insumoId = $foto->insumo_id;
        $eraPrincipal = $foto->es_principal;
        
        $foto->delete();

        if ($eraPrincipal) {
            $siguienteFoto = InsumoFoto::where('insumo_id', $insumoId)->inRandomOrder()->first();
            if ($siguienteFoto) {
                $siguienteFoto->update(['es_principal' => true]);
            }
        }

        return redirect()->back()->with('success', 'Foto eliminada correctamente.');
    }

    public function albumSetPrincipal($fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $foto = InsumoFoto::findOrFail($fotoId);

        InsumoFoto::where('insumo_id', $foto->insumo_id)->update(['es_principal' => false]);
        $foto->update(['es_principal' => true]);

        return redirect()->back()->with('success', 'Foto establecida como principal exitosamente.');
    }

    public function albumGeneral(Request $request)
    {
        $fotos = InsumoFoto::with('insumo')->latest()->paginate(20);

        if ($request->ajax()) {
            // Preparamos los datos estructurados para agregarlos dinámicamente al arreglo de JS del visor
            $fotosData = $fotos->map(function($foto) {
                $nombreArchivo = basename($foto->ruta);
                $rutaThumb = file_exists(public_path('albumes/thumbs/' . $nombreArchivo)) 
                             ? asset('albumes/thumbs/' . $nombreArchivo) 
                             : asset($foto->ruta);

                return [
                    'ruta' => asset($foto->ruta), // Imagen original HD para el visor
                    'thumb' => $rutaThumb,         // Miniatura para la tarjeta
                    'titulo' => $foto->titulo ?: 'Sin título',
                    'producto' => optional($foto->insumo)->producto ?? 'Sin producto',
                    'serial' => optional($foto->insumo)->serial ?? 'N/A',
                    'descripcion' => optional($foto->insumo)->descripcion ?? 'Sin descripción registrada.',
                    'es_principal' => $foto->es_principal,
                    'id' => $foto->id
                ];
            });

            return response()->json([
                'html' => view('inventario.insumos.partials.grid_items', compact('fotos'))->render(),
                'fotos' => $fotosData,
                'has_more' => $fotos->hasMorePages()
            ]);
        }

        return view('inventario.insumos.album_general', compact('fotos'));
    }

    private function generarMiniatura($rutaOrigen, $rutaDestino, $nuevoAncho, $nuevoAlto, $calidad)
    {
        $info = @getimagesize($rutaOrigen);
        if (!$info) return;

        list($anchoOriginal, $altoOriginal, $tipo) = $info;

        switch ($tipo) {
            case IMAGETYPE_JPEG:
                $imgOriginal = @imagecreatefromjpeg($rutaOrigen);
                break;
            case IMAGETYPE_PNG:
                $imgOriginal = @imagecreatefrompng($rutaOrigen);
                if ($imgOriginal) {
                    imagepalettetotruecolor($imgOriginal);
                    imagealphablending($imgOriginal, true);
                    imagesavealpha($imgOriginal, true);
                }
                break;
            case IMAGETYPE_WEBP:
                $imgOriginal = @imagecreatefromwebp($rutaOrigen);
                break;
            default:
                return;
        }

        if (!$imgOriginal) return;

        $ratio = $anchoOriginal / $altoOriginal;
        if ($nuevoAncho / $nuevoAlto > $ratio) {
            $nuevoAncho = $nuevoAlto * $ratio;
        } else {
            $nuevoAlto = $nuevoAncho / $ratio;
        }

        $imgMiniatura = imagecreatetruecolor((int)$nuevoAncho, (int)$nuevoAlto);

        if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_WEBP) {
            imagecolortransparent($imgMiniatura, imagecolorallocatealpha($imgMiniatura, 0, 0, 0, 127));
            imagealphablending($imgMiniatura, false);
            imagesavealpha($imgMiniatura, true);
        }

        imagecopyresampled($imgMiniatura, $imgOriginal, 0, 0, 0, 0, (int)$nuevoAncho, (int)$nuevoAlto, $anchoOriginal, $altoOriginal);

        $extension = strtolower(pathinfo($rutaDestino, PATHINFO_EXTENSION));
        if ($extension == 'png') {
            imagepng($imgMiniatura, $rutaDestino, 6);
        } else {
            imagejpeg($imgMiniatura, $rutaDestino, $calidad);
        }

        imagedestroy($imgOriginal);
        imagedestroy($imgMiniatura);
    }
}
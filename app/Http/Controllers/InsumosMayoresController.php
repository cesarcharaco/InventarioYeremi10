<?php

namespace App\Http\Controllers;

use App\Models\InsumosMayor;
use Illuminate\Http\Request;
use App\Imports\InsumosImport;
use App\Models\ListasOferta;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB; // Añadido al namespace
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Cliente;

class InsumosMayoresController extends Controller
{
    /**
     * Muestra el listado de productos al mayor.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Obtenemos todos los insumos mayoristas
        // Puedes agregar un ->orderBy('created_at', 'desc') si prefieres ver los últimos cargados primero
        $insumos = InsumosMayor::with('listaOferta')->get();


        return view('insumos_mayores.index', compact('insumos'));
    }

    public function createImport()
    {
        return view('insumos_mayores.cargar_ofertas');
    }

    public function importar(Request $request) 
    {
        // 1. Validar tanto el archivo como los nuevos metadatos
        $request->validate([
            'nombre'        => 'required|string',
            'proveedor'     => 'required|string',
            'fecha_inicio'  => 'required|date|after_or_equal:today',
            'fecha_fin'     => 'required|date|after:fecha_inicio',
            'monto_minimo'  => 'required|numeric',
            'incremento'    => 'required|numeric',
            'archivo'       => 'required|mimes:csv,xlsx'
        ]);

        try {
            // 2. Crear primero la cabecera (ListasOfertas)
            $lista = ListasOferta::create([
                'nombre'       => $request->nombre,
                'proveedor'    => $request->proveedor,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'monto_minimo' => $request->monto_minimo,
                'incremento'   => $request->incremento,
                'estado'       => 'activo'
            ]);

            // 3. Importar el archivo pasando el ID de la lista recién creada
            // Pasamos el $lista->id al constructor de InsumosImport
            Excel::import(new InsumosImport($lista->id,$request->incremento), $request->file('archivo'));

            return back()->with('success', 'Oferta "' . $request->nombre . '" procesada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }
    public function listarOfertas()
    {
        // Obtenemos solo las listas, omitiendo nombre y proveedor en la vista, 
        // pero permitiendo que el usuario vea los detalles de fechas y montos.
        $listas = ListasOferta::where('estado', 'activo')->get();
        
        return view('insumos_mayores.listas_para_pedidos', compact('listas'));
    }

    public function verItems($id)
    {
        $lista = ListasOferta::findOrFail($id);
        $items = $lista->items;

        // BUSCAMOS EL BORRADOR:
        // Intentamos conseguir un pedido que aún no esté en preparación/enviado
        $pedidoExistente = Pedido::with('detalles')
            ->where('user_id', auth()->id())
            ->where('listas_oferta_id', $id)
            ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
            ->first();

        return view('insumos_mayores.listas_items', compact('lista', 'items', 'pedidoExistente'));
    }

    public function guardarPedido(Request $request)
    {
        // 1. Validaciones
        $request->validate([
            'lista_id'   => 'required|exists:listas_ofertas,id',
            'cantidades' => 'required|array',
            'cantidades.*' => 'integer|min:0',
        ]);

        // dd($request->all()); // Comentado para que el flujo siga

        $lista = ListasOferta::findOrFail($request->lista_id);
        
        DB::beginTransaction();

        try {
            // 2. Buscar si el cliente ya tiene un pedido PENDIENTE o APROBADO
            $pedido = Pedido::where('user_id', Auth::id())
                ->where('listas_oferta_id', $lista->id)
                ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
                ->first();

            if (!$pedido) {
                $pedido = new Pedido();
                $pedido->user_id = Auth::id();
                $pedido->listas_oferta_id = $lista->id;
                // El estado inicial se definirá abajo según el total
            }

            // 3. Procesar los ítems y calcular total
            $totalAcumulado = 0;
            $detallesParaGuardar = []; // Mantengo tu variable original

            foreach ($request->cantidades as $insumo_id => $cantidad) {
                if ($cantidad > 0) {
                    $producto = InsumosMayor::findOrFail($insumo_id);
                    $subtotal = $producto->venta_usd * $cantidad;
                    $totalAcumulado += $subtotal;

                    $detallesParaGuardar[$insumo_id] = [
                        'insumos_mayor_id'    => $insumo_id,
                        'cantidad_solicitada' => $cantidad,
                        'precio_unitario'     => $producto->venta_usd,
                    ];
                }
            }

            // 4. Guardar cabecera del pedido
            $pedido->total = $totalAcumulado;
            
            // Lógica de cambio de estado según tu Workflow (Total vs Mínimo)
            $pedido->estado = ($totalAcumulado >= $lista->monto_minimo) ? 'APROBADO' : 'PENDIENTE';
            $pedido->save();

            // 5. Sincronizar detalles (Borramos los anteriores para evitar duplicados)
            $pedido->detalles()->delete();
            
            foreach ($detallesParaGuardar as $id => $data) {
                // Usamos create() a través de la relación para que asigne automáticamente el pedido_id
                $pedido->detalles()->create([
                    'insumos_mayores_id'    => $id,
                    'cantidad_solicitada' => $data['cantidad_solicitada'],
                    'cantidad_despachada' => 0,
                    'precio_unitario'     => $data['precio_unitario'],
                ]);
            }

            DB::commit();

            $mensaje = ($pedido->estado == 'APROBADO') 
                ? 'Pedido aprobado y listo para procesamiento.' 
                : 'Pedido guardado como PENDIENTE (no alcanza el mínimo de ' . number_format($lista->monto_minimo, 2) . ' $).';

            return redirect()->route('insumos-mayores.listas')->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            // Esto es vital para saber POR QUÉ dio error 500
            \Log::error("Error crítico en guardarPedido: " . $e->getMessage());
            return back()->with('error', 'Error al procesar el pedido: ' . $e->getMessage());
        }
    }

    public function misPedidos()
    {
        // Obtenemos los pedidos del cliente con sus detalles, ordenados por los más recientes
        $pedidos = Pedido::where('user_id', auth()->id())
            ->with(['listaOferta']) // Para mostrar el nombre de la oferta
            ->orderBy('created_at', 'desc')
            ->get();

        return view('insumos_mayores.mis_pedidos', compact('pedidos'));
    }

    public function show($id)
    {
        // Usamos findOrFail para lanzar un error 404 si el ID no existe.
        // Además, filtramos obligatoriamente por user_id para evitar que 
        // un cliente vea pedidos de otro cambiando el ID en la URL.
        $pedido = Pedido::where('user_id', auth()->id())
            ->with([
                'detalles.producto', // Para mostrar la descripción del insumo
                'listaOferta'        // Para mostrar datos de la oferta original
            ])
            ->findOrFail($id);

        return view('insumos_mayores.show', compact('pedido'));
    }

    // Carga la vista con los datos del pedido precargados
    public function editarPedido($id)
    {
        $pedido = Pedido::with('detalles')->findOrFail($id);

        // Validación de seguridad: no editar si está en preparación o más allá
        if (!in_array($pedido->estado, ['PENDIENTE', 'APROBADO'])) {
            return back()->with('error', 'Este pedido ya no se puede editar.');
        }

        $lista = $pedido->listaOferta;
        $items = InsumosMayor::all(); // O tu consulta de items original

        // Pasamos el objeto $pedido completo para que la vista lo use
        return view('insumos_mayores.listas_items_edit', compact('pedido', 'lista', 'items'));
    }

    // Procesa el guardado de los cambios
    public function actualizarPedido(Request $request, $id)
    {
        // 1. Validaciones estrictas
        $request->validate([
            'lista_id'     => 'required|exists:listas_ofertas,id',
            'cantidades'   => 'required|array',
            'cantidades.*' => 'integer|min:0',
        ]);

        $pedido = Pedido::findOrFail($id);
        $lista  = ListasOferta::findOrFail($request->lista_id);

        // Seguridad: Evitar edición si el pedido ya fue tomado por el Admin
        if (!in_array($pedido->estado, ['PENDIENTE', 'APROBADO'])) {
            return back()->with('error', 'Este pedido no puede ser modificado en su estado actual.');
        }

        DB::beginTransaction();

        try {
            $totalAcumulado = 0;
            $detallesParaGuardar = [];

            // 2. Procesar ítems y calcular total
            foreach ($request->cantidades as $insumo_id => $cantidad) {
                if ($cantidad > 0) {
                    // Buscamos el producto para obtener su precio actual de venta
                    $producto = InsumosMayor::findOrFail($insumo_id);
                    $subtotal = $producto->venta_usd * $cantidad;
                    $totalAcumulado += $subtotal;

                    $detallesParaGuardar[$insumo_id] = [
                        'insumos_mayores_id'  => $insumo_id, // Coherente con tu migración
                        'cantidad_solicitada' => $cantidad,
                        'cantidad_despachada' => 0,
                        'precio_unitario'     => $producto->venta_usd,
                    ];
                }
            }

            // 3. Actualizar Cabecera
            $pedido->total = $totalAcumulado;
            $pedido->estado = ($totalAcumulado >= $lista->monto_minimo) ? 'APROBADO' : 'PENDIENTE';
            $pedido->save();

            // 4. Sincronizar: Borramos lo anterior y creamos lo nuevo
            $pedido->detalles()->delete();
            
            foreach ($detallesParaGuardar as $detalle) {
                $pedido->detalles()->create($detalle);
            }

            DB::commit();

            $mensaje = ($pedido->estado == 'APROBADO') 
                ? 'Pedido actualizado y APROBADO.' 
                : 'Pedido actualizado como PENDIENTE (no alcanza el mínimo de ' . number_format($lista->monto_minimo, 2) . ' $).';

            return redirect()->route('pedidos.mis_pedidos')->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error al actualizar pedido ID {$id}: " . $e->getMessage());
            return back()->with('error', 'Error crítico al procesar la actualización: ' . $e->getMessage());
        }
    }

    public function cancelarPedidoCliente($id)
    {
        // 1. Buscamos el pedido asegurando que pertenezca al usuario autenticado
        $pedido = Pedido::where('user_id', auth()->id())->findOrFail($id);

        // 2. Doble validación de seguridad (Servidor)
        if (!in_array($pedido->estado, ['PENDIENTE', 'APROBADO'])) {
            return back()->with('error', 'El pedido ya está en preparación o enviado y no puede cancelarse.');
        }

        try {
            DB::beginTransaction();

            // 3. Cambiamos el estado
            $pedido->estado = 'CANCELADO';
            $pedido->save();

            DB::commit();

            return redirect()->route('pedidos.mis_pedidos')
                ->with('success', 'Pedido #' . str_pad($pedido->id, 5, '0', STR_PAD_LEFT) . ' cancelado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Hubo un problema al cancelar el pedido. Inténtalo de nuevo.');
        }
    }
    public function cancelarPedidoAdmin($id)
    {
        if (!auth()->user()->esAdmin()) {
            return redirect()->back()->with('error', 'No tienes permiso.');
        }
        $pedido = Pedido::findOrFail($id);

        // El admin puede hasta "EN PREPARACIÓN", pero no más allá
        if (in_array($pedido->estado, ['ENVIADO', 'ENTREGADO'])) {
            return back()->with('error', 'No se puede cancelar un pedido que ya ha sido enviado o entregado.');
        }

        $pedido->update([
            'estado' => 'CANCELADO',
            'observaciones' => 'Cancelado por la administración.'
        ]);
        return back()->with('success', 'Pedido cancelado por la administración.');
    }

    
    public function gestionOfertas()
    {
        // Traemos TODAS las listas
        $listas = ListasOferta::withCount(['pedidos' => function($query) {
            // Solo contamos los que son un "obstáculo" para editar
            $query->whereIn('estado', ['PENDIENTE', 'APROBADO', 'EN PREPARACIÓN']);
        }])
        ->orderBy('created_at', 'desc')
        ->get();

        return view('insumos_mayores.gestion', compact('listas'));
    }

    public function editarLista($id)
    {
        $lista = ListasOferta::withCount(['pedidos' => function($query) {
            $query->whereIn('estado', ['PENDIENTE', 'APROBADO', 'EN PREPARACIÓN']);
        }])->findOrFail($id);

        // Mantenemos la variable original para que tu vista no se rompa
        $tienePedidos = $lista->pedidos_count > 0;

        // Agregamos la nueva lógica de bloqueo de fechas como una variable extra
        $bloqueoFechas = ($lista->fecha_inicio <= now()->format('Y-m-d'));

        return view('insumos_mayores.edit', compact('lista', 'tienePedidos', 'bloqueoFechas'));
    }

    public function actualizarLista(Request $request, $id)
    {
        $lista = ListasOferta::withCount(['pedidos' => function($query) {
            $query->whereIn('estado', ['PENDIENTE', 'APROBADO', 'EN PREPARACIÓN']);
        }])->findOrFail($id);

        $tienePedidos = $lista->pedidos_count > 0;

        // 1. Validación de Seguridad
        if ($tienePedidos && $request->hasFile('archivo')) {
            return back()->with('error', 'No puedes actualizar los productos de esta lista porque tiene pedidos activos.');
        }

        // 2. Transacción para integridad de datos
        DB::beginTransaction();
        try {
            // Actualizamos datos básicos (siempre permitidos)
            $lista->update($request->only(['nombre', 'proveedor', 'fecha_inicio', 'fecha_fin']));

            // Solo si no tiene pedidos, permitimos actualizar el archivo y los campos críticos
            if (!$tienePedidos) {
                $lista->update($request->only(['monto_minimo', 'incremento']));

                if ($request->hasFile('archivo')) {
                    // Eliminamos los registros antiguos antes de la nueva importación
                    $lista->items()->delete();
                    
                    // Procesamos el nuevo archivo con tu clase InsumosImport
                    \Excel::import(new InsumosImport($lista->id, $request->incremento), $request->file('archivo'));
                }
            }

            DB::commit();
            return redirect()->route('insumos-mayores.gestion')->with('success', 'Lista actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
    public function anularOferta($id)
    {
        // Usamos withCount para contar TODO (no solo pendientes)
        $lista = ListasOferta::withCount('pedidos')->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($lista->pedidos_count == 0) {
                // ESCENARIO 1: Limpieza Total (Sin pedidos en la historia)
                $lista->delete(); // Esto borrará la lista y sus productos (si tienes cascade)
                $mensaje = 'Oferta eliminada permanentemente por falta de actividad.';
            } else {
                // ESCENARIO 2: Anulación Lógica (Tiene historia, debemos auditar)
                
                // 1. Cancelar pedidos activos
                $lista->pedidos()
                    ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
                    ->update([
                        'estado' => 'CANCELADO',
                        'observaciones' => 'Cancelado automáticamente por anulación de oferta.'
                    ]);

                // 2. Marcar lista como ANULADA
                $lista->update(['estado' => 'ANULADA']);
                
                $mensaje = 'Oferta anulada y pedidos activos cancelados correctamente.';
            }

            DB::commit();
            return back()->with('success', $mensaje);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'No se pudo realizar la operación: ' . $e->getMessage());
        }
    }}

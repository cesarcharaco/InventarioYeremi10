<?php

namespace App\Http\Controllers;

use App\Models\InsumosMayor;
use Illuminate\Http\Request;
use App\Imports\InsumosImport;
use App\Models\ListasOferta;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Maatwebsite\Excel\Facades\Excel;

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
            $lista = \App\Models\ListasOferta::create([
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
            \Maatwebsite\Excel\Facades\Excel::import(new InsumosImport($lista->id,$request->incremento), $request->file('archivo'));

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
        $pedidoExistente = \App\Models\Pedido::with('detalles')
            ->where('user_id', auth()->id())
            ->where('listas_oferta_id', $id)
            ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
            ->first();

        return view('insumos_mayores.listas_items', compact('lista', 'items', 'pedidoExistente'));
    }

    public function guardarPedido(Request $request)
        {
            // 1. Validaciones estrictas
            $request->validate([
                'lista_id'   => 'required|exists:listas_ofertas,id',
                'cantidades' => 'required|array',
                'cantidades.*' => 'integer|min:0', // Evita valores negativos
            ]);

            $lista = ListasOferta::findOrFail($request->lista_id);
            
            // Iniciamos transacción para asegurar integridad total
            DB::beginTransaction();

            try {
                // 2. Buscar si el cliente ya tiene un pedido PENDIENTE o APROBADO para esta lista
                // Según nuestra lógica de "Borrador Único"
                $pedido = Pedido::where('user_id', Auth::id())
                    ->where('listas_oferta_id', $lista->id)
                    ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
                    ->first();

                if (!$pedido) {
                    $pedido = new Pedido();
                    $pedido->user_id = Auth::id();
                    $pedido->listas_oferta_id = $lista->id;
                    $pedido->estado = 'PENDIENTE';
                }

                // 3. Procesar los ítems y calcular total
                $totalAcumulado = 0;
                $detallesParaGuardar = [];

                foreach ($request->cantidades as $insumo_id => $cantidad) {
                    if ($cantidad > 0) {
                        $producto = InsumosMayor::findOrFail($insumo_id);
                        $subtotal = $producto->venta_usd * $cantidad;
                        $totalAcumulado += $subtotal;

                        $detallesParaGuardar[$insumo_id] = [
                            'cantidad_solicitada' => $cantidad,
                            'precio_unitario'     => $producto->venta_usd,
                        ];
                    }
                }

                // 4. Guardar cabecera del pedido para obtener ID
                $pedido->total = $totalAcumulado;
                
                // Lógica de cambio de estado automático
                $pedido->estado = ($totalAcumulado >= $lista->monto_minimo) ? 'APROBADO' : 'PENDIENTE';
                $pedido->save();

                // 5. Sincronizar detalles (Borramos los anteriores para evitar duplicados en el borrador)
                $pedido->detalles()->delete();
                
                foreach ($detallesParaGuardar as $id => $data) {
                    $pedido->detalles()->create([
                        'insumos_mayor_id'    => $id,
                        'cantidad_solicitada' => $data['cantidad_solicitada'],
                        'cantidad_despachada' => 0, // Aún no entra a almacén
                        'precio_unitario'     => $data['precio_unitario'],
                    ]);
                }

                DB::commit();

                $mensaje = ($pedido->estado == 'APROBADO') 
                    ? 'Pedido aprobado y listo para procesamiento.' 
                    : 'Pedido guardado, pero aún no alcanza el monto mínimo de ' . number_format($lista->monto_minimo, 2) . ' $.';

                return redirect()->route('insumos-mayores.listas')->with('success', $mensaje);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error crítico al procesar el pedido: ' . $e->getMessage());
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
}

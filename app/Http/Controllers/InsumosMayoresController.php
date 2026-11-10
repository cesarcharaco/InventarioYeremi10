<?php

namespace App\Http\Controllers;

use App\Models\InsumosMayor;
use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Local;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Imports\InsumosImport;
use App\Models\ListasOferta;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Cliente;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Services\BcvRateService;

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
    }

    /**
     * Muestra el formulario de filtros múltiples para generar la plantilla Excel de depósitos.
     */
    public function plantillaForm()
    {
        // Obtenemos solo los locales que son depósitos
        $depositos = Local::where('tipo', 'DEPOSITO')->where('estado', 'Activo')->get();
        
        // Obtenemos todas las categorías de repuestos
        $categorias = Categoria::all();

        return view('insumos_mayores.generar_plantilla', compact('depositos', 'categorias'));
    }

    public function exportarPlantilla(Request $request)
    {
        // 1. Validar selección múltiple de depósitos y categorías
        $request->validate([
            'depositos'    => 'required|array',
            'depositos.*'  => 'exists:local,id',
            'categorias'   => 'nullable|array',
            'categorias.*' => 'exists:categorias,id',
        ]);

        $depositosIds = $request->depositos;
        $categoriasIds = $request->categorias ?? [];

        // 2. Consultar insumos con stock positivo
        $query = Insumos::with([
            'categoria',
            'existencias' => function ($q) use ($depositosIds) {
                $q->whereIn('id_local', $depositosIds)
                    ->where('cantidad', '>', 0);
            },
        ])
            ->whereHas('existencias', function ($q) use ($depositosIds) {
                $q->whereIn('id_local', $depositosIds)
                    ->where('cantidad', '>', 0);
            });

        if (!empty($categoriasIds)) {
            $query->whereIn('categoria_id', $categoriasIds);
        }

        $insumos = $query->get();

        if ($insumos->isEmpty()) {
            return back()->with(
                'error',
                'No se encontraron productos con stock en los depósitos seleccionados.'
            );
        }

        // 3. Obtener la tasa BCV
        $tasaBcv = 80.00;

        try {
            $tasaBcv = app(BcvRateService::class)->getCurrentRate('USD') ?? 80.00;
        } catch (\Throwable $e) {
            // Mantener el valor por defecto
        }

        // 4. Construcción del Excel
        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();

        $ws->setTitle('Hoja1');
        $ws->setShowGridLines(true);

        $borderThin = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'argb' => 'FFCBD5E1',
                    ],
                ],
            ],
        ];

        // Cabecera institucional: filas 3 a 9
        $ws->setCellValue(
            'B3',
            'YERMOTORS REPUESTOS C.A. Venta de Lubricantes y Repuestos para Motos - TELF:04140863107/04163031901'
        );

        $ws->getStyle('B3')
            ->getFont()
            ->setBold(true)
            ->setSize(11)
            ->setColor(new Color('FF1A365D'));

        $ws->setCellValue('I3', 'RIF:J501868034');

        $ws->getStyle('I3')
            ->getFont()
            ->setBold(true)
            ->setSize(11);

        $ws->setCellValue('A4', 'CLIENTE:');
        $ws->setCellValue('G4', 'C.I./RIF:');

        $ws->setCellValue('A5', 'DIRECCIÓN:');

        $ws->setCellValue('A6', 'TLF:');
        $ws->setCellValue('G6', 'WHATSAPP:');

        // Condiciones comerciales
        $ws->setCellValue('B7', 'MONTO MÍNIMO DE PEDIDO 200$');

        $ws->getStyle('B7')
            ->getFont()
            ->setBold(true)
            ->setColor(new Color('FF8B0000'));

        $ws->setCellValue('F7', 'EN DIVISAS 20% DE DESCUENTO');

        $ws->getStyle('F7')
            ->getFont()
            ->setBold(true)
            ->setColor(new Color('FF1A365D'));

        $ws->setCellValue('I7', 'TOTAL NETO');
        $ws->getStyle('I7')->getFont()->setBold(true);

        $ws->setCellValue('B8', 'ORDEN DE PEDIDO');

        $ws->getStyle('B8')
            ->getFont()
            ->setBold(true)
            ->setSize(12)
            ->setColor(new Color('FF1A365D'));

        $lastRow = 10 + count($insumos);

        $ws->setCellValue('I8', '=SUM(I11:I' . $lastRow . ')');

        $ws->getStyle('I8')
            ->getFont()
            ->setBold(true)
            ->setSize(12)
            ->setColor(new Color('FF8B0000'));

        $ws->getStyle('I8')
            ->getNumberFormat()
            ->setFormatCode('$#,##0.00');

        $ws->setCellValue('H9', 'TASA BCV');
        $ws->getStyle('H9')->getFont()->setBold(true);

        $ws->setCellValue('I9', $tasaBcv);
        $ws->getStyle('I9')->getFont()->setBold(true);

        $ws->getStyle('I9')
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Fila 10: encabezados de la tabla
        $headers = [
            'CÓDIGO',
            'PRODUCTO',
            '',
            '',
            'PRECIO',
            'CANTIDAD',
            'BS',
            'BCV',
            'DIVISAS',
        ];

        for ($colIdx = 1; $colIdx <= count($headers); $colIdx++) {
            $cell = $ws->getCellByColumnAndRow($colIdx, 10);
            $cell->setValue($headers[$colIdx - 1]);

            $ws->getStyleByColumnAndRow($colIdx, 10)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF1A365D',
                    ],
                ],
                'font' => [
                    'name' => 'Arial',
                    'size' => 11,
                    'bold' => true,
                    'color' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $ws->mergeCells('B10:D10');

        // 5. Poblar productos desde la fila 11
        $startRow = 11;

        foreach ($insumos as $index => $insumo) {
            $i = $startRow + $index;

            $ws->setCellValue("A{$i}", (string) $insumo->serial);

            $ws->getStyle("A{$i}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $descripcionCompleta = trim(
                $insumo->producto
                . ($insumo->descripcion ? ' ' . $insumo->descripcion : '')
            );

            $ws->setCellValue("B{$i}", $descripcionCompleta);
            $ws->mergeCells("B{$i}:D{$i}");

            $ws->getStyle("B{$i}:D{$i}")
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Precio unitario base
            $ws->setCellValue("E{$i}", (float) $insumo->costo);

            $ws->getStyle("E{$i}")
                ->getNumberFormat()
                ->setFormatCode('$#,##0.00');

            // Cantidad vacía
            $ws->setCellValue("F{$i}", '');

            $ws->getStyle("F{$i}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Fórmulas de cálculo
            $ws->setCellValue("G{$i}", "=E{$i}*F{$i}*I\$9");

            $ws->getStyle("G{$i}")
                ->getNumberFormat()
                ->setFormatCode('Bs #,##0.00');

            $ws->setCellValue("H{$i}", "=E{$i}*F{$i}");

            $ws->getStyle("H{$i}")
                ->getNumberFormat()
                ->setFormatCode('$#,##0.00');

            $ws->setCellValue(
                "I{$i}",
                "=IF(H{$i}>0,H{$i}-H{$i}*20%,0)"
            );

            $ws->getStyle("I{$i}")
                ->getNumberFormat()
                ->setFormatCode('$#,##0.00');

            // Bordes
            for ($col = 1; $col <= 9; $col++) {
                $ws->getStyleByColumnAndRow($col, $i)
                    ->applyFromArray($borderThin);
            }
        }

        // 6. Definir los anchos antes de calcular el tamaño del logotipo
        $columnWidths = [
            'A' => 16,
            'B' => 22,
            'C' => 15,
            'D' => 15,
            'E' => 14,
            'F' => 14,
            'G' => 18,
            'H' => 14,
            'I' => 16,
        ];

        foreach ($columnWidths as $colLetter => $width) {
            $ws->getColumnDimension($colLetter)->setWidth($width);
        }

        // 7. Combinar A1:I2 y reservar espacio para el logotipo
        $ws->mergeCells('A1:I2');

        $ws->getRowDimension(1)->setRowHeight(30);
        $ws->getRowDimension(2)->setRowHeight(30);

        $logoPath = public_path('images/logo_reporte.png');

        // Validar antes de enviar los encabezados de descarga
        if (!is_file($logoPath) || !is_readable($logoPath)) {
            return back()->with(
                'error',
                'No se encontró el logotipo o no se puede leer: images/logo_reporte.png'
            );
        }

        $imageInfo = @getimagesize($logoPath);

        if (
            $imageInfo === false
            || $imageInfo[0] <= 0
            || $imageInfo[1] <= 0
        ) {
            return back()->with(
                'error',
                'El archivo images/logo_reporte.png no es una imagen válida.'
            );
        }

        // Ancho del área A:I en píxeles
        $defaultFont = $spreadsheet->getDefaultStyle()->getFont();
        $areaWidthPx = 0;

        foreach (array_keys($columnWidths) as $columnLetter) {
            $columnWidth = $ws->getColumnDimension($columnLetter)->getWidth();

            $areaWidthPx +=
                \PhpOffice\PhpSpreadsheet\Shared\Drawing::cellDimensionToPixels(
                    $columnWidth,
                    $defaultFont
                );
        }

        // Altura del área: filas 1 y 2
        $areaHeightPx =
            \PhpOffice\PhpSpreadsheet\Shared\Drawing::pointsToPixels(
                $ws->getRowDimension(1)->getRowHeight()
            )
            +
            \PhpOffice\PhpSpreadsheet\Shared\Drawing::pointsToPixels(
                $ws->getRowDimension(2)->getRowHeight()
            );

        // Margen interior
        $paddingPx = 5;

        $availableWidthPx = max(
            1,
            $areaWidthPx - (2 * $paddingPx)
        );

        $availableHeightPx = max(
            1,
            $areaHeightPx - (2 * $paddingPx)
        );

        $originalWidthPx = $imageInfo[0];
        $originalHeightPx = $imageInfo[1];

        // Ajustar sin deformar ni exceder el espacio disponible
        $scale = min(
            $availableWidthPx / $originalWidthPx,
            $availableHeightPx / $originalHeightPx
        );

        $imageWidthPx = max(
            1,
            (int) floor($originalWidthPx * $scale)
        );

        $imageHeightPx = max(
            1,
            (int) floor($originalHeightPx * $scale)
        );

        // Centrar horizontal y verticalmente
        $offsetXPx = (int) floor(
            ($areaWidthPx - $imageWidthPx) / 2
        );

        $offsetYPx = (int) floor(
            ($areaHeightPx - $imageHeightPx) / 2
        );

        $drawing = new Drawing();
        $drawing->setName('Logo Yermotors');
        $drawing->setDescription('Logo institucional en A1:I2');
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('A1');

        // Las dimensiones proporcionales ya fueron calculadas
        $drawing->setResizeProportional(false);
        $drawing->setWidth($imageWidthPx);
        $drawing->setHeight($imageHeightPx);

        $drawing->setOffsetX($offsetXPx);
        $drawing->setOffsetY($offsetYPx);
        $drawing->setWorksheet($ws);

        // 8. Descarga directa
        $fileName = 'PedidosAlMayor_' . date('Y-m-d') . '.xlsx';

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment;filename="' . $fileName . '"'
        );

        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        exit;
    }
}

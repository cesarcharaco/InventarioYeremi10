<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InsumosController;
use App\Http\Controllers\IncidenciasController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ModeloVentaController;
use App\Http\Controllers\DespachoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\VentaController;
use App\Models\AutorizacionPin;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ConfigOfertaController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\InsumosMayoresController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CorrelativoController;
/*
|-------------------------------------------------------------------------- 
| Web Routes
|-------------------------------------------------------------------------- 
*/

Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes();

Route::get('/registro-cliente', [ClienteController::class, 'create'])->name('clientes.create');
Route::post('/registro-cliente', [ClienteController::class, 'store'])->name('clientes.store');

Route::middleware(['auth'])->group(function () {
    Route::prefix('config-ofertas')->group(function () {
        Route::get('/', [ConfigOfertaController::class, 'index'])->name('config-ofertas.index');
        Route::post('/store', [ConfigOfertaController::class, 'store'])->name('config-ofertas.store');
        Route::patch('/{id}/desactivar', [ConfigOfertaController::class, 'desactivar'])->name('config-ofertas.desactivar');
    });
    Route::get('/debug-bcv', function () {
        try {
            $rate = app(\App\Services\BcvRateService::class)->getCurrentRate('USD');
            dd($rate);
        } catch (\Throwable $e) {
            dd($e->getMessage());
        }
    });
    Route::get('/pines-activos-ajax', [HomeController::class, 'getPinesAjax'])->name('admin.pines_activos');

    // Rutas de Usuarios y Perfil Personal
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios/guardar', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}/actualizar', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}/eliminar', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::get('/usuarios/{id}/show', [UserController::class, 'show'])->name('usuarios.show');

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // 1. Rutas específicas y de utilidad para Insumos
    Route::prefix('inventario/insumos')->group(function () {
        Route::get('data', [InsumosController::class, 'getInsumosData'])->name('insumos.data');
        Route::get('precios', [InsumosController::class, 'precios'])->name('insumos.precios');
        Route::post('actualizar-costo', [InsumosController::class, 'actualizarCosto'])->name('insumos.actualizarCosto');
        Route::post('destroy-manual', [InsumosController::class, 'destroy'])->name('insumos.destroy_manual');
        Route::get('/local/{id}', [InsumosController::class, 'listarPorLocal'])->name('inventario.local');

        Route::post('cambiar-estado', [InsumosController::class, 'cambiarEstadoInsumo'])->name('insumo.cambiarEstado');
        Route::get('{id}/barcode-pdf', [InsumosController::class, 'generarCodigoBarrasPdf'])->name('insumos.barcode_pdf');

        // Vista del Carrito de Etiquetas
        Route::get('etiquetas', [InsumosController::class, 'etiquetasView'])->name('insumos.etiquetas');
        // Búsqueda AJAX para el autocompletado del buscador
        Route::get('buscar-ajax', [InsumosController::class, 'buscarInsumosAjax'])->name('insumos.buscar_ajax');
        // Generación del PDF Múltiple
        Route::post('barcode-pdf-multiple', [InsumosController::class, 'generarCodigosBarrasPdfMultiple'])->name('insumos.barcode_pdf_multiple');
    });

    // 3. Resource estándar de Insumos
    Route::post('insumos/importar-oferta', [InsumosController::class, 'importar'])->name('insumos.importar');
    Route::post('/insumos/store-rapido', [InsumosController::class, 'storeRapido'])->name('insumos.store_rapido');
    Route::resource('insumos', InsumosController::class);

    // Grupo de rutas para Despachos
    Route::group(['prefix' => 'despacho'], function () {
        // 1. Rutas principales estáticas (siempre arriba)
        Route::get('/', [DespachoController::class, 'index'])->name('despacho.index');
        Route::get('/create', [DespachoController::class, 'create'])->name('despacho.create');
        Route::post('/store', [DespachoController::class, 'store'])->name('despacho.store');

        // 2. Rutas específicas con sub-parámetros (antes del show /{id})
        Route::get('/{id}/json', [DespachoController::class, 'getJson'])->name('despacho.json'); // <--- NUEVA: Para poblar el modal
        Route::get('/{id}/edit', [DespachoController::class, 'edit'])->name('despacho.edit');

        // 3. Ruta genérica de visualización (después de las específicas)
        Route::get('/{id}', [DespachoController::class, 'show'])->name('despacho.show');
        
        // 4. Acciones por POST / DELETE
        Route::post('/confirmar/{id}', [DespachoController::class, 'confirmarRecepcion'])->name('despacho.confirmar'); // <--- Esta ya la tenías
        Route::post('/anular/{id}', [DespachoController::class, 'anular'])->name('despacho.anular');
        Route::delete('/{id}', [DespachoController::class, 'destroy'])->name('despacho.destroy');
    });
    

    // --- SECCIÓN DE INCIDENCIAS ---
    Route::get('/incidencias/historial', [IncidenciasController::class, 'historial'])->name('incidencias.historial');
    Route::get('/incidencias/{id_incidencia}/detalles_historial', [IncidenciasController::class, 'detalles_historial'])->name('incidencias.historial_detalles');
    Route::post('/incidencias/deshacer', [IncidenciasController::class, 'deshacer_incidencia'])->name('deshacer_incidencia');
    Route::resource('incidencias', IncidenciasController::class);

        
    Route::post('local/cambiar_status', [LocalController::class, 'cambiar_estado'])->name('local.cambiar_estado');
    Route::resource('local', LocalController::class);
    Route::get('/locales/{id}/vendedores', [CajaController::class, 'getVendedoresPorLocal'])->name('locales.vendedores');
    Route::resource('categorias', CategoriaController::class);
    Route::resource('modelos-venta', ModeloVentaController::class);
    Route::get('api/modelo-datos/{id}', [ModeloVentaController::class, 'getDatos']);

    // --- MÓDULO DE CLIENTES ---
    Route::post('/clientes-ajax', [ClienteController::class, 'storeAjax'])->name('clientes.store_ajax');
    Route::post('clientes/store-rapido', [ClienteController::class, 'storeRapido'])->name('clientes.storeRapido');
    Route::get('/clientes/{id}/verificar-deuda', [ClienteController::class, 'getDeudaPendiente'])->name('clientes.deuda');
    Route::get('clientes/pendientes', [ClienteController::class, 'listaActivar'])->name('clientes.pendientes');
    Route::patch('clientes/{id}/activar', [ClienteController::class, 'activar'])->name('clientes.activar');
    Route::resource('clientes', ClienteController::class)->except(['create', 'store']);

    // --- GESTIÓN DE CAJA ---
    Route::resource('cajas', CajaController::class)->only(['create', 'store', 'edit', 'update']);
    Route::get('cajas/historial', [CajaController::class, 'index'])->name('cajas.index'); 
    
    Route::middleware(['auth'])->prefix('movimientos-caja')->group(function () {
        Route::get('/', [MovimientoCajaController::class, 'index'])->name('movimientos.index');
        Route::get('/create', [MovimientoCajaController::class, 'create'])->name('movimientos.create');
        Route::post('/store', [MovimientoCajaController::class, 'store'])->name('movimientos.store');
        Route::put('/{id}/update', [MovimientoCajaController::class, 'update'])->name('movimientos.update');
        Route::delete('/{id}/destroy', [MovimientoCajaController::class, 'destroy'])->name('movimientos.destroy');
    });

    Route::post('cajas/anular/{id}', [CajaController::class, 'anular'])
        ->name('cajas.anular')
        ->middleware('can:auditar-cajas'); 

    // --- MÓDULO DE VENTAS (Protegido por Caja Abierta) ---
    Route::middleware(['caja.abierta'])->group(function () {
        Route::get('ventas/{id}/ticket', [VentaController::class, 'imprimirTicket'])->name('ventas.ticket');
        Route::get('api/insumos/{id}/precio', [VentaController::class, 'getPrecioInsumo']);
        Route::post('/ventas/solicitar-pin', [VentaController::class, 'solicitarPin'])->name('ventas.solicitar_pin');
        Route::post('/ventas/verificar-pin', [VentaController::class, 'verificarPin'])->name('ventas.verificar_pin');
        Route::post('/ventas/presupuesto', [VentaController::class, 'generarPresupuesto'])->name('ventas.presupuesto');
        Route::resource('ventas', VentaController::class);
        Route::get('/ventas/proximo-correlativo-nota', [VentaController::class, 'getProximoCorrelativo'])->name('ventas.correlativo');
    });

  // --- MÓDULO DE CRÉDITOS ---
  Route::prefix('creditos')->group(function () {
      Route::get('/', [CreditoController::class, 'index'])->name('creditos.index');
      Route::post('/directo-general', [CreditoController::class, 'storeDirectoGeneral'])->name('creditos.directo.store_general');
      
      // 1. Rutas GET específicas (Deben ir ANTES de /{id})
      Route::get('/{id}/modal-interes', [CreditoController::class, 'modalInteres'])->name('creditos.modalInteres');
      Route::get('/{id}/historial-fechas', [CreditoController::class, 'historialPorFecha'])->name('creditos.historial_crediticio');
      Route::get('/{id}/historial', [CreditoController::class, 'historial'])->name('creditos.historial');
      Route::get('/{id}/productos', [CreditoController::class, 'listarProductos'])->name('creditos.productos');
      Route::get('/pdf-estado-cuenta/{cliente_id}', [CreditoController::class, 'pdfEstadoCuenta'])->name('creditos.pdf_estado_cuenta');

      // 2. Rutas POST
      Route::get('/abonos/{id}/editar', [CreditoController::class, 'editAbono'])->name('abonos.edit');
      Route::put('/abonos/{id}', [CreditoController::class, 'updateAbono'])->name('abonos.update');
      Route::post('/{id}/abono', [CreditoController::class, 'registrarAbono'])->name('creditos.abono');
      Route::post('/{id}/revalorizar', [CreditoController::class, 'revalorizar'])->name('creditos.revalorizar');
      Route::post('/abono/{id}/anular', [CreditoController::class, 'anularAbono'])->name('abonos.anular');
      Route::post('/{id}/aplicar-interes', [CreditoController::class, 'aplicarInteres'])->name('creditos.aplicarInteres');
      Route::post('/interes/{id}/anular', [CreditoController::class, 'anularInteres'])->name('creditos.interes.anular');
      Route::post('/cliente/{id_cliente}/procesar-reembolso', [CreditoController::class, 'procesarReembolso'])->name('creditos.procesarReembolso');
      Route::post('/cliente/{id}/directo', [CreditoController::class, 'storeDirecto'])->name('creditos.directo.store');

      // 3. Rutas comodín genéricas (Al final)
      Route::get('/{id}', [CreditoController::class, 'show'])->name('creditos.show');
      Route::delete('/{id}', [CreditoController::class, 'destroy'])->name('creditos.destroy');
  });

  
  
    // --- MÓDULO DE PROVEEDORES ---
    Route::prefix('proveedores')->group(function () {
        Route::get('/', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('/crear', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::post('/guardar', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::get('/{id}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::post('/{id}/actualizar', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/{id}/eliminar', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    });

    // --- ENTRADAS ---
    Route::prefix('entradas')->group(function () {
        Route::get('/', [EntradaController::class, 'index'])->name('entradas.index');
        Route::get('/crear', [EntradaController::class, 'create'])->name('entradas.create');
        Route::post('/guardar', [EntradaController::class, 'store'])->name('entradas.store');
        
        // Nueva ruta para la bandeja de pendientes de recepción (Almacén X10)
        Route::get('/recepcion/pendientes', [EntradaController::class, 'pendientesRecepcion'])->name('entradas.recepcion');
        // Nueva ruta para procesar/aprobar un ítem o lote de recepción
        Route::post('/recepcion/{id}/procesar', [EntradaController::class, 'procesarRecepcion'])->name('entradas.procesar');
        // Ruta para revertir una recepción fraccionada y unificar de nuevo
        Route::delete('/recepcion/revertir/{id}', [EntradaController::class, 'revertirRecepcion'])->name('entradas.revertir');

        Route::get('/{id}', [EntradaController::class, 'show'])->name('entradas.show');
        Route::delete('/{id}/anular', [EntradaController::class, 'destroy'])->name('entradas.anular');
    });
    
    Route::get('generar_reporte', [ReportesController::class, 'store'])->name('generar_reporte');
    Route::resource('reportes', ReportesController::class);

    Route::get('graficas', function () {
        return view('graficas.index');
    });

    // --- MAYORISTA ---
    Route::prefix('mayorista')->group(function () {
        Route::get('/', [InsumosMayoresController::class, 'index'])->name('insumos-mayores.index');
        Route::get('/listas', [InsumosMayoresController::class, 'listarOfertas'])->name('insumos-mayores.listas');
        Route::get('/items/{id}', [InsumosMayoresController::class, 'verItems'])->name('insumos-mayores.items');
        Route::post('/pedido/guardar', [InsumosMayoresController::class, 'guardarPedido'])->name('pedidos.store');
        Route::get('/mis-pedidos', [InsumosMayoresController::class, 'misPedidos'])->name('pedidos.mis_pedidos');
        Route::get('/pedido/detalle/{id}', [InsumosMayoresController::class, 'show'])->name('pedidos.show');
        Route::get('/pedido/{id}/editar', [InsumosMayoresController::class, 'editarPedido'])->name('pedidos.editar');
        Route::get('/cargar-oferta', [InsumosMayoresController::class, 'createImport'])->name('insumos-mayores.formulario');
        Route::post('/ofertas/importar', [InsumosMayoresController::class, 'importar'])->name('insumos-mayores.importar');
        Route::put('/pedido/{id}/actualizar', [InsumosMayoresController::class, 'actualizarPedido'])->name('pedidos.actualizar');
        Route::patch('/pedido/{id}/cancelar', [InsumosMayoresController::class, 'cancelarPedidoCliente'])->name('pedidos.cancelar.cliente');
    });

    // --- GRUPO ADMIN ---
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::patch('/pedido/{id}/cancelar-admin', [InsumosMayoresController::class, 'cancelarPedidoAdmin'])->name('pedidos.cancelar.admin');
        Route::get('/ofertas/gestion', [InsumosMayoresController::class, 'gestionOfertas'])->name('insumos-mayores.gestion');
        Route::get('/ofertas/{id}/editar', [InsumosMayoresController::class, 'editarLista'])->name('insumos-mayores.editar');
        Route::put('/ofertas/{id}/actualizar', [InsumosMayoresController::class, 'actualizarLista'])->name('insumos-mayores.actualizar');
        Route::delete('/ofertas/{id}/anular', [InsumosMayoresController::class, 'anularOferta'])->name('insumos-mayores.anular');
    });

    Route::get('/forzar-tasa', function() {
        \App\Services\TasaCambioService::actualizarTodasLasTasas();
        return "Tasa actualizada manualmente";
    });

    // --- NOTIFICACIONES ---
    Route::get('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::get('/notifications/all', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');



    

    Route::middleware(['auth'])->group(function () {
        Route::resource('correlativos', CorrelativoController::class);
    });
});
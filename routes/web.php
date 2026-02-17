<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SolicitantesController;
use App\Http\Controllers\InsumosController;
use App\Http\Controllers\PrestamosController;
use App\Http\Controllers\IncidenciasController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ModeloVentaController;
use App\Http\Controllers\DespachoController;
/*
|--------------------------------------------------------------------------  
| Web Routes
|-------------------------------------------------------------------------- 
*/



/*Route::get('/', function () {
    return view('auth.login');
});*/
Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes();
// Forzar el nombre si algo lo está pisando
//Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

/*Route::get('/login', [LoginController::class, 'show'])->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');*/

// 1. Rutas específicas y de utilidad para Insumos (Agrupadas para mayor orden)
Route::prefix('inventario/insumos')->group(function () {
    Route::get('precios', [InsumosController::class, 'precios'])->name('insumos.precios');
    Route::post('actualizar-costo', [InsumosController::class, 'actualizarCosto'])->name('insumos.actualizarCosto');
    Route::post('destroy-manual', [InsumosController::class, 'destroy'])->name('insumos.destroy_manual');
    Route::get('/local/{id}', [InsumosController::class, 'listarPorLocal'])->name('inventario.local');// la fpuedo agregar aqui así
});

// 2. Rutas de integración con otros módulos (Préstamos)
// Se mantienen fuera del prefijo porque ya tienen su propia estructura de parámetros
Route::get('insumos/{id_gerencia}/buscar', [PrestamosController::class, 'buscar_insumos']);
Route::get('insumos/{id_insumo}/buscar_existencia', [PrestamosController::class, 'buscar_existencia']);
Route::post('/insumo/cambiar-estado', [InsumosController::class, 'cambiarEstadoInsumo'])->name('insumo.cambiarEstado');
// 3. Resource estándar de Insumos
// Al estar al final, no interfiere con 'precios' ni 'actualizar-costo'
Route::resource('insumos', InsumosController::class);

// Grupo de rutas para Despachos
Route::group(['prefix' => 'despacho'], function () {
        
        // Listado de despachos (Index)
        Route::get('/', [DespachoController::class, 'index'])->name('despacho.index');
        
        // Formulario de creación (Nueva Salida/Despacho)
        Route::get('/create', [DespachoController::class, 'create'])->name('despacho.create');
        
        // Acción de guardar el despacho y afectar el stock
        Route::post('/store', [DespachoController::class, 'store'])->name('despacho.store');
        
        // Ver el detalle de un despacho específico (Importante para auditoría)
        Route::get('/{id}', [DespachoController::class, 'show'])->name('despacho.show');

        // Confirmar recepción (Para cuando la tienda B recibe la mercancía)
        Route::post('/confirmar/{id}', [DespachoController::class, 'confirmarRecepcion'])->name('despacho.confirmar');

        // Opcional: Ruta para anular despacho (si fuera necesario antes de ser recibido)
        Route::post('/anular/{id}', [DespachoController::class, 'anular'])->name('despacho.anular');

        Route::post('/confirmar/{id}', [DespachoController::class, 'confirmar'])->name('despacho.confirmar');

        // Eliminar registro de despacho
        Route::delete('/{id}', [DespachoController::class, 'destroy'])->name('despacho.destroy');

        // Formulario de edición
        Route::get('/{id}/edit', [DespachoController::class, 'edit'])->name('despacho.edit');


    });
Route::resource('solicitantes', SolicitantesController::class);
Route::post('solicitantes/cambiar_status', [SolicitantesController::class, 'cambiar_status'])->name('solicitantes.cambiar_status');
Route::post('prestamos/cambiar_status', [PrestamosController::class, 'cambiar_status'])->name('prestamos.cambiar_status');
Route::get('/prestamos/historial', [PrestamosController::class, 'historial'])->name('prestamos.historial');
Route::post('prestamos/deshacer', [PrestamosController::class, 'deshacer_prestamo'])->name('prestamos.deshacer');
Route::resource('prestamos', PrestamosController::class);


// --- SECCIÓN DE INCIDENCIAS (AUDITABLES) ---

// 1. Historial y Detalles (Lectura)
Route::get('/incidencias/historial', [IncidenciasController::class, 'historial'])->name('incidencias.historial');
Route::get('/incidencias/{id_incidencia}/detalles_historial', [IncidenciasController::class, 'detalles_historial'])->name('incidencias.historial_detalles');

// 2. Acción de Reversión (Anulación profesional)
// Esta es la ruta que llama el botón "Anular" de tu tabla historial
Route::post('/incidencias/deshacer', [IncidenciasController::class, 'deshacer_incidencia'])->name('deshacer_incidencia');

// 3. Resource estándar (CRUD)
// Nota: El método destroy aquí registrará el snapshot antes de borrar
Route::resource('incidencias', IncidenciasController::class);

// --- FIN SECCIÓN INCIDENCIAS ---

Route::get('/salidas/{id_local}/listar', [SalidaController::class, 'index'])->name('salidas.listar');
Route::get('/salidas/index2', [SalidaController::class, 'index2'])->name('salidas.index2');
Route::get('/salidas/seleccionar_local', [SalidaController::class, 'seleccionar_local'])->name('seleccionar_local');
Route::post('/salidas/create2', [SalidaController::class, 'create2'])->name('salidas.create2');
Route::get('/salidas/{id_local}/createl', [SalidaController::class, 'create3'])->name('salidas.createl');
Route::resource('salidas', SalidaController::class);
Route::post('local/cambiar_status', [LocalController::class, 'cambiar_estado'])->name('local.cambiar_estado');
Route::resource('local', LocalController::class);

Route::resource('categorias', CategoriaController::class);
Route::resource('modelos-venta', ModeloVentaController::class);
// Ruta extra que necesitaremos para el cálculo "instantáneo" más adelante
Route::get('api/modelo-datos/{id}', [App\Http\Controllers\ModeloVentaController::class, 'getDatos']);



Route::get('generar_reporte', [ReportesController::class, 'store']);
Route::get('generar_reporte', [ReportesController::class, 'store'])->name('generar_reporte');
Route::resource('reportes', ReportesController::class);

Route::get('graficas', function () {
    return view('graficas.index');
});

});
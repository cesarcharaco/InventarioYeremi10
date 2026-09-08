<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Categoria;
use App\Models\Insumos;
use App\Observers\AuditoriaObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'categoria' => Categoria::class,
            'insumo'    => Insumos::class, // Apunta al modelo correcto en plural
        ]);

        $modelos = [
                    \App\Models\AbonoCredito::class,
                    \App\Models\AbonoDetalle::class,
                    \App\Models\AutorizacionPin::class,
                    \App\Models\Caja::class,
                    \App\Models\CajaMovimiento::class,
                    \App\Models\Categoria::class,
                    \App\Models\Cliente::class,
                    \App\Models\ConfigOfertas::class,
                    \App\Models\Configuracion::class,
                    \App\Models\Correlativo::class,
                    \App\Models\Credito::class,
                    \App\Models\CreditoInteres::class,
                    \App\Models\Despachos::class,
                    \App\Models\DespachoDetalles::class,
                    \App\Models\DetalleEntrada::class,
                    \App\Models\DetalleVenta::class,
                    \App\Models\EntradaAlmacen::class,
                    \App\Models\ExchangeRate::class,
                    \App\Models\HistorialIncidencias::class,
                    \App\Models\HistoricoInsumoRecepcion::class,
                    \App\Models\Incidencias::class,
                    \App\Models\InsumoRecepcion::class,
                    \App\Models\Insumos::class,
                    \App\Models\InsumosC::class,
                    \App\Models\InsumosMayor::class,
                    \App\Models\ListasOferta::class,
                    \App\Models\Local::class,
                    \App\Models\ModeloVenta::class,
                    \App\Models\PagoReferencia::class,
                    \App\Models\Pedido::class,
                    \App\Models\PedidoDetalle::class,
                    \App\Models\PromocionRegla::class,
                    \App\Models\Proveedor::class,
                    \App\Models\User::class,
                    \App\Models\Venta::class,
                    \App\Models\VentaInformacion::class,
                ];

                foreach ($modelos as $modelo) {
                    $modelo::observe(AuditoriaObserver::class);
                }
    }
}

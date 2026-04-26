<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TasaCambioService;
class ActualizarTasas extends Command
{
    protected $signature = 'tasas:actualizar';
    protected $description = 'Actualiza las tasas desde BCV y Binance';

    public function handle()
    {
        TasaCambioService::actualizarTodasLasTasas();
        $this->info('Tasas actualizadas exitosamente.');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\AlertaCobranzaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarAlertasCobranzaCommand extends Command
{
    /**
     * Nombre y firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'creditos:alertas-cobranza';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Evalúa créditos pendientes (15, 20 y 30+ días) y genera notificaciones cada 5 días según local';

    /**
     * Ejecuta el comando.
     */
    public function handle(AlertaCobranzaService $servicio): int
    {
        $this->info('Iniciando evaluación de alertas de cobranza...');

        try {
            $resultado = $servicio->procesarAlertas();

            $enviadas = $resultado['alertas_enviadas'] ?? 0;
            $fecha = $resultado['fecha'] ?? now()->toDateString();

            $this->info("Proceso finalizado ({$fecha}). Alertas generadas: {$enviadas}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Error en comando creditos:alertas-cobranza: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->error('Ocurrió un error al procesar las alertas de cobranza: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
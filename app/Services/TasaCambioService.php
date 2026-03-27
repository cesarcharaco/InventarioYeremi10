<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;

class TasaCambioService
{
    // Este método es el que llamarás desde tu Comando o Cron
    public static function actualizarTodasLasTasas()
    {
        // 1. Procesar BCV
        $tasaBcv = self::fetchBCV();
        if ($tasaBcv > 0) {
            Configuracion::setTasa('tasa_bcv', $tasaBcv);
        }

        // 2. Procesar Binance
        $tasaBinance = self::fetchBinance(); 
        if ($tasaBinance > 0) {
            Configuracion::setTasa('tasa_binance', $tasaBinance);
        }

        Configuracion::setTasa('ultima_actualizacion', now());
    }

    private static function fetchBCV()
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
            ])->timeout(10)->get('https://www.bcv.org.ve/');

            if ($response->successful()) {
                preg_match('/id="dolar".*?<strong>\s*(.*?)\s*<\/strong>/s', $response->body(), $matches);
                return isset($matches[1]) ? (float) str_replace(',', '.', trim($matches[1])) : 0;
            }
        } catch (\Exception $e) {
            Log::warning("Fallo al conectar BCV: " . $e->getMessage());
        }
        return 0;
    }

    private static function fetchBinance()
    {
        // Aquí agregarías tu lógica para la API de Binance o P2P
        return 0; 
    }
}
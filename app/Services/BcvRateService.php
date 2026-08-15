<?php
// app/Services/BcvRateService.php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BcvRateService
{
    // Usa la API pública gratuita (sin auth)
    private const API_URL = 'https://rates.dolarvzla.com/bcv/current.json';
    
    // Cache por 4 horas (la tasa BCV cambia ~1 vez al día)
    private const CACHE_MINUTES = 240;

    public function getCurrentRate(string $currency = 'USD'): ?float
    {
        $cacheKey = "bcv_rate_{$currency}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($currency) {
            // 1. Primero intenta desde la base de datos (último registro)
            $latest = ExchangeRate::where('currency', $currency)
                ->where('fetched_at', '>=', now()->subHours(6))
                ->latest()
                ->first();

            if ($latest) {
                return (float) $latest->rate;
            }

            // 2. Si no hay datos recientes, consulta la API
            return $this->fetchFromApi($currency);
        });
    }

    private function fetchFromApi(string $currency): ?float
    {
        try {
            $response = Http::timeout(10)->get(self::API_URL);

            if (!$response->successful()) {
                Log::error('BCV API error', ['status' => $response->status()]);
                return $this->getFallbackRate($currency);
            }

            $data = $response->json();

            $rate = match(strtoupper($currency)) {
                'USD' => $data['current']['usd'] ?? null,
                'EUR' => $data['current']['eur'] ?? null,
                default => null,
            };

            if (!$rate) {
                return $this->getFallbackRate($currency);
            }

            // Guardar en BD para historial
            ExchangeRate::create([
                'currency' => strtoupper($currency),
                'rate' => $rate,
                'rate_date' => $data['current']['date'] ?? now()->toDateString(),
                'fetched_at' => now(),
            ]);

            return (float) $rate;

        } catch (\Exception $e) {
            Log::error('BCV fetch error: ' . $e->getMessage());
            return $this->getFallbackRate($currency);
        }
    }

    // Si todo falla, usa la última tasa guardada
    private function getFallbackRate(string $currency): ?float
    {
        $latest = ExchangeRate::where('currency', $currency)->latest()->first();
        return $latest ? (float) $latest->rate : null;
    }

    // Forzar actualización (útil para un botón "actualizar" en el admin)
    public function refreshRate(string $currency = 'USD'): ?float
    {
        Cache::forget("bcv_rate_{$currency}");
        return $this->getCurrentRate($currency);
    }
}
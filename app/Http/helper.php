<?php 
function locales()
{
	$locales=App\Models\Local::all();

	return $locales;
}

if (!function_exists('bcv_rate')) {
    function bcv_rate(string $currency = 'USD'): ?float
    {
        return app(\App\Services\BcvRateService::class)->getCurrentRate($currency);
    }
}
<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Obtiene el tipo de cambio de MXN a USD.
     * Cacheamos por 24 horas para no saturar la API gratuita.
     */
    public function getMxnToUsdRate(): float
    {
        return Cache::remember('mxn_to_usd_rate', 86400, function () {
            try {
                // Usamos la API gratuita de exchangerate-api.com
                $response = Http::get('https://api.exchangerate-api.com/v4/latest/MXN');
                
                if ($response->successful()) {
                    $rates = $response->json('rates');
                    return (float)($rates['USD'] ?? 0.05); // Valor por defecto ~0.05 si falla
                }
            } catch (\Exception $e) {
                \Log::warning("Error al consumir API de tipo de cambio: " . $e.getMessage());
            }
            
            return 0.05; // Fallback razonable
        });
    }

    /**
     * Convierte un monto en MXN a USD.
     */
    public function convertMxnToUsd(float $mxnAmount): float
    {
        $rate = $this->getMxnToUsdRate();
        return round($mxnAmount * $rate, 2);
    }
}

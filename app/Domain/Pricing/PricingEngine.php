<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\ValueObjects\PriceBreakdown;

/**
 * Motor de precios puro — sin dependencias de framework.
 * Fórmula de decaimiento exponencial: P(n) = 50 + 70 * exp(1.25 * (1 - n))
 */
class PricingEngine
{
    private const BASE_PRICE = 50.0;
    private const COEFFICIENT = 70.0;
    private const DECAY_RATE = 1.25;

    /**
     * Calcula el precio para el alumno n-ésimo.
     *
     * @param int $n Número de alumno (1-based, el siguiente en inscribirse)
     * @return PriceBreakdown
     */
    public function calculate(int $n): PriceBreakdown
    {
        if ($n < 1 || $n > 5) {
            throw new \InvalidArgumentException("El número de alumno debe estar entre 1 y 5. Recibido: {$n}");
        }

        // Funcionalidad dinámica comentada para el futuro según requerimiento
        /*
        $currentPrice = self::BASE_PRICE + self::COEFFICIENT * exp(self::DECAY_RATE * (1 - $n));
        $firstPrice = self::BASE_PRICE + self::COEFFICIENT * exp(self::DECAY_RATE * (1 - 1));
        $savedAmount = $firstPrice - $currentPrice;
        $discountPercentage = $firstPrice > 0 ? ($savedAmount / $firstPrice) * 100 : 0.0;
        */

        // Por ahora, todos pagan precio fijo de 50
        $fixedPrice = self::BASE_PRICE;

        return new PriceBreakdown(
            basePrice: self::BASE_PRICE,
            currentPrice: round($fixedPrice, 2),
            savedAmount: 0.0,
            discountPercentage: 0.0
        );
    }

    /**
     * Calcula precio para el SIGUIENTE alumno dado el count actual.
     */
    public function calculateForNextStudent(int $currentCount): PriceBreakdown
    {
        return $this->calculate($currentCount + 1);
    }
}

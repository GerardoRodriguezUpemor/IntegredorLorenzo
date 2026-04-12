<?php

namespace App\Domain\Pricing\ValueObjects;

/**
 * Value Object inmutable que representa el desglose de precio.
 */
final class PriceBreakdown
{
    public function __construct(
        public readonly float $basePrice,
        public readonly float $currentPrice,
        public readonly float $savedAmount,
        public readonly float $discountPercentage,
    ) {}

    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice,
            'current_price' => $this->currentPrice,
            'saved_amount' => $this->savedAmount,
            'discount_percentage' => $this->discountPercentage,
        ];
    }
}

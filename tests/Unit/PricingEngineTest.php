<?php

namespace Tests\Unit;

use App\Domain\Pricing\PricingEngine;
use App\Domain\Pricing\ValueObjects\PriceBreakdown;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PricingEngineTest extends TestCase
{
    private PricingEngine $pricingEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingEngine = new PricingEngine();
    }

    public function test_pricing_follows_exponential_decay_formula()
    {
        // First student n=1
        // P(1) = 50 + 70 * exp(1.25 * (1 - 1)) = 50 + 70 * exp(0) = 50 + 70 = 120
        $breakdown1 = $this->pricingEngine->calculate(1);
        $this->assertEquals(120.0, $breakdown1->currentPrice);
        $this->assertEquals(0.0, $breakdown1->savedAmount);
        $this->assertEquals(0.0, $breakdown1->discountPercentage);

        // Second student n=2
        // P(2) = 50 + 70 * exp(1.25 * (1 - 2)) = 50 + 70 * exp(-1.25) ≈ 70.055
        $breakdown2 = $this->pricingEngine->calculate(2);
        // exp(-1.25) is approx 0.2865, so 70 * 0.2865 ≈ 20.055, + 50 = 70.06
        $this->assertEqualsWithDelta(70.06, $breakdown2->currentPrice, 0.01);
        $this->assertEqualsWithDelta(49.94, $breakdown2->savedAmount, 0.01); // 120 - 70.06

        // Fifth student n=5
        // P(5) = 50 + 70 * exp(1.25 * (1 - 5)) = 50 + 70 * exp(-5) ≈ 50.47
        $breakdown5 = $this->pricingEngine->calculate(5);
        $this->assertEqualsWithDelta(50.47, $breakdown5->currentPrice, 0.01);
        $this->assertEqualsWithDelta(69.53, $breakdown5->savedAmount, 0.01);
    }

    public function test_calculate_for_next_student()
    {
        // If current count is 3, next student is 4
        $breakdown = $this->pricingEngine->calculateForNextStudent(3);
        $this->assertEquals($this->pricingEngine->calculate(4)->currentPrice, $breakdown->currentPrice);
    }

    public function test_throws_exception_if_n_is_out_of_bounds()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricingEngine->calculate(6); // Max capacity is 5
    }

    public function test_throws_exception_if_n_is_zero_or_negative()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricingEngine->calculate(0);
    }
}

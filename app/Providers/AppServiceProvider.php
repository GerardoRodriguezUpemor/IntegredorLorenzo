<?php

namespace App\Providers;

use App\Domain\Booking\Repositories\GroupRepositoryInterface;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Scheduling\VotingEngine;
use App\Infrastructure\Pdf\ReceiptPdfGenerator;
use App\Infrastructure\Repositories\MongoGroupRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register DDD bindings.
     */
    public function register(): void
    {
        // Domain Services (singleton — stateless)
        $this->app->singleton(PricingEngine::class, fn() => new PricingEngine());
        $this->app->singleton(VotingEngine::class, fn() => new VotingEngine());

        // Repository binding (interface → implementation)
        $this->app->bind(
            GroupRepositoryInterface::class,
            MongoGroupRepository::class
        );

        // Infrastructure Services
        $this->app->singleton(ReceiptPdfGenerator::class, fn() => new ReceiptPdfGenerator());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

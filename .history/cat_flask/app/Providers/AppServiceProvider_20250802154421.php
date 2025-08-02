<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FlaskApiService;
use App\Services\HybridCATService;
use App\Services\PerformanceMonitorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FlaskApiService
        $this->app->singleton(FlaskApiService::class, function ($app) {
            return new FlaskApiService();
        });

        // Register PerformanceMonitorService
        $this->app->singleton(PerformanceMonitorService::class, function ($app) {
            return new PerformanceMonitorService();
        });

        // Register HybridCATService
        $this->app->singleton(HybridCATService::class, function ($app) {
            return new HybridCATService(
                $app->make(FlaskApiService::class),
                $app->make(CATService::class),
                $app->make(PerformanceMonitorService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

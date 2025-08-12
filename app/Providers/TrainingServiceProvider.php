<?php

namespace App\Providers;

use App\Services\CertificateGenerationService;
use App\Services\NotificationService;
use App\Services\ExportService;
use Illuminate\Support\ServiceProvider;

class TrainingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Certificate Generation Service
        $this->app->singleton(CertificateGenerationService::class, function ($app) {
            return new CertificateGenerationService();
        });

        // Register Notification Service
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // Register Export Service
        $this->app->singleton(ExportService::class, function ($app) {
            return new ExportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Boot any training-related services here if needed

        // Example: Register training-specific validators
        // Validator::extend('training_date', function ($attribute, $value, $parameters, $validator) {
        //     return Carbon::parse($value)->isFuture();
        // });

        // Example: Register training-specific view composers
        // View::composer('training.*', function ($view) {
        //     $view->with('trainingTypes', TrainingType::active()->get());
        // });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            CertificateGenerationService::class,
            NotificationService::class,
            ExportService::class,
        ];
    }
}

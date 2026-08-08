<?php

namespace App\Providers;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ReportAiDispatcher;
use App\Services\InlineReportAiDispatcher;
use App\Services\LocalPrivateReportPhotoStorage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PrivateReportPhotoStorage::class,
            LocalPrivateReportPhotoStorage::class
        );
        $this->app->bind(
            ReportAiDispatcher::class,
            InlineReportAiDispatcher::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

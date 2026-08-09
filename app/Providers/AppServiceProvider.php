<?php

namespace App\Providers;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ReportAiDispatcher;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Services\InlineReportAiDispatcher;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\Phase9AReadOnlyRuntimeGuard;
use App\Services\ReportPhotoStorageResolver;
use App\Services\SupabasePrivateReportPhotoStorage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PrivateReportPhotoStorage::class, function ($app) {
            return match ((string) config('report_photos.driver', 'local')) {
                'local' => $app->make(LocalPrivateReportPhotoStorage::class),
                'supabase' => tap(
                    $app->make(SupabasePrivateReportPhotoStorage::class),
                    fn (SupabasePrivateReportPhotoStorage $storage) => $storage->assertReady()
                ),
                default => throw new RuntimeException(
                    'Invalid private photograph storage driver.'
                ),
            };
        });
        $this->app->bind(
            ResolvesPrivateReportPhotoStorage::class,
            ReportPhotoStorageResolver::class
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
        if ((bool) config('phase9a.runtime_read_only')) {
            $this->app->make(Phase9AReadOnlyRuntimeGuard::class)->activate(
                (string) config('database.default'),
            );
        }
    }
}

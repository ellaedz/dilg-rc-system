<?php

namespace App\Providers;

use App\Contracts\CreatesCloudTask;
use App\Contracts\InteractsWithAzureQueue;
use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ProvidesManagedIdentityToken;
use App\Contracts\ReportAiDispatcher;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Contracts\VerifiesCloudTaskOidc;
use App\Contracts\VerifiesGoogleIdTokenSignature;
use App\Services\CloudTasksReportAiDispatcher;
use App\Services\AzureEntraAccessTokenVerifier;
use App\Services\AzureManagedIdentityTokenProvider;
use App\Services\AzureQueueRestClient;
use App\Services\AzureQueueTaskCreator;
use App\Services\GoogleCloudTaskCreator;
use App\Services\GoogleCloudTaskOidcVerifier;
use App\Services\GoogleIdTokenSignatureVerifier;
use App\Services\InlineReportAiDispatcher;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\Phase9AReadOnlyRuntimeGuard;
use App\Services\ReportPhotoStorageResolver;
use App\Services\SupabasePrivateReportPhotoStorage;
use Google\Auth\AccessToken;
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
        $this->app->singleton(
            ProvidesManagedIdentityToken::class,
            AzureManagedIdentityTokenProvider::class,
        );
        $this->app->bind(InteractsWithAzureQueue::class, AzureQueueRestClient::class);
        $this->app->bind(CreatesCloudTask::class, function ($app) {
            return match ((string) config('cloud_tasks.dispatcher', 'inline')) {
                'azure_queue' => $app->make(AzureQueueTaskCreator::class),
                default => $app->make(GoogleCloudTaskCreator::class),
            };
        });
        $this->app->bind(
            VerifiesCloudTaskOidc::class,
            fn ($app) => (string) config('cloud_tasks.dispatcher', 'inline') === 'azure_queue'
                ? $app->make(AzureEntraAccessTokenVerifier::class)
                : $app->make(GoogleCloudTaskOidcVerifier::class)
        );
        $this->app->bind(
            VerifiesGoogleIdTokenSignature::class,
            fn () => new GoogleIdTokenSignatureVerifier(new AccessToken)
        );
        $this->app->bind(
            ReportAiDispatcher::class,
            function ($app) {
                return match ((string) config('cloud_tasks.dispatcher', 'inline')) {
                    'inline' => $app->make(InlineReportAiDispatcher::class),
                    'cloud_tasks' => $app->make(CloudTasksReportAiDispatcher::class),
                    'azure_queue' => $app->make(CloudTasksReportAiDispatcher::class),
                    default => throw new RuntimeException('Invalid AI dispatcher.'),
                };
            }
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

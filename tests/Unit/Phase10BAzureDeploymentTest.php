<?php

namespace Tests\Unit;

use App\Contracts\InteractsWithAzureQueue;
use App\Contracts\ProvidesManagedIdentityToken;
use App\Data\AzureQueueMessage;
use App\Data\CloudTaskDefinition;
use App\Exceptions\CloudTaskIdentityException;
use App\Services\AzureAiQueueWorker;
use App\Services\AzureEntraAccessTokenVerifier;
use App\Services\AzureManagedIdentityTokenProvider;
use App\Services\AzureQueueConfiguration;
use App\Services\AzureQueueRestClient;
use App\Services\AzureQueueTaskCreator;
use App\Services\FastApiRequestAuthenticator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase10BAzureDeploymentTest extends TestCase
{
    private const LARAVEL_CLIENT_ID = '11111111-1111-4111-8111-111111111111';

    private const WORKER_CLIENT_ID = '22222222-2222-4222-8222-222222222222';

    private const WORKER_PRINCIPAL_ID = '33333333-3333-4333-8333-333333333333';

    private const TENANT_ID = '44444444-4444-4444-8444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'azure.identity.endpoint' => 'http://127.0.0.1:42356/msi/token',
            'azure.identity.header' => 'test-identity-header',
            'azure.identity.allowed_client_ids' => [
                self::LARAVEL_CLIENT_ID,
                self::WORKER_CLIENT_ID,
            ],
            'azure.identity.allowed_resources' => [
                'https://storage.azure.com/',
                'api://civiclear-task-api',
            ],
            'azure.queue.account' => 'civicleartestqueue',
            'azure.queue.primary_queue' => 'civiclear-ai-processing',
            'azure.queue.quarantine_queue' => 'civiclear-ai-quarantine',
            'azure.queue.storage_resource' => 'https://storage.azure.com/',
            'azure.queue.laravel_client_id' => self::LARAVEL_CLIENT_ID,
            'azure.queue.worker_client_id' => self::WORKER_CLIENT_ID,
            'azure.queue.task_api_resource' => 'api://civiclear-task-api',
            'azure.queue.task_handler_url' => 'https://laravel.internal.example/api/internal/ai-tasks/process-report-ai',
            'azure.queue.worker_context' => false,
            'azure.queue.maximum_dequeue_count' => 5,
            'azure.queue.retry_initial_seconds' => 10,
            'azure.queue.retry_max_seconds' => 300,
            'cloud_tasks.payload_version' => 'v1',
        ]);
    }

    public function test_managed_identity_uses_fixed_resource_client_and_identity_header(): void
    {
        Http::fake([
            'http://127.0.0.1:42356/*' => Http::response([
                'access_token' => $this->jwtShape(),
                'expires_on' => time() + 3600,
            ]),
        ]);

        $token = app(AzureManagedIdentityTokenProvider::class)->forResource(
            'api://civiclear-task-api',
            self::WORKER_CLIENT_ID,
        );

        $this->assertSame($this->jwtShape(), $token);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('X-IDENTITY-HEADER', 'test-identity-header')
                && ($query['resource'] ?? null) === 'api://civiclear-task-api'
                && ($query['client_id'] ?? null) === self::WORKER_CLIENT_ID;
        });
    }

    public function test_managed_identity_rejects_unapproved_resource_before_network_access(): void
    {
        Http::fake();

        $this->expectException(\RuntimeException::class);
        try {
            app(AzureManagedIdentityTokenProvider::class)->forResource(
                'https://management.azure.com/',
                self::WORKER_CLIENT_ID,
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_task_creator_sends_only_the_versioned_internal_contract(): void
    {
        $queue = new Phase10BFakeAzureQueue;
        $creator = new AzureQueueTaskCreator($queue, app(AzureQueueConfiguration::class));

        $result = $creator->create(new CloudTaskDefinition(
            str_repeat('a', 64),
            ['version' => 'v1', 'report_id' => 7, 'task_generation' => 2],
        ));

        $this->assertSame('created', $result->outcome);
        $this->assertSame('civiclear-ai-processing', $queue->sent[0]['queue']);
        $this->assertSame([
            'version' => 'azure-queue-v1',
            'task_id' => str_repeat('a', 64),
            'task' => ['version' => 'v1', 'report_id' => 7, 'task_generation' => 2],
        ], $queue->sent[0]['payload']);
    }

    public function test_queue_rest_sender_uses_bearer_xml_and_approved_laravel_identity(): void
    {
        $tokens = new Phase10BRecordingManagedIdentityTokenProvider;
        Http::fake([
            'https://civicleartestqueue.queue.core.windows.net/*' => Http::response('', 201),
        ]);
        $client = new AzureQueueRestClient($tokens, app(AzureQueueConfiguration::class));

        $client->send('civiclear-ai-processing', [
            'version' => 'azure-queue-v1',
            'task_id' => str_repeat('a', 64),
        ]);

        $this->assertSame([
            'resource' => 'https://storage.azure.com/',
            'client_id' => self::LARAVEL_CLIENT_ID,
        ], $tokens->calls[0]);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $decoded = base64_decode(
                (string) simplexml_load_string($request->body())->MessageText,
                true,
            );

            return $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer header.payload.signature')
                && $request->hasHeader('x-ms-version', '2023-11-03')
                && ($query['visibilitytimeout'] ?? null) === '0'
                && str_contains((string) $decoded, 'azure-queue-v1');
        });
    }

    public function test_queue_rest_worker_receives_updates_and_deletes_with_worker_identity(): void
    {
        config(['azure.queue.worker_context' => true]);
        $tokens = new Phase10BRecordingManagedIdentityTokenProvider;
        $messageBody = json_encode([
            'version' => 'azure-queue-v1',
            'task_id' => str_repeat('a', 64),
        ], JSON_THROW_ON_ERROR);
        Http::fakeSequence()
            ->push('<QueueMessagesList><QueueMessage>'
                .'<MessageId>12345678-1234-4234-8234-123456789012</MessageId>'
                .'<PopReceipt>receipt-one</PopReceipt><DequeueCount>2</DequeueCount>'
                .'<MessageText>'.base64_encode($messageBody).'</MessageText>'
                .'</QueueMessage></QueueMessagesList>', 200)
            ->push('', 204)
            ->push('', 204);
        $client = new AzureQueueRestClient($tokens, app(AzureQueueConfiguration::class));

        $message = $client->receive('civiclear-ai-processing');
        $this->assertNotNull($message);
        $this->assertSame($messageBody, $message->body);
        $client->updateVisibility('civiclear-ai-processing', $message, 20);
        $client->delete('civiclear-ai-processing', $message);

        $this->assertCount(3, $tokens->calls);
        foreach ($tokens->calls as $call) {
            $this->assertSame(self::WORKER_CLIENT_ID, $call['client_id']);
            $this->assertSame('https://storage.azure.com/', $call['resource']);
        }
        Http::assertSentCount(3);
    }

    public function test_fastapi_authenticator_uses_only_the_configured_https_service(): void
    {
        config([
            'ai_inference.url' => 'https://fastapi.internal.example',
            'ai_inference.auth_mode' => 'azure_entra',
            'ai_inference.entra_resource' => 'api://civiclear-task-api',
            'azure.identity.laravel_client_id' => self::LARAVEL_CLIENT_ID,
        ]);
        $tokens = new Phase10BRecordingManagedIdentityTokenProvider;
        Http::fake([
            'https://fastapi.internal.example/*' => Http::response(['ok' => true]),
        ]);
        $endpoint = 'https://fastapi.internal.example/v1/predict/multimodal';
        $request = (new FastApiRequestAuthenticator($tokens))->authenticate(
            Http::acceptJson(),
            $endpoint,
        );

        $request->post($endpoint);

        $this->assertSame([
            'resource' => 'api://civiclear-task-api',
            'client_id' => self::LARAVEL_CLIENT_ID,
        ], $tokens->calls[0]);
        Http::assertSent(fn (Request $sent): bool => $sent->hasHeader(
            'Authorization',
            'Bearer header.payload.signature',
        ));
    }

    public function test_worker_acknowledges_success_and_deletes_the_original_message(): void
    {
        $queue = $this->workerQueue(1);
        Http::fake([
            'https://laravel.internal.example/*' => Http::response([
                'success' => true,
                'acknowledged' => true,
                'code' => 'AI_COMPLETED',
            ]),
        ]);

        $result = $this->worker($queue)->processOne();

        $this->assertSame(AzureAiQueueWorker::ACKNOWLEDGED, $result);
        $this->assertCount(1, $queue->deleted);
        $this->assertSame([], $queue->visibilityUpdates);
        $this->assertSame([], $queue->sent);
    }

    public function test_worker_schedules_bounded_visibility_retry_for_transient_failure(): void
    {
        $queue = $this->workerQueue(2);
        Http::fake([
            'https://laravel.internal.example/*' => Http::response([
                'success' => false,
                'retry' => true,
                'code' => 'FASTAPI_UNAVAILABLE',
            ], 500),
        ]);

        $result = $this->worker($queue)->processOne();

        $this->assertSame(AzureAiQueueWorker::RETRY_SCHEDULED, $result);
        $this->assertSame(20, $queue->visibilityUpdates[0]['seconds']);
        $this->assertSame([], $queue->deleted);
        $this->assertSame([], $queue->sent);
    }

    public function test_worker_quarantines_after_the_maximum_delivery_count(): void
    {
        $queue = $this->workerQueue(5);
        Http::fake([
            'https://laravel.internal.example/*' => Http::response([
                'success' => false,
                'retry' => true,
                'code' => 'FASTAPI_UNAVAILABLE',
            ], 500),
        ]);

        $result = $this->worker($queue)->processOne();

        $this->assertSame(AzureAiQueueWorker::QUARANTINED, $result);
        $this->assertSame('civiclear-ai-quarantine', $queue->sent[0]['queue']);
        $this->assertSame('azure-quarantine-v1', $queue->sent[0]['payload']['version']);
        $this->assertSame('FASTAPI_UNAVAILABLE', $queue->sent[0]['payload']['failure_code']);
        $this->assertCount(1, $queue->deleted);
    }

    public function test_entra_verifier_binds_tenant_audience_role_client_and_principal(): void
    {
        $now = time();
        config([
            'azure.entra.tenant_id' => self::TENANT_ID,
            'azure.entra.task_api_audience' => 'api://civiclear-task-api',
            'azure.entra.task_api_role' => 'Civiclear.AiTask.Invoke',
            'azure.entra.task_worker_client_id' => self::WORKER_CLIENT_ID,
            'azure.entra.task_worker_principal_id' => self::WORKER_PRINCIPAL_ID,
        ]);
        $claims = [
            'aud' => 'api://civiclear-task-api',
            'iss' => 'https://login.microsoftonline.com/'.self::TENANT_ID.'/v2.0',
            'ver' => '2.0',
            'tid' => self::TENANT_ID,
            'azp' => self::WORKER_CLIENT_ID,
            'oid' => self::WORKER_PRINCIPAL_ID,
            'roles' => ['Civiclear.AiTask.Invoke'],
            'iat' => $now - 5,
            'nbf' => $now - 5,
            'exp' => $now + 300,
        ];
        $verifier = new Phase10BControlledEntraVerifier($claims);
        $verified = $verifier->verify('header.payload.signature');
        $this->assertSame(self::WORKER_PRINCIPAL_ID, $verified['oid']);

        $managedIdentityClaims = $claims;
        $managedIdentityClaims['iss'] = 'https://sts.windows.net/'.self::TENANT_ID.'/';
        $managedIdentityClaims['ver'] = '1.0';
        $managedIdentityClaims['appid'] = $managedIdentityClaims['azp'];
        unset($managedIdentityClaims['azp']);
        $managedIdentityVerified = (new Phase10BControlledEntraVerifier($managedIdentityClaims))
            ->verify('header.payload.signature');
        $this->assertSame(self::WORKER_CLIENT_ID, $managedIdentityVerified['appid']);

        $claims['roles'] = ['Unapproved.Role'];
        $this->expectException(CloudTaskIdentityException::class);
        (new Phase10BControlledEntraVerifier($claims))->verify('header.payload.signature');
    }

    public function test_entra_verifier_rejects_a_token_version_issuer_mismatch(): void
    {
        config([
            'azure.entra.tenant_id' => self::TENANT_ID,
            'azure.entra.task_api_audience' => 'api://civiclear-task-api',
            'azure.entra.task_api_role' => 'Civiclear.AiTask.Invoke',
            'azure.entra.task_worker_client_id' => self::WORKER_CLIENT_ID,
            'azure.entra.task_worker_principal_id' => self::WORKER_PRINCIPAL_ID,
        ]);

        $claims = [
            'aud' => 'api://civiclear-task-api',
            'iss' => 'https://login.microsoftonline.com/'.self::TENANT_ID.'/v2.0',
            'ver' => '1.0',
            'tid' => self::TENANT_ID,
            'appid' => self::WORKER_CLIENT_ID,
            'oid' => self::WORKER_PRINCIPAL_ID,
            'roles' => ['Civiclear.AiTask.Invoke'],
        ];

        $this->expectException(CloudTaskIdentityException::class);
        (new Phase10BControlledEntraVerifier($claims))->verify('header.payload.signature');
    }

    public function test_deployment_artifacts_preserve_cost_identity_and_network_gates(): void
    {
        $workloads = file_get_contents(base_path('infra/phase10b/workloads.bicep'));
        $main = file_get_contents(base_path('infra/phase10b/main.bicep'));
        $rbac = file_get_contents(base_path('infra/phase10b/rbac.bicep'));
        $roles = json_decode(
            file_get_contents(base_path('infra/phase10b/entra-app-roles.example.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString('external: false', $workloads);
        $this->assertStringContainsString('minReplicas: 0, maxReplicas: 2', $workloads);
        $this->assertStringContainsString('minReplicas: 0, maxReplicas: 1', $workloads);
        $this->assertStringContainsString('minExecutions: 0', $workloads);
        $this->assertStringContainsString('maxExecutions: 1', $workloads);
        $this->assertStringContainsString('parallelism: 1', $workloads);
        $this->assertStringContainsString("sku: { name: 'Basic' }", $main);
        $this->assertStringContainsString('workloadProfileType: \'Consumption\'', $main);
        $this->assertStringContainsString('c6a89b2d-59bc-44d0-9896-0f6e12d7b80a', $rbac);
        $this->assertStringContainsString('8a0f0c08-91a1-4084-bc3d-661d67233fed', $rbac);
        $this->assertSame(['Application'], $roles['laravelTaskApi']['appRoles'][0]['allowedMemberTypes']);
        $this->assertSame(['Application'], $roles['fastApi']['appRoles'][0]['allowedMemberTypes']);
    }

    public function test_laravel_runtime_packages_only_the_verified_public_supabase_ca(): void
    {
        $certificateRelativePath = 'docker/laravel/certs/supabase-root-2021.crt';
        $certificatePath = base_path($certificateRelativePath);
        $certificate = file_get_contents($certificatePath);
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $dockerignore = file_get_contents(base_path('.dockerignore'));
        $buildWorkflow = file_get_contents(base_path(
            '.github/workflows/phase10b-azure-build.yml',
        ));

        $this->assertFileExists($certificatePath);
        $this->assertSame(
            '700723581420dd1ac98fd7e9ac529f0ef210eadcaf87fc868a3ad7d114c2f3b7',
            hash_file('sha256', $certificatePath),
        );
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $certificate);
        $this->assertStringNotContainsString('PRIVATE KEY', $certificate);
        $this->assertStringContainsString('*.crt', $dockerignore);
        $this->assertStringContainsString(
            '!'.$certificateRelativePath,
            $dockerignore,
        );
        $this->assertStringContainsString(
            'COPY '.$certificateRelativePath.' /usr/local/share/ca-certificates/supabase-root-2021.crt',
            $dockerfile,
        );
        $this->assertStringContainsString(
            'DB_SSLROOTCERT=/usr/local/share/ca-certificates/supabase-root-2021.crt',
            $dockerfile,
        );
        $this->assertStringContainsString(
            'chmod 0444 /usr/local/share/ca-certificates/supabase-root-2021.crt',
            $dockerfile,
        );
        $this->assertStringContainsString(
            "-e '".$certificateRelativePath."'",
            $buildWorkflow,
        );
        $this->assertStringContainsString("-e '.env.example'", $buildWorkflow);
        $this->assertStringContainsString(
            "-e 'storage/app/private/.gitignore'",
            $buildWorkflow,
        );
        $this->assertStringContainsString(
            '\\.(pem|key|pfx|p12|crt|gpkg)$',
            $buildWorkflow,
        );
    }

    private function worker(Phase10BFakeAzureQueue $queue): AzureAiQueueWorker
    {
        return new AzureAiQueueWorker(
            $queue,
            new Phase10BFakeManagedIdentityTokenProvider,
            app(AzureQueueConfiguration::class),
        );
    }

    private function workerQueue(int $dequeueCount): Phase10BFakeAzureQueue
    {
        $queue = new Phase10BFakeAzureQueue;
        $queue->messages[] = new AzureQueueMessage(
            '12345678-1234-4234-8234-123456789012',
            'pop-receipt',
            $dequeueCount,
            json_encode([
                'version' => 'azure-queue-v1',
                'task_id' => str_repeat('a', 64),
                'task' => ['version' => 'v1', 'report_id' => 7, 'task_generation' => 2],
            ], JSON_THROW_ON_ERROR),
        );

        return $queue;
    }

    private function jwtShape(): string
    {
        return 'header.payload.signature';
    }
}

class Phase10BControlledEntraVerifier extends AzureEntraAccessTokenVerifier
{
    /** @param array<string, mixed> $claims */
    public function __construct(private readonly array $claims) {}

    protected function decodeToken(string $token, string $tenant): object
    {
        return (object) $this->claims;
    }
}

class Phase10BFakeManagedIdentityTokenProvider implements ProvidesManagedIdentityToken
{
    public function forResource(string $resource, string $clientId): string
    {
        return 'header.payload.signature';
    }
}

class Phase10BRecordingManagedIdentityTokenProvider implements ProvidesManagedIdentityToken
{
    /** @var list<array{resource:string,client_id:string}> */
    public array $calls = [];

    public function forResource(string $resource, string $clientId): string
    {
        $this->calls[] = ['resource' => $resource, 'client_id' => $clientId];

        return 'header.payload.signature';
    }
}

class Phase10BFakeAzureQueue implements InteractsWithAzureQueue
{
    /** @var list<array{queue:string,payload:array<string,mixed>}> */
    public array $sent = [];

    /** @var list<AzureQueueMessage> */
    public array $messages = [];

    /** @var list<array{queue:string,message:AzureQueueMessage,seconds:int}> */
    public array $visibilityUpdates = [];

    /** @var list<array{queue:string,message:AzureQueueMessage}> */
    public array $deleted = [];

    public function send(string $queue, array $payload): void
    {
        $this->sent[] = compact('queue', 'payload');
    }

    public function receive(string $queue): ?AzureQueueMessage
    {
        return array_shift($this->messages);
    }

    public function updateVisibility(
        string $queue,
        AzureQueueMessage $message,
        int $visibilityTimeoutSeconds,
    ): void {
        $this->visibilityUpdates[] = [
            'queue' => $queue,
            'message' => $message,
            'seconds' => $visibilityTimeoutSeconds,
        ];
    }

    public function delete(string $queue, AzureQueueMessage $message): void
    {
        $this->deleted[] = compact('queue', 'message');
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\CreatesCloudTask;
use App\Contracts\VerifiesCloudTaskOidc;
use App\Contracts\VerifiesGoogleIdTokenSignature;
use App\Data\CloudTaskCreationResult;
use App\Data\CloudTaskDefinition;
use App\Exceptions\CloudTaskCreationException;
use App\Exceptions\CloudTaskIdentityException;
use App\Models\User;
use App\Models\ViolationReport;
use App\Services\GoogleCloudTaskOidcVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPhase8cResponses;
use Tests\Support\CreatesTestImages;
use Tests\TestCase;

class Phase10ACloudTasksProcessingTest extends TestCase
{
    use CreatesPhase8cResponses;
    use CreatesTestImages;
    use RefreshDatabase;

    private Phase10AFakeCloudTaskCreator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('report_photo_quarantine');
        Storage::fake('public');
        Http::preventStrayRequests();

        config([
            'cloud_tasks.dispatcher' => 'cloud_tasks',
            'cloud_tasks.project' => 'civiclear-test-project',
            'cloud_tasks.location' => 'asia-southeast1',
            'cloud_tasks.queue' => 'civiclear-ai',
            'cloud_tasks.handler_url' => 'https://example.test/api/internal/cloud-tasks/process-report-ai',
            'cloud_tasks.oidc_service_account_email' => 'tasks@example.test',
            'cloud_tasks.oidc_service_account_subject' => '1234567890',
            'cloud_tasks.oidc_audience' => 'https://example.test/api/internal/cloud-tasks/process-report-ai',
            'cloud_tasks.create_timeout_seconds' => 10,
            'cloud_tasks.dispatch_deadline_seconds' => 45,
            'cloud_tasks.creation_claim_seconds' => 30,
            'cloud_tasks.stale_dispatch_seconds' => 60,
            'ai_inference.timeout_seconds' => 20,
            'ai_inference.processing_lease_seconds' => 60,
        ]);

        $this->creator = new Phase10AFakeCloudTaskCreator;
        $this->app->instance(CreatesCloudTask::class, $this->creator);
        $this->app->instance(
            VerifiesCloudTaskOidc::class,
            new Phase10AAcceptingOidcVerifier
        );
    }

    public function test_schema_is_additive_and_task_errors_are_separate(): void
    {
        foreach ([
            'task_generation',
            'task_creation_attempts',
            'task_id_hash',
            'task_creation_token_hash',
            'task_creation_started_at',
            'task_creation_expires_at',
            'task_last_attempted_at',
            'task_created_at',
            'task_creation_error_code',
            'task_creation_error_message',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('violation_reports', $column), $column);
        }

        $this->assertTrue(Schema::hasColumn('violation_reports', 'processing_error_code'));
        $this->assertTrue(Schema::hasColumn('violation_reports', 'task_creation_status'));
    }

    public function test_submission_persists_before_queueing_and_does_not_wait_for_inference(): void
    {
        $response = $this->submit();

        $response->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'pending')
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonMissingPath('data.task_creation_status')
            ->assertJsonMissingPath('data.task_id_hash')
            ->assertJsonMissingPath('data.task_generation');

        $report = ViolationReport::firstOrFail();
        $this->assertSame(ViolationReport::TASK_STATUS_CREATED, $report->task_creation_status);
        $this->assertSame(1, $report->task_generation);
        $this->assertSame(1, $report->task_creation_attempts);
        $this->assertSame(0, $report->ai_processing_attempts);
        $this->assertSame(ViolationReport::AI_STATUS_PENDING, $report->ai_processing_status);
        $this->assertNull($report->processing_error_code);
        $this->assertNull($report->task_creation_error_code);
        $this->assertCount(1, $this->creator->definitions);

        $definition = $this->creator->definitions[0];
        $this->assertSame(
            hash('sha256', "civiclear-ai:v1:{$report->id}:1"),
            $definition->taskId
        );
        $this->assertSame([
            'version' => 'v1',
            'report_id' => $report->id,
            'task_generation' => 1,
        ], $definition->payload);
        $this->assertStringNotContainsString($report->report_number, $definition->taskId);
        Http::assertNothingSent();
    }

    public function test_public_replay_does_not_create_another_task_generation(): void
    {
        $key = 'phase-10a-idempotency-key-000001';
        $first = $this->submit($key);
        $second = $this->submit($key);

        $first->assertCreated();
        $second->assertOk()->assertJsonPath('data.idempotent_replay', true);
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ViolationReport::firstOrFail()->task_generation);
        $this->assertCount(1, $this->creator->definitions);
    }

    public function test_definitive_creation_failure_preserves_report_and_ai_pending_state(): void
    {
        $this->creator->outcomes = [CloudTaskCreationException::definitive()];

        $response = $this->submit();
        $response->assertCreated()->assertJsonPath('data.ai_processing_status', 'pending');

        $report = ViolationReport::firstOrFail();
        $this->assertSame(ViolationReport::TASK_STATUS_FAILED, $report->task_creation_status);
        $this->assertSame('TASK_CREATION_FAILED', $report->task_creation_error_code);
        $this->assertNull($report->processing_error_code);
        $this->assertSame(ViolationReport::AI_STATUS_PENDING, $report->ai_processing_status);
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
    }

    public function test_unsafe_timeout_configuration_is_recorded_as_definite_failure(): void
    {
        config(['cloud_tasks.dispatch_deadline_seconds' => 20]);

        $this->submit()->assertCreated();

        $report = ViolationReport::firstOrFail();
        $this->assertSame(ViolationReport::TASK_STATUS_FAILED, $report->task_creation_status);
        $this->assertSame('TASK_CREATION_FAILED', $report->task_creation_error_code);
        $this->assertSame(ViolationReport::AI_STATUS_PENDING, $report->ai_processing_status);
        $this->assertNull($report->processing_error_code);
        $this->assertCount(0, $this->creator->definitions);
    }

    public function test_uncertain_creation_recovery_reuses_exact_task_id(): void
    {
        $this->creator->outcomes = [
            CloudTaskCreationException::uncertain(),
            CloudTaskCreationResult::alreadyExists(),
        ];
        $this->submit()->assertCreated();
        $before = ViolationReport::firstOrFail();
        $firstTaskId = $before->task_id_hash;
        $this->assertSame(ViolationReport::TASK_STATUS_UNCERTAIN, $before->task_creation_status);

        $this->artisan('phase10a:recover-ai-dispatch', ['--execute' => true])
            ->assertSuccessful();

        $after = $before->refresh();
        $this->assertSame(ViolationReport::TASK_STATUS_CREATED, $after->task_creation_status);
        $this->assertSame(1, $after->task_generation);
        $this->assertSame(2, $after->task_creation_attempts);
        $this->assertSame($firstTaskId, $after->task_id_hash);
        $this->assertSame($firstTaskId, $this->creator->definitions[1]->taskId);
    }

    public function test_recovery_dry_run_changes_nothing(): void
    {
        $this->creator->outcomes = [CloudTaskCreationException::uncertain()];
        $this->submit();
        $before = ViolationReport::firstOrFail()->getAttributes();

        $this->artisan('phase10a:recover-ai-dispatch')->assertSuccessful();

        $this->assertSame($before, ViolationReport::firstOrFail()->getAttributes());
        $this->assertCount(1, $this->creator->definitions);
    }

    public function test_stale_created_recovery_uses_a_new_generation_and_task_id(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $firstTaskId = $report->task_id_hash;
        $report->forceFill(['task_created_at' => now()->subMinutes(2)])->save();

        $this->artisan('phase10a:recover-ai-dispatch', ['--execute' => true])
            ->assertSuccessful();

        $report->refresh();
        $this->assertSame(2, $report->task_generation);
        $this->assertNotSame($firstTaskId, $report->task_id_hash);
        $this->assertSame(
            hash('sha256', "civiclear-ai:v1:{$report->id}:2"),
            $report->task_id_hash
        );
    }

    public function test_handler_reconciles_creation_race_and_duplicate_delivery_is_safe(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $report->forceFill([
            'task_creation_status' => ViolationReport::TASK_STATUS_CREATING,
            'task_creation_token_hash' => hash('sha256', 'creator-owner'),
            'task_creation_started_at' => now(),
            'task_creation_expires_at' => now()->addMinute(),
            'task_created_at' => null,
        ])->save();
        Http::fake([
            '*/v1/predict/multimodal' => Http::response($this->phase8cResponse()),
        ]);

        $this->postTask($report)->assertOk()->assertJsonPath('code', 'AI_COMPLETED');
        $report->refresh();
        $this->assertSame(ViolationReport::TASK_STATUS_CREATED, $report->task_creation_status);
        $this->assertSame(ViolationReport::AI_STATUS_COMPLETED, $report->ai_processing_status);

        $this->postTask($report)
            ->assertOk()
            ->assertJsonPath('code', 'AI_ALREADY_COMPLETED');
        Http::assertSentCount(1);
    }

    public function test_creator_cannot_overwrite_delivery_that_won_the_race(): void
    {
        $this->creator->onCreate = function (CloudTaskDefinition $definition): void {
            ViolationReport::whereKey($definition->payload['report_id'])->update([
                'task_creation_status' => ViolationReport::TASK_STATUS_CREATED,
                'task_creation_token_hash' => null,
                'task_creation_started_at' => null,
                'task_creation_expires_at' => null,
                'task_created_at' => now(),
                'ai_processing_status' => ViolationReport::AI_STATUS_COMPLETED,
                'ai_processed_at' => now(),
                'updated_at' => now(),
            ]);
        };

        $this->submit()->assertCreated()->assertJsonPath('data.ai_processing_status', 'completed');

        $report = ViolationReport::firstOrFail();
        $this->assertSame(ViolationReport::TASK_STATUS_CREATED, $report->task_creation_status);
        $this->assertSame(ViolationReport::AI_STATUS_COMPLETED, $report->ai_processing_status);
        $this->assertNull($report->task_creation_error_code);
    }

    public function test_transient_worker_failure_retries_same_generation(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw new ConnectionException('offline');
            }

            return Http::response($this->phase8cResponse());
        });

        $this->postTask($report)->assertStatus(500)->assertJsonPath('retry', true);
        $this->assertSame(ViolationReport::AI_STATUS_FAILED, $report->refresh()->ai_processing_status);

        $this->postTask($report)->assertOk()->assertJsonPath('code', 'AI_COMPLETED');
        $this->assertSame(1, $report->refresh()->task_generation);
        $this->assertSame(2, $report->ai_processing_attempts);
    }

    public function test_authenticated_permanent_evidence_failure_is_acknowledged(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();
        Storage::disk('report_photos')->delete($report->photo_object_key);

        $this->postTask($report)
            ->assertOk()
            ->assertJsonPath('code', 'AI_PHOTO_UNAVAILABLE');
        $this->assertSame(ViolationReport::AI_STATUS_FAILED, $report->refresh()->ai_processing_status);
    }

    public function test_oidc_runs_before_payload_and_permanent_payload_errors_are_acknowledged(): void
    {
        $this->postJson('/api/internal/cloud-tasks/process-report-ai', [])
            ->assertUnauthorized();

        $this->app->instance(
            VerifiesCloudTaskOidc::class,
            new Phase10ARejectingOidcVerifier
        );
        $this->postJson(
            '/api/internal/cloud-tasks/process-report-ai',
            [],
            ['Authorization' => 'Bearer '.$this->bearerToken()]
        )->assertForbidden();

        $this->app->instance(
            VerifiesCloudTaskOidc::class,
            new Phase10AAcceptingOidcVerifier
        );
        $this->postJson(
            '/api/internal/cloud-tasks/process-report-ai',
            ['report_id' => 'not-an-integer'],
            ['Authorization' => 'Bearer '.$this->bearerToken()]
        )->assertOk()->assertJsonPath('code', 'TASK_PAYLOAD_INVALID');
    }

    public function test_verified_google_identity_is_bound_to_expected_service_account(): void
    {
        $claims = [
            'iat' => time() - 10,
            'exp' => time() + 300,
            'sub' => '1234567890',
            'email' => 'tasks@example.test',
            'email_verified' => true,
        ];
        $this->app->instance(
            VerifiesGoogleIdTokenSignature::class,
            new Phase10AFakeGoogleSignatureVerifier($claims)
        );

        $verified = $this->app->make(GoogleCloudTaskOidcVerifier::class)
            ->verify($this->bearerToken());
        $this->assertSame('tasks@example.test', $verified['email']);

        $claims['email'] = 'different@example.test';
        $this->app->instance(
            VerifiesGoogleIdTokenSignature::class,
            new Phase10AFakeGoogleSignatureVerifier($claims)
        );
        $this->expectException(CloudTaskIdentityException::class);
        $this->app->make(GoogleCloudTaskOidcVerifier::class)
            ->verify($this->bearerToken());
    }

    public function test_stale_generation_and_live_processing_lease_are_handled_safely(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();

        $this->postTask($report, generation: 99)
            ->assertOk()
            ->assertJsonPath('code', 'TASK_GENERATION_STALE');

        $report->forceFill([
            'ai_processing_status' => ViolationReport::AI_STATUS_PROCESSING,
            'ai_processing_token_hash' => hash('sha256', 'live-ai-owner'),
            'ai_processing_started_at' => now(),
            'ai_processing_expires_at' => now()->addMinute(),
        ])->save();
        $this->postTask($report)
            ->assertStatus(409)
            ->assertJsonPath('code', 'AI_PROCESSING_LEASE_ACTIVE');
        Http::assertNothingSent();
    }

    public function test_staff_retry_creates_new_generation_without_running_inline_ai(): void
    {
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $report->forceFill([
            'ai_processing_status' => ViolationReport::AI_STATUS_FAILED,
            'processing_error_code' => 'FASTAPI_UNAVAILABLE',
            'processing_error_message' => 'AI processing is temporarily unavailable.',
        ])->save();
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->actingAs($admin)
            ->post(route('violation-reports.retry-ai', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame(2, $report->task_generation);
        $this->assertSame(ViolationReport::TASK_STATUS_CREATED, $report->task_creation_status);
        $this->assertSame(ViolationReport::AI_STATUS_FAILED, $report->ai_processing_status);
        $this->assertSame('FASTAPI_UNAVAILABLE', $report->processing_error_code);
        Http::assertNothingSent();
    }

    private function submit(string $idempotencyKey = 'phase-10a-submission-key-000001')
    {
        return $this->post('/api/mobile/reports', [
            'description' => 'A vehicle is blocking the public road.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'gps_accuracy' => 8.5,
            'timestamp' => '2026-08-10T08:00:00Z',
            'photo' => $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png'),
        ], [
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    private function postTask(ViolationReport $report, ?int $generation = null)
    {
        return $this->postJson('/api/internal/cloud-tasks/process-report-ai', [
            'version' => 'v1',
            'report_id' => $report->id,
            'task_generation' => $generation ?? (int) $report->refresh()->task_generation,
        ], [
            'Authorization' => 'Bearer '.$this->bearerToken(),
        ]);
    }

    private function bearerToken(): string
    {
        return 'valid.cloud.task.token.for.phase10a.tests';
    }
}

class Phase10AFakeCloudTaskCreator implements CreatesCloudTask
{
    /** @var list<CloudTaskDefinition> */
    public array $definitions = [];

    /** @var list<CloudTaskCreationResult|CloudTaskCreationException> */
    public array $outcomes = [];

    public mixed $onCreate = null;

    public function create(CloudTaskDefinition $definition): CloudTaskCreationResult
    {
        $this->definitions[] = $definition;
        if (is_callable($this->onCreate)) {
            ($this->onCreate)($definition);
        }
        $outcome = array_shift($this->outcomes) ?: CloudTaskCreationResult::created();
        if ($outcome instanceof CloudTaskCreationException) {
            throw $outcome;
        }

        return $outcome;
    }
}

class Phase10AAcceptingOidcVerifier implements VerifiesCloudTaskOidc
{
    public function verify(string $token): array
    {
        return ['sub' => '1234567890'];
    }
}

class Phase10ARejectingOidcVerifier implements VerifiesCloudTaskOidc
{
    public function verify(string $token): array
    {
        throw new CloudTaskIdentityException;
    }
}

class Phase10AFakeGoogleSignatureVerifier implements VerifiesGoogleIdTokenSignature
{
    /** @param array<string, mixed> $claims */
    public function __construct(private readonly array $claims) {}

    public function verify(string $token, string $audience, string $issuer): array|false
    {
        return $this->claims;
    }
}

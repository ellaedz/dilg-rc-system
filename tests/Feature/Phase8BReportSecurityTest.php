<?php

namespace Tests\Feature;

use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use App\Services\ReportCredentialService;
use App\Services\ReportNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPhase8cResponses;
use Tests\TestCase;

class Phase8BReportSecurityTest extends TestCase
{
    use CreatesPhase8cResponses;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('report_photos');
        Http::fake([
            '*/v1/predict/multimodal' => Http::response(
                $this->phase8cResponse([
                    'fusion' => ['final_confidence' => 0.82],
                ], 1, 1)
            ),
        ]);
    }

    public function test_phase_8b_schema_is_additive_and_relationships_remain_available(): void
    {
        $expected = [
            'report_number',
            'token_derivation_nonce',
            'tracking_token_hash',
            'idempotency_key_hash',
            'report_status',
            'legacy_verification_status',
            'photo_upload_status',
            'task_creation_status',
            'barangay_assignment_status',
            'ai_manual_review_reason',
            'processing_error_code',
            'processing_error_message',
            'ai_possible_violation',
            'ai_possible_violation_confidence',
            'official_violation_type',
            'verified_by',
            'verified_at',
            'is_duplicate',
            'is_test_data',
            'processed_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('violation_reports', $column), $column);
        }

        $report = $this->createDirectReport();
        ReportTimeline::create([
            'report_id' => $report->id,
            'status' => 'Submitted',
        ]);

        $this->assertSame($report->id, $report->timelines()->firstOrFail()->report_id);
        $this->assertSame('Pending', $report->verification_status);
        $this->assertNull($report->official_violation_type);
    }

    public function test_creation_returns_opaque_token_but_persists_only_nonce_and_hmac(): void
    {
        $response = $this->submit('phase-8b-create-key-00000001');

        $response->assertCreated()
            ->assertJsonPath('data.verification_status', 'Pending')
            ->assertJsonPath('data.idempotent_replay', false)
            ->assertJsonMissingPath('data.tracking_token_hash')
            ->assertJsonMissingPath('data.idempotency_key_hash')
            ->assertJsonMissingPath('data.token_derivation_nonce');

        $token = $response->json('data.tracking_token');
        $reportNumber = $response->json('data.report_number');
        $report = ViolationReport::where('report_number', $reportNumber)->firstOrFail();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        $this->assertMatchesRegularExpression('/^RCV-\d{4}-\d{4,}$/', $reportNumber);
        $this->assertNotSame($token, $report->tracking_token_hash);
        $this->assertSame(
            app(ReportCredentialService::class)->hashTrackingToken($token),
            $report->tracking_token_hash
        );
        $this->assertSame(64, strlen($report->token_derivation_nonce));
        $this->assertFalse($report->is_test_data);
        $this->assertNull($report->official_violation_type);
        $this->assertDatabaseMissing('violation_reports', ['tracking_token_hash' => $token]);
    }

    public function test_public_tracking_accepts_only_the_opaque_token_and_returns_safe_json(): void
    {
        $submission = $this->submit('phase-8b-track-key-000000001');
        $token = $submission->json('data.tracking_token');
        $reportNumber = $submission->json('data.report_number');

        $response = $this->getJson('/api/mobile/reports/status', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.report_number', $reportNumber)
            ->assertJsonPath('data.current_status', 'Submitted')
            ->assertJsonMissingPath('data.tracking_token_hash')
            ->assertJsonMissingPath('data.idempotency_key_hash')
            ->assertJsonMissingPath('data.token_derivation_nonce')
            ->assertJsonMissingPath('data.contact_number')
            ->assertJsonMissingPath('data.ai_raw_response');
        $this->assertStringContainsString(
            'Authorization',
            (string) $response->headers->get('Vary')
        );

        $this->getJson('/api/mobile/reports/status')->assertNotFound();
        $this->getJson('/api/mobile/reports/status', [
            'Authorization' => 'Bearer '.$reportNumber,
        ])->assertNotFound();
        $this->getJson('/api/mobile/reports/status', [
            'Authorization' => 'Bearer '.str_repeat('x', 43),
        ])->assertNotFound();
        $this->getJson('/api/mobile/reports/status?tracking_token='.$token)->assertNotFound();
        $this->getJson('/api/mobile/reports/status/'.$token)->assertNotFound();
    }

    public function test_same_idempotency_key_replays_one_report_without_duplicate_side_effects(): void
    {
        $key = 'phase-8b-replay-key-00000001';
        $first = $this->submit($key, true);
        $second = $this->submit($key, true);

        $first->assertCreated();
        $second->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.report_number', $first->json('data.report_number'))
            ->assertJsonPath('data.tracking_token', $first->json('data.tracking_token'));

        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ReportTimeline::count());
        $this->assertCount(1, Storage::disk('report_photos')->allFiles('reports'));
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_different_idempotency_keys_create_distinct_reports_and_tokens(): void
    {
        $first = $this->submit('phase-8b-distinct-key-000001');
        $second = $this->submit('phase-8b-distinct-key-000002');

        $this->assertNotSame($first->json('data.report_number'), $second->json('data.report_number'));
        $this->assertNotSame($first->json('data.tracking_token'), $second->json('data.tracking_token'));
        $this->assertSame(2, ViolationReport::count());
    }

    public function test_report_number_sequence_is_unique_and_database_constraints_reject_duplicates(): void
    {
        $service = app(ReportNumberService::class);
        $first = $service->next(2031);
        $second = $service->next(2031);

        $this->assertSame('RCV-2031-0001', $first);
        $this->assertSame('RCV-2031-0002', $second);

        $report = $this->createDirectReport([
            'report_id' => $first,
            'report_number' => $first,
        ]);

        $this->expectException(QueryException::class);
        DB::transaction(fn () => $this->createDirectReport([
            'report_id' => 'RCV-2031-9999',
            'report_number' => $report->report_number,
        ]));
    }

    public function test_token_and_idempotency_hash_constraints_reject_duplicates(): void
    {
        $first = $this->createDirectReport();

        try {
            DB::transaction(fn () => $this->createDirectReport([
                'tracking_token_hash' => $first->tracking_token_hash,
            ]));
            $this->fail('The tracking token hash uniqueness constraint was not enforced.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            DB::transaction(fn () => $this->createDirectReport([
                'idempotency_key_hash' => $first->idempotency_key_hash,
            ]));
            $this->fail('The idempotency key hash uniqueness constraint was not enforced.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_approved_verification_states_are_exposed_by_the_domain_config(): void
    {
        $this->assertSame([
            'Pending',
            'Valid Violation',
            'Invalid Report',
            'Duplicate',
            'Outside Jurisdiction',
            'Insufficient Evidence',
        ], BarangayAssignmentService::getVerificationStatuses());
    }

    public function test_ai_output_remains_advisory_and_never_populates_official_classification(): void
    {
        $response = $this->submit('phase-8b-ai-separation-00001', true);
        $report = ViolationReport::where(
            'report_number',
            $response->json('data.report_number')
        )->firstOrFail();

        $this->assertSame('illegal_parking', $report->ai_possible_violation);
        $this->assertSame('0.8200', $report->ai_possible_violation_confidence);
        $this->assertSame('completed', $report->ai_processing_status);
        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);
    }

    private function submit(string $idempotencyKey, bool $withImage = false)
    {
        $payload = [
            'description' => 'A vehicle is blocking the public road.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'timestamp' => '2026-07-28T08:00:00Z',
        ];

        if ($withImage) {
            $payload['photo'] = UploadedFile::fake()->createWithContent(
                'evidence.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            );
        }

        return $this->post('/api/mobile/reports', $payload, [
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    private function createDirectReport(array $overrides = []): ViolationReport
    {
        $credentials = app(ReportCredentialService::class)->issue();
        $reportNumber = app(ReportNumberService::class)->next();

        return ViolationReport::create(array_merge([
            'report_id' => $reportNumber,
            'report_number' => $reportNumber,
            'token_derivation_nonce' => $credentials['token_derivation_nonce'],
            'tracking_token_hash' => $credentials['tracking_token_hash'],
            'idempotency_key_hash' => app(ReportCredentialService::class)
                ->hashIdempotencyKey('direct-report-key-'.bin2hex(random_bytes(12))),
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Direct Phase 8B test report.',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'timestamp' => now(),
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => now()->toDateString(),
        ], $overrides));
    }
}

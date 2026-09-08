<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use App\Services\ReportSubmissionFingerprint;
use App\Support\CitizenViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPhase8cResponses;
use Tests\TestCase;

class Phase8F0MobileUnclassifiedContractTest extends TestCase
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
                $this->phase8cResponse([], 1, 1)
            ),
        ]);
    }

    public function test_mobile_submission_may_omit_citizen_classification_without_exposing_the_sentinel(): void
    {
        $response = $this->submit('phase-8f0-unclassified-000001');

        $response->assertCreated()
            ->assertJsonPath('data.selected_violation_type', null)
            ->assertJsonPath('data.citizen_selected_violation_type', null)
            ->assertJsonPath('data.has_citizen_classification', false)
            ->assertJsonPath('data.verification_status', 'Pending')
            ->assertJsonPath('data.final_ai_category', null);

        $report = ViolationReport::where(
            'report_number',
            $response->json('data.report_number')
        )->firstOrFail();

        $this->assertSame(CitizenViolationType::UNCLASSIFIED, $report->selected_violation_type);
        $this->assertNull($report->citizen_selected_violation_type);
        $this->assertFalse($report->has_citizen_classification);
        $this->assertSame(CitizenViolationType::STAFF_LABEL, $report->citizen_violation_type_label);
        $this->assertSame('Pending', $report->verification_status);
        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);

        $this->withToken($response->json('data.tracking_token'))
            ->getJson('/api/mobile/reports/status')
            ->assertOk()
            ->assertJsonPath('data.citizen_selected_violation_type', null)
            ->assertJsonPath('data.has_citizen_classification', false)
            ->assertJsonPath('data.final_ai_category', null)
            ->assertJsonMissingPath('data.selected_violation_type');
    }

    public function test_internal_sentinel_cannot_be_submitted_by_a_client(): void
    {
        $this->submit('phase-8f0-reject-sentinel-0001', [
            'selected_violation_type' => CitizenViolationType::UNCLASSIFIED,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('selected_violation_type');

        $this->assertDatabaseCount('violation_reports', 0);
    }

    public function test_mobile_submission_routes_to_the_barangay_selected_by_the_citizen(): void
    {
        $response = $this->submit('phase-10c-citizen-barangay-00001', [
            'reported_barangay' => 'Calios',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reported_barangay', 'Calios')
            ->assertJsonPath('data.assigned_barangay_office', 'Barangay Hall - Calios')
            ->assertJsonPath('data.barangay_assignment_status', 'citizen_selected')
            ->assertJsonPath('data.needs_manual_barangay_review', false);

        $report = ViolationReport::where(
            'report_number',
            $response->json('data.report_number')
        )->firstOrFail();

        $this->assertSame('Calios', $report->citizen_reported_barangay);
        $this->assertSame('Calios', $report->effective_barangay);
        $this->assertSame('Barangay Hall - Calios', $report->assigned_barangay_office);
        $this->assertSame('citizen_selected', $report->barangay_assignment_status);
        $this->assertFalse($report->needs_manual_barangay_review);
        $this->assertNull($report->manually_assigned_barangay);

        $this->withToken($response->json('data.tracking_token'))
            ->getJson('/api/mobile/reports/status')
            ->assertOk()
            ->assertJsonPath('data.reported_barangay', 'Calios')
            ->assertJsonPath('data.barangay', 'Calios')
            ->assertJsonPath('data.assigned_barangay_office', 'Barangay Hall - Calios');
    }

    public function test_mobile_submission_rejects_a_barangay_outside_the_santa_cruz_list(): void
    {
        $this->submit('phase-10c-invalid-barangay-000001', [
            'reported_barangay' => 'Not a Santa Cruz barangay',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reported_barangay');

        $this->assertDatabaseCount('violation_reports', 0);
    }

    public function test_ai_training_consent_is_optional_and_recorded_only_when_selected(): void
    {
        $withoutConsent = $this->submit('phase-data-consent-default-00001');
        $withoutConsent->assertCreated();
        $defaultReport = ViolationReport::where(
            'report_number',
            $withoutConsent->json('data.report_number')
        )->firstOrFail();

        $this->assertFalse($defaultReport->ai_training_consent);
        $this->assertNull($defaultReport->ai_training_consent_at);
        $this->assertNull($defaultReport->ai_training_notice_version);

        $withConsent = $this->submit('phase-data-consent-selected-0001', [
            'ai_training_consent' => true,
        ]);
        $withConsent->assertCreated();
        $consentedReport = ViolationReport::where(
            'report_number',
            $withConsent->json('data.report_number')
        )->firstOrFail();

        $this->assertTrue($consentedReport->ai_training_consent);
        $this->assertNotNull($consentedReport->ai_training_consent_at);
        $this->assertSame('2026-09-08', $consentedReport->ai_training_notice_version);
    }

    public function test_legacy_clients_keep_genuine_categories_and_the_category_list_excludes_the_sentinel(): void
    {
        $response = $this->submit('phase-8f0-legacy-category-00001', [
            'selected_violation_type' => 'Other Road Clearing Violation',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.selected_violation_type', 'Other Road Clearing Violation')
            ->assertJsonPath('data.citizen_selected_violation_type', 'Other Road Clearing Violation')
            ->assertJsonPath('data.has_citizen_classification', true);

        $this->getJson('/api/mobile/violation-types')
            ->assertOk()
            ->assertJsonFragment(['Other Road Clearing Violation'])
            ->assertJsonMissing([CitizenViolationType::UNCLASSIFIED]);
    }

    public function test_unclassified_idempotent_replay_is_canonical_and_cannot_be_changed_to_a_citizen_category(): void
    {
        $key = 'phase-8f0-idempotent-unclassified-001';
        $first = $this->submit($key);
        $replay = $this->submit($key);

        $first->assertCreated();
        $replay->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.report_number', $first->json('data.report_number'))
            ->assertJsonPath('data.tracking_token', $first->json('data.tracking_token'))
            ->assertJsonPath('data.selected_violation_type', null);

        $this->submit($key, [
            'selected_violation_type' => 'Illegal Parking',
        ])->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_PAYLOAD_CONFLICT');

        $this->submit($key, [
            'ai_training_consent' => true,
        ])->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_PAYLOAD_CONFLICT');

        $this->assertDatabaseCount('violation_reports', 1);
        $this->assertSame(
            CitizenViolationType::UNCLASSIFIED,
            ViolationReport::firstOrFail()->selected_violation_type
        );
    }

    public function test_fingerprint_uses_one_canonical_value_for_omitted_and_internal_unclassified_state(): void
    {
        $fingerprint = app(ReportSubmissionFingerprint::class);
        $payload = $this->payload();

        $this->assertSame(
            $fingerprint->fromValidated($payload),
            $fingerprint->fromValidated(array_merge($payload, [
                'selected_violation_type' => CitizenViolationType::UNCLASSIFIED,
            ]))
        );
    }

    public function test_server_ai_possible_violation_remains_independent_from_citizen_and_official_fields(): void
    {
        $response = $this->submit(
            'phase-8f0-server-ai-separation-001',
            ['photo' => $this->photo()]
        );

        $response->assertCreated()
            ->assertJsonPath('data.selected_violation_type', null)
            ->assertJsonPath('data.has_citizen_classification', false)
            ->assertJsonPath('data.ai_processing_status', 'completed')
            ->assertJsonPath('data.text_prediction', 'illegal_parking')
            ->assertJsonPath('data.text_confidence', 0.76)
            ->assertJsonPath('data.image_prediction', 'illegal_parking')
            ->assertJsonPath('data.image_confidence', 0.82)
            ->assertJsonPath('data.final_ai_category', 'illegal_parking')
            ->assertJsonPath('data.final_ai_confidence', 0.79)
            ->assertJsonPath('data.verification_status', 'Pending');

        $report = ViolationReport::where(
            'report_number',
            $response->json('data.report_number')
        )->firstOrFail();

        $this->assertSame(CitizenViolationType::UNCLASSIFIED, $report->selected_violation_type);
        $this->assertSame('illegal_parking', $report->ai_possible_violation);
        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);

        $this->withToken($response->json('data.tracking_token'))
            ->getJson('/api/mobile/reports/status')
            ->assertOk()
            ->assertJsonPath('data.description', 'A vehicle is blocking the public road.')
            ->assertJsonPath('data.text_prediction', 'illegal_parking')
            ->assertJsonPath('data.text_confidence', 0.76)
            ->assertJsonPath('data.image_prediction', 'illegal_parking')
            ->assertJsonPath('data.image_confidence', 0.82)
            ->assertJsonPath('data.final_ai_category', 'illegal_parking')
            ->assertJsonPath('data.final_ai_confidence', 0.79);
    }

    public function test_citizen_category_analytics_exclude_the_internal_unclassified_state(): void
    {
        $unclassified = $this->submit('phase-8f0-analytics-unclassified-01');
        $classified = $this->submit('phase-8f0-analytics-classified-001', [
            'selected_violation_type' => 'Illegal Parking',
        ]);

        $unclassified->assertCreated();
        $classified->assertCreated();

        $admin = User::factory()->create([
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('analytics-reports.index'))
            ->assertOk()
            ->assertViewHas('reportsByViolationType', function ($types): bool {
                return $types->pluck('selected_violation_type')->all() === ['Illegal Parking'];
            });

        $this->actingAs($admin)
            ->getJson('/api/gis/hotspots-summary')
            ->assertOk()
            ->assertJsonPath('data.total_mapped_reports', 2)
            ->assertJsonPath('data.violation_type_counts.Illegal Parking', 1)
            ->assertJsonMissingPath('data.violation_type_counts.'.CitizenViolationType::UNCLASSIFIED);
    }

    public function test_staff_view_uses_the_waiting_label_without_rendering_the_internal_literal(): void
    {
        $response = $this->submit('phase-8f0-staff-label-00000001');
        $report = ViolationReport::where(
            'report_number',
            $response->json('data.report_number')
        )->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('violation-reports.show', $report))
            ->assertOk()
            ->assertSee(CitizenViolationType::STAFF_LABEL)
            ->assertSee('Photo Match Score')
            ->assertSee('Text Report Match Score')
            ->assertSee('Combined Confidence')
            ->assertDontSee(CitizenViolationType::UNCLASSIFIED);
    }

    private function submit(string $idempotencyKey, array $overrides = [])
    {
        return $this->post('/api/mobile/reports', array_merge(
            $this->payload(),
            $overrides
        ), [
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    private function payload(): array
    {
        return [
            'description' => 'A vehicle is blocking the public road.',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'gps_accuracy' => 8.5,
            'timestamp' => '2026-07-29T08:00:00Z',
        ];
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'evidence.png',
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            )
        );
    }
}

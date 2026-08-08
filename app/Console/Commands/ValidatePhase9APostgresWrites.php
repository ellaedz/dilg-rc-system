<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\MobileReportApiController;
use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use App\Services\Phase9APostgresSafetyGuard;
use App\Services\ReportCredentialService;
use App\Services\ReportNumberService;
use App\Support\CitizenViolationType;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ValidatePhase9APostgresWrites extends Command
{
    private const IDEMPOTENCY_KEY = 'phase9a-gate3a-controlled-write-validation-v1';

    protected $signature = 'phase9a:validate-postgres-writes';

    protected $description = 'Create and verify one idempotent, marked Gate 3A PostgreSQL test report';

    public function handle(
        Phase9APostgresSafetyGuard $postgresSafetyGuard,
        ReportCredentialService $credentialService,
        ReportNumberService $reportNumberService,
        MobileReportApiController $mobileReportController,
    ): int {
        $connectionName = (string) config('database.default');
        $schema = (string) config('phase9a.final_schema', 'civiclear');

        try {
            $this->assertWriteGate($connectionName, $schema, $postgresSafetyGuard);
            $idempotencyHash = $credentialService->hashIdempotencyKey(self::IDEMPOTENCY_KEY);
            $existing = ViolationReport::query()
                ->where('idempotency_key_hash', $idempotencyHash)
                ->first();
            $created = false;

            if ($existing) {
                $report = $existing;
            } else {
                $credentials = $credentialService->issue();
                $location = BarangayAssignmentService::assignReportLocation(14.281, 121.416);

                $report = DB::transaction(function () use (
                    $credentialService,
                    $credentials,
                    $idempotencyHash,
                    $location,
                    $reportNumberService,
                ): ViolationReport {
                    $reportNumber = $reportNumberService->next();
                    $report = ViolationReport::create([
                        'report_id' => $reportNumber,
                        'report_number' => $reportNumber,
                        'token_derivation_nonce' => $credentials['token_derivation_nonce'],
                        'tracking_token_hash' => $credentials['tracking_token_hash'],
                        'idempotency_key_hash' => $idempotencyHash,
                        'submission_payload_hash' => hash(
                            'sha256',
                            'civiclear:phase9a:gate3a:write-validation:v1',
                        ),
                        'submitted_by' => 'Anonymous Citizen',
                        'contact_number' => null,
                        'description' => '[PHASE 9A GATE 3A TEST DATA] Controlled PostgreSQL write validation.',
                        'selected_violation_type' => CitizenViolationType::UNCLASSIFIED,
                        'predicted_violation_category' => null,
                        'confidence_score' => null,
                        'image_validation_status' => null,
                        'image_model_version' => null,
                        'needs_manual_review' => true,
                        'ai_processing_status' => ViolationReport::AI_STATUS_PENDING,
                        'photo_upload_status' => ViolationReport::PHOTO_STATUS_NOT_PROVIDED,
                        'task_creation_status' => 'not_started',
                        'latitude' => 14.281,
                        'longitude' => 121.416,
                        'gps_accuracy' => 8.5,
                        'timestamp' => now(),
                        'image_path' => null,
                        'status' => 'Submitted',
                        'report_status' => 'Submitted',
                        'verification_status' => 'Pending',
                        'detected_barangay' => $location['detected_barangay'],
                        'assigned_barangay_office' => $location['assigned_barangay_office'],
                        'location_context' => $location['location_context'],
                        'municipality_validated' => $location['municipality_validated'],
                        'municipality_name' => $location['municipality_name'],
                        'barangay_detection_status' => $location['barangay_detection_status'],
                        'barangay_assignment_status' => $this->barangayAssignmentStatus($location),
                        'needs_manual_barangay_review' => $location['needs_manual_barangay_review'],
                        'is_duplicate' => false,
                        'is_test_data' => true,
                        'date_submitted' => now()->toDateString(),
                        'date_updated' => now()->toDateString(),
                    ]);

                    ReportTimeline::create([
                        'report_id' => $report->id,
                        'status' => 'Submitted',
                        'remarks' => '[PHASE 9A GATE 3A TEST DATA] Controlled PostgreSQL write validation.',
                        'updated_by' => null,
                    ]);

                    $rawToken = $credentialService->replayToken($report);

                    if (! hash_equals(
                        (string) $report->tracking_token_hash,
                        $credentialService->hashTrackingToken($rawToken),
                    )) {
                        throw new RuntimeException('The controlled tracking credential did not verify.');
                    }

                    return $report;
                });
                $created = true;
            }

            $this->assertCreatedReport(
                report: $report->fresh(['timelines']),
                idempotencyHash: $idempotencyHash,
                credentialService: $credentialService,
                mobileReportController: $mobileReportController,
            );

            $this->info('Phase 9A controlled PostgreSQL write validation passed.');
            $this->line('Report Number: '.$report->report_number);
            $this->line('New test row created: '.($created ? 'yes' : 'no; verified idempotent replay'));
            $this->line('Marked is_test_data: yes');
            $this->line('Timeline relationship: passed');
            $this->line('Opaque public tracking: passed');
            $this->line('Raw tracking token printed: no');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A PostgreSQL write validation stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function assertWriteGate(
        string $connectionName,
        string $schema,
        Phase9APostgresSafetyGuard $postgresSafetyGuard,
    ): void {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Gate 3A requires Laravel maintenance mode.');
        }

        if (config('phase9a.runtime_read_only')) {
            throw new RuntimeException('Gate 3A requires an explicit temporary read-only override.');
        }

        if (config('phase9a.write_validation_confirmation')
            !== 'approved-after-explicit-gate3a') {
            throw new RuntimeException('The explicit Gate 3A write approval is not present.');
        }

        if ($connectionName !== 'pgsql') {
            throw new RuntimeException('Gate 3A requires the pgsql runtime connection.');
        }

        $configuration = config("database.connections.{$connectionName}");

        if (! is_array($configuration)
            || ! empty($configuration['url'])
            || ($configuration['sslmode'] ?? null) !== 'verify-full'
            || ! is_file((string) ($configuration['sslrootcert'] ?? ''))) {
            throw new RuntimeException('Gate 3A PostgreSQL TLS configuration is not approved.');
        }

        $connection = DB::connection($connectionName);
        $database = (string) $connection->scalar('SELECT current_database()');
        $currentSchema = $connection->scalar('SELECT current_schema()');
        $readOnly = $connection->scalar('SHOW default_transaction_read_only');
        $ssl = $connection->selectOne(
            'SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()'
        );

        if ($database !== (string) config('phase9a.runtime_expected_database', 'postgres')
            || $currentSchema !== $schema
            || $readOnly !== 'off'
            || ! in_array($ssl?->ssl ?? false, [true, 1, '1', 't', 'true'], true)) {
            throw new RuntimeException('Gate 3A PostgreSQL connection state is not approved for writes.');
        }

        $postgresSafetyGuard->assertPrivateSchemaPrivileges($connectionName, $schema);
    }

    private function assertCreatedReport(
        ?ViolationReport $report,
        string $idempotencyHash,
        ReportCredentialService $credentialService,
        MobileReportApiController $mobileReportController,
    ): void {
        if (! $report
            || ! $report->is_test_data
            || $report->verification_status !== 'Pending'
            || $report->official_violation_type !== null
            || $report->timelines->count() !== 1
            || ViolationReport::where('idempotency_key_hash', $idempotencyHash)->count() !== 1) {
            throw new RuntimeException('The controlled test report failed persistence checks.');
        }

        $rawToken = $credentialService->replayToken($report);
        $response = $mobileReportController->status(
            Request::create('/', 'GET'),
            $rawToken,
            $credentialService,
        );
        $rawToken = null;
        $payload = $response->getData(true);

        if ($response->getStatusCode() !== 200
            || ($payload['success'] ?? false) !== true
            || ($payload['data']['report_number'] ?? null) !== $report->report_number) {
            throw new RuntimeException('Controlled public tracking verification failed.');
        }
    }

    /**
     * @param  array<string, mixed>  $location
     */
    private function barangayAssignmentStatus(array $location): string
    {
        if (! empty($location['detected_barangay'])) {
            return 'auto_detected';
        }

        if (($location['barangay_detection_status'] ?? null) === 'outside_coverage') {
            return 'outside_coverage';
        }

        if (($location['barangay_detection_status'] ?? null)
            === 'barangay_boundary_unavailable') {
            return 'barangay_boundary_unavailable';
        }

        return 'manual_assignment_required';
    }
}

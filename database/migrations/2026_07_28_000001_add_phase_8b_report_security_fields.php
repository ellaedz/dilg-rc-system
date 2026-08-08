<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 8A established that baseline report rows 1-10 came from the
     * RoadClearingViolationSeeder. This manifest uses that explicit provenance;
     * it does not infer test data from names or descriptions.
     */
    private const BASELINE_SEEDED_REPORT_IDS = [
        1 => 'RCV-2026-0001',
        2 => 'RCV-2026-0002',
        3 => 'RCV-2026-0003',
        4 => 'RCV-2026-0004',
        5 => 'RCV-2026-0005',
        6 => 'RCV-2026-0006',
        7 => 'RCV-2026-0007',
        8 => 'RCV-2026-0008',
        9 => 'RCV-2026-0009',
        10 => 'RCV-2026-0010',
    ];

    public function up(): void
    {
        Schema::create('report_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('report_number')->nullable()->unique()->after('report_id');
            $table->char('token_derivation_nonce', 64)->nullable()->unique()->after('report_number');
            $table->char('tracking_token_hash', 64)->nullable()->unique()->after('token_derivation_nonce');
            $table->char('idempotency_key_hash', 64)->nullable()->unique()->after('tracking_token_hash');

            $table->string('report_status')->default('Submitted')->index()->after('status');
            $table->string('legacy_verification_status')->nullable()->after('verification_status');

            $table->string('photo_upload_status')->default('pending')->index()->after('ai_processing_status');
            $table->string('task_creation_status')->default('not_started')->index()->after('photo_upload_status');

            $table->string('barangay_assignment_status')->default('manual_assignment_required')
                ->index()->after('barangay_detection_status');

            $table->string('ai_manual_review_reason')->nullable()->after('ai_needs_manual_review');
            $table->string('processing_error_code')->nullable()->index()->after('task_creation_status');
            $table->text('processing_error_message')->nullable()->after('processing_error_code');

            $table->string('ai_possible_violation')->nullable()->after('ai_manual_review_reason');
            $table->decimal('ai_possible_violation_confidence', 5, 4)->nullable()
                ->after('ai_possible_violation');

            $table->string('official_violation_type')->nullable()->after('ai_possible_violation_confidence');
            $table->foreignId('verified_by')->nullable()->after('official_violation_type')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            $table->boolean('is_duplicate')->default(false)->index()->after('verified_at');
            $table->boolean('is_test_data')->default(false)->index()->after('is_duplicate');
            $table->timestamp('processed_at')->nullable()->after('is_test_data');
        });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('verification_status')->default('Pending')->change();
        });

        $this->backfillReports();
    }

    private function backfillReports(): void
    {
        $reports = DB::table('violation_reports')->orderBy('id')->get();
        $usedNumbers = [];
        $yearCounters = [];

        foreach ($reports as $report) {
            if (is_string($report->report_id)
                && preg_match('/^RCV-(\d{4})-(\d{4,})$/', $report->report_id, $matches)
                && ! isset($usedNumbers[$report->report_id])) {
                $usedNumbers[$report->report_id] = true;
                $year = (int) $matches[1];
                $yearCounters[$year] = max($yearCounters[$year] ?? 0, (int) $matches[2]);
            }
        }

        foreach ($reports as $report) {
            $reportNumber = null;

            if (is_string($report->report_id)
                && preg_match('/^RCV-(\d{4})-(\d{4,})$/', $report->report_id)
                && isset($usedNumbers[$report->report_id])) {
                $reportNumber = $report->report_id;
                unset($usedNumbers[$report->report_id]);
            }

            if ($reportNumber === null) {
                $year = (int) substr((string) ($report->created_at ?? now()->year), 0, 4);
                $year = $year >= 2000 && $year <= 9999 ? $year : (int) now()->year;

                do {
                    $yearCounters[$year] = ($yearCounters[$year] ?? 0) + 1;
                    $reportNumber = sprintf('RCV-%04d-%04d', $year, $yearCounters[$year]);
                } while (isset($usedNumbers[$reportNumber]));
            }

            $isKnownSeed = isset(self::BASELINE_SEEDED_REPORT_IDS[(int) $report->id])
                && self::BASELINE_SEEDED_REPORT_IDS[(int) $report->id] === $report->report_id;

            DB::table('violation_reports')->where('id', $report->id)->update([
                'report_number' => $reportNumber,
                'report_status' => $report->status,
                'legacy_verification_status' => $report->verification_status,
                'verification_status' => 'Pending',
                'photo_upload_status' => $report->image_path ? 'uploaded' : 'not_provided',
                'task_creation_status' => 'not_started',
                'barangay_assignment_status' => $this->mapBarangayAssignmentStatus($report),
                'ai_possible_violation' => $report->final_ai_prediction
                    ?: $report->predicted_violation_category,
                'ai_possible_violation_confidence' => $report->final_ai_prediction
                    ? $report->final_ai_confidence
                    : $report->confidence_score,
                'official_violation_type' => null,
                'verified_by' => null,
                'verified_at' => null,
                'is_duplicate' => false,
                'is_test_data' => $isKnownSeed,
                'processed_at' => $report->ai_processed_at,
            ]);
        }

        foreach ($yearCounters as $year => $lastNumber) {
            DB::table('report_number_sequences')->insert([
                'year' => $year,
                'last_number' => $lastNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function mapBarangayAssignmentStatus(object $report): string
    {
        if ($report->manually_assigned_barangay) {
            return 'manually_assigned';
        }

        if ($report->detected_barangay) {
            return 'auto_detected';
        }

        if ($report->barangay_detection_status === 'outside_coverage') {
            return 'outside_coverage';
        }

        if ($report->barangay_detection_status === 'barangay_boundary_unavailable') {
            return 'barangay_boundary_unavailable';
        }

        return 'manual_assignment_required';
    }

    public function down(): void
    {
        DB::table('violation_reports')
            ->whereNotNull('legacy_verification_status')
            ->orderBy('id')
            ->get(['id', 'legacy_verification_status'])
            ->each(function (object $report): void {
                DB::table('violation_reports')->where('id', $report->id)->update([
                    'verification_status' => $report->legacy_verification_status,
                ]);
            });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('verification_status')->default('Unverified')->change();
        });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropUnique(['report_number']);
            $table->dropUnique(['token_derivation_nonce']);
            $table->dropUnique(['tracking_token_hash']);
            $table->dropUnique(['idempotency_key_hash']);
            $table->dropIndex(['report_status']);
            $table->dropIndex(['photo_upload_status']);
            $table->dropIndex(['task_creation_status']);
            $table->dropIndex(['barangay_assignment_status']);
            $table->dropIndex(['processing_error_code']);
            $table->dropIndex(['is_duplicate']);
            $table->dropIndex(['is_test_data']);
            $table->dropColumn([
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
            ]);
        });

        Schema::dropIfExists('report_number_sequences');
    }
};

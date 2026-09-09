<?php

namespace App\Console\Commands;

use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileLegacyBarangayAssignments extends Command
{
    protected $signature = 'gis:reconcile-legacy-barangays
                            {--apply : Persist safe polygon assignments; without this flag the command is read-only}';

    protected $description = 'Re-evaluate legacy reports created before MPDO barangay polygons were available';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $candidateCount = $this->legacyCandidates()->count();

        if ($candidateCount === 0) {
            $this->info('No legacy barangay assignments require reconciliation.');

            return self::SUCCESS;
        }

        $summary = [
            'auto_detected' => 0,
            'manual_review' => 0,
            'outside_coverage' => 0,
            'updated' => 0,
        ];

        $this->info(($apply ? 'Applying' : 'Dry run for').' '.$candidateCount.' legacy report(s).');

        $this->legacyCandidates()->chunkById(100, function ($reports) use ($apply, &$summary): void {
            foreach ($reports as $report) {
                $location = BarangayAssignmentService::assignReportLocation(
                    (float) $report->latitude,
                    (float) $report->longitude,
                );

                $status = $location['barangay_detection_status'];
                $summary[$status === 'auto_detected'
                    ? 'auto_detected'
                    : ($status === 'outside_coverage' ? 'outside_coverage' : 'manual_review')]++;

                $destination = $location['detected_barangay'] ?? $status;
                $this->line($report->report_id.' -> '.$destination);

                if ($apply && $this->persistIfStillEligible($report->id, $location)) {
                    $summary['updated']++;
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Automatic polygon match', $summary['auto_detected']],
                ['Still needs manual review', $summary['manual_review']],
                ['Outside Santa Cruz', $summary['outside_coverage']],
                ['Records updated', $summary['updated']],
            ],
        );

        if (! $apply) {
            $this->warn('Dry run only. Re-run with --apply to persist these results.');
        }

        return self::SUCCESS;
    }

    private function legacyCandidates()
    {
        return ViolationReport::query()
            ->where('needs_manual_barangay_review', true)
            ->where('barangay_detection_status', 'barangay_boundary_unavailable')
            ->whereNull('manually_assigned_barangay')
            ->whereNull('citizen_reported_barangay')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id');
    }

    private function persistIfStillEligible(int $reportId, array $location): bool
    {
        return DB::transaction(function () use ($reportId, $location): bool {
            $report = ViolationReport::query()->lockForUpdate()->find($reportId);

            if (! $report
                || ! $report->needs_manual_barangay_review
                || $report->barangay_detection_status !== 'barangay_boundary_unavailable'
                || $report->manually_assigned_barangay
                || $report->citizen_reported_barangay) {
                return false;
            }

            $report->update([
                'detected_barangay' => $location['detected_barangay'],
                'assigned_barangay_office' => $location['assigned_barangay_office'],
                'location_context' => $location['location_context'],
                'municipality_validated' => $location['municipality_validated'],
                'municipality_name' => $location['municipality_name'],
                'barangay_detection_status' => $location['barangay_detection_status'],
                'needs_manual_barangay_review' => $location['needs_manual_barangay_review'],
                'barangay_assignment_status' => $this->assignmentStatus($location),
                'date_updated' => now(),
            ]);

            if ($location['barangay_detection_status'] === 'auto_detected') {
                ReportTimeline::create([
                    'report_id' => $report->id,
                    'status' => $report->status,
                    'remarks' => 'Barangay automatically reconciled from the validated MPDO polygon: '.$location['detected_barangay'].'.',
                    'updated_by' => null,
                ]);
            }

            return true;
        });
    }

    private function assignmentStatus(array $location): string
    {
        if (! empty($location['detected_barangay'])) {
            return 'auto_detected';
        }

        if ($location['barangay_detection_status'] === 'outside_coverage') {
            return 'outside_coverage';
        }

        return 'manual_assignment_required';
    }
}

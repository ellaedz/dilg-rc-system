<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ViolationReport;
use App\Models\ReportTimeline;
use App\Models\User;

class ReportTimelineSeeder extends Seeder
{
    /**
     * Seed initial timeline entries for existing reports
     */
    public function run(): void
    {
        // Get all existing reports
        $reports = ViolationReport::all();

        foreach ($reports as $report) {
            // Create initial timeline entry for the submitted status
            ReportTimeline::create([
                'report_id' => $report->id,
                'status' => 'Submitted',
                'old_status' => null,
                'remarks' => 'Report submitted by citizen',
                'action_taken' => null,
                'assigned_personnel' => null,
                'updated_by' => null,
                'created_at' => $report->date_submitted ?? $report->created_at,
                'updated_at' => $report->date_submitted ?? $report->created_at,
            ]);

            // If report has progressed beyond submitted, add timeline for current status
            if ($report->status !== 'Submitted') {
                // Get the barangay staff user for this barangay
                $barangayStaff = User::where('role', 'barangay_staff')
                    ->where('assigned_barangay', $report->detected_barangay)
                    ->first();

                ReportTimeline::create([
                    'report_id' => $report->id,
                    'status' => $report->status,
                    'old_status' => 'Submitted',
                    'remarks' => $report->remarks,
                    'action_taken' => $report->action_taken,
                    'assigned_personnel' => $report->assigned_personnel,
                    'updated_by' => $barangayStaff?->id,
                    'created_at' => $report->date_updated ?? $report->updated_at,
                    'updated_at' => $report->date_updated ?? $report->updated_at,
                ]);
            }
        }

        $this->command->info('Timeline entries created for ' . $reports->count() . ' reports');
    }
}

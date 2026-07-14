<?php

namespace App\Http\Controllers;

use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManualBarangayRoutingController extends Controller
{
    public function index()
    {
        $reports = ViolationReport::needsBarangayReview()->latest()->paginate(15);
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('dilg.needs-barangay-review', compact('reports', 'barangays'));
    }

    public function route(Request $request, ViolationReport $report)
    {
        abort_unless($report->needs_manual_barangay_review && ! $report->manually_assigned_barangay, 422, 'This report no longer requires routing.');
        abort_unless($report->municipality_validated, 422, 'Reports outside Santa Cruz cannot be routed to a Santa Cruz barangay.');

        $validated = $request->validate([
            'selected_barangay' => ['required', 'string', Rule::in(BarangayAssignmentService::getAllBarangays())],
            'assignment_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'confirm_assignment' => ['accepted'],
        ]);

        DB::transaction(function () use ($report, $validated, $request) {
            $report->update([
                'manually_assigned_barangay' => $validated['selected_barangay'],
                'manual_assignment_reason' => $validated['assignment_reason'],
                'manual_assignment_by' => $request->user()->id,
                'manual_assignment_at' => now(),
                'needs_manual_barangay_review' => false,
                'assigned_barangay_office' => 'Barangay Hall - '.$validated['selected_barangay'],
                'location_context' => 'Inside Santa Cruz; Temporarily routed by DILG',
                'date_updated' => now(),
            ]);

            ReportTimeline::create([
                'report_id' => $report->id,
                'status' => $report->status,
                'remarks' => 'Temporary DILG Routing to '.$validated['selected_barangay'].': '.$validated['assignment_reason'],
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('dilg.needs-barangay-review.index')
            ->with('success', 'Report '.$report->report_id.' routed to '.$validated['selected_barangay'].'.');
    }
}

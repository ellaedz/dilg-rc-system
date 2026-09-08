<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use App\Services\StaffReportVerificationService;
use App\Support\OfficialViolationType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayIncomingReportController extends Controller
{
    /**
     * Display incoming reports for specific barangay (Submitted or For Verification)
     */
    public function index($barangay, Request $request)
    {
        // Verify barangay exists
        $barangayDetails = BarangayAssignmentService::getBarangayByName($barangay);
        if (! $barangayDetails) {
            abort(404, 'Barangay not found');
        }

        // Query only reports for this barangay
        $query = ViolationReport::forEffectiveBarangay($barangay)
            ->where('verification_status', 'Pending')
            ->whereIn('status', ['Submitted', 'For Verification']);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                    ->orWhere('submitted_by', 'like', "%{$search}%")
                    ->orWhere('selected_violation_type', 'like', "%{$search}%");
            });
        }

        // Filter by violation type
        if ($request->has('violation_type') && $request->violation_type != '') {
            $query->where('selected_violation_type', $request->violation_type);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        $officialViolationTypes = OfficialViolationType::all();

        return view('barangay.incoming-reports', compact('reports', 'barangay', 'officialViolationTypes'));
    }

    /**
     * Verify report (change status to Verified, verification_status to Valid Violation)
     */
    public function verify(
        Request $request,
        $barangay,
        ViolationReport $report,
        StaffReportVerificationService $verificationService,
    ) {
        $validated = $request->validate([
            'official_violation_type' => ['required', 'string', Rule::in(OfficialViolationType::all())],
            'correction_reason' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $verificationService->verify(
            $request->user(),
            $barangay,
            $report,
            $validated['official_violation_type'],
            $validated['remarks'] ?? null,
            $validated['correction_reason'] ?? null,
        );

        return redirect()->route('barangay.incoming-reports', $barangay)
            ->with('success', 'Report verified successfully!');
    }

    /**
     * Reject report
     */
    public function reject(
        Request $request,
        $barangay,
        ViolationReport $report,
        StaffReportVerificationService $verificationService,
    ) {
        $validated = $request->validate([
            'verification_status' => ['required', 'string', Rule::in(StaffReportVerificationService::REJECTION_OUTCOMES)],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $verificationService->reject(
            $request->user(),
            $barangay,
            $report,
            $validated['verification_status'],
            $validated['reason'],
        );

        return redirect()->route('barangay.incoming-reports', $barangay)
            ->with('success', 'Report rejected.');
    }

    /**
     * AJAX: Get real-time incoming reports updates
     */
    public function getUpdates($barangay)
    {
        $reports = ViolationReport::forEffectiveBarangay($barangay)
            ->where('verification_status', 'Pending')
            ->whereIn('status', ['Submitted', 'For Verification'])
            ->select('id', 'report_id', 'status', 'selected_violation_type', 'submitted_by', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'reports' => $reports,
            'count' => $reports->count(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}

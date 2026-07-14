<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ViolationReportController extends Controller
{
    /**
     * Display all violation reports (DILG Admin View Only)
     */
    public function index(Request $request)
    {
        $query = ViolationReport::query();

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                  ->orWhere('submitted_by', 'like', "%{$search}%")
                  ->orWhere('selected_violation_type', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by violation type
        if ($request->has('violation_type') && $request->violation_type != '') {
            $query->where('selected_violation_type', $request->violation_type);
        }

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by verification status
        if ($request->has('verification_status') && $request->verification_status != '') {
            $query->where('verification_status', $request->verification_status);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('violation-reports.index', compact('reports', 'barangays'));
    }

    /**
     * Display specific report with role-based layout detection
     */
    public function show(ViolationReport $violationReport)
    {
        $user = auth()->user();
        
        // Load timelines with user who updated
        $violationReport->load(['timelines.updatedByUser']);
        
        // Check if user is barangay staff
        if ($user->role === 'barangay_staff') {
            // Verify barangay staff can only view reports from their assigned barangay (case-insensitive)
            if (strcasecmp((string) $violationReport->effective_barangay, (string) $user->assigned_barangay) !== 0) {
                abort(403, 'Access denied. You can only view reports from ' . $user->assigned_barangay . '.');
            }
            
            // Use barangay layout and pass barangay name
            return view('violation-reports.show', [
                'violationReport' => $violationReport,
                'isBarangayView' => true,
                'barangayName' => $user->assigned_barangay,
                'barangay' => $user->assigned_barangay  // For sidebar links
            ]);
        }
        
        // DILG Admin can view all reports with DILG layout
        return view('violation-reports.show', [
            'violationReport' => $violationReport,
            'isBarangayView' => false,
            'barangayName' => null
        ]);
    }

    // REMOVED: edit() and update() methods
    // DILG Admin has READ-ONLY access (monitoring/analytics only)
    // Barangay Staff updates handled by dedicated controllers:
    // - BarangayIncomingReportController (verify/reject)
    // - BarangayVerifiedReportController (assign)
    // - BarangayResponseTrackingController (update status/action taken)

    /**
     * AJAX: Get real-time violation reports updates
     */
    public function getUpdates(Request $request)
    {
        $reports = ViolationReport::select('id', 'report_id', 'status', 'detected_barangay', 'selected_violation_type', 'date_updated')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'reports' => $reports,
            'timestamp' => now()->toISOString()
        ]);
    }
}

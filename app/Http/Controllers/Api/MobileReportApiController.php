<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileReportResource;
use App\Http\Resources\ReportStatusResource;
use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class MobileReportApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            'selected_violation_type' => ['required', 'string', 'in:'.implode(',', BarangayAssignmentService::getViolationTypes())],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timestamp' => ['required', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'gps_accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reportId = ViolationReport::generateReportId();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->storeAs('reports', $reportId.'_'.time().'.'.$image->extension(), 'public');
        }

        $location = BarangayAssignmentService::assignReportLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        $report = ViolationReport::create([
            'report_id' => $reportId,
            'submitted_by' => 'Anonymous Citizen',
            'contact_number' => $validated['contact_number'] ?? null,
            'description' => $validated['description'],
            'selected_violation_type' => $validated['selected_violation_type'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'gps_accuracy' => $validated['gps_accuracy'] ?? null,
            'timestamp' => $validated['timestamp'],
            'image_path' => $imagePath,
            'status' => 'Submitted',
            'verification_status' => 'Unverified',
            'detected_barangay' => $location['detected_barangay'],
            'assigned_barangay_office' => $location['assigned_barangay_office'],
            'location_context' => $location['location_context'],
            'municipality_validated' => $location['municipality_validated'],
            'municipality_name' => $location['municipality_name'],
            'barangay_detection_status' => $location['barangay_detection_status'],
            'needs_manual_barangay_review' => $location['needs_manual_barangay_review'],
            'date_submitted' => now()->toDateString(),
            'date_updated' => now()->toDateString(),
        ]);

        ReportTimeline::create([
            'report_id' => $report->id,
            'status' => 'Submitted',
            'remarks' => $report->needs_manual_barangay_review
                ? 'Anonymous report submitted; barangay routing requires DILG review.'
                : 'Anonymous report submitted via mobile API.',
            'updated_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully',
            'data' => (new MobileReportResource($report))->resolve($request),
        ], 201);
    }

    /** Authenticated staff-only detail endpoint. */
    public function show(Request $request, int $id)
    {
        $report = ViolationReport::findOrFail($id);
        $user = $request->user();

        if ($user->role === 'barangay_staff' && strcasecmp((string) $report->effective_barangay, (string) $user->assigned_barangay) !== 0) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report retrieved successfully',
            'data' => (new MobileReportResource($report))->resolve($request),
        ]);
    }

    public function status(Request $request, string $tracking_id)
    {
        $report = ViolationReport::where('report_id', $tracking_id)->firstOrFail();
        $latestTimeline = $report->timelines()->latest()->first();
        $report->setAttribute('latest_public_action', $latestTimeline?->action_taken);

        return response()->json([
            'success' => true,
            'message' => 'Report status retrieved successfully',
            'data' => (new ReportStatusResource($report))->resolve($request),
        ]);
    }

    public function violationTypes()
    {
        return response()->json([
            'success' => true,
            'message' => 'Violation types retrieved successfully',
            'data' => ['violation_types' => BarangayAssignmentService::getViolationTypes()],
        ]);
    }

    public function barangays()
    {
        $barangays = collect(config('santa_cruz_barangays.barangays', []))->map(fn (array $barangay) => [
            'name' => $barangay['name'],
            'office' => $barangay['office'],
        ])->values();

        return response()->json([
            'success' => true,
            'message' => 'Barangays retrieved successfully',
            'data' => ['barangays' => $barangays, 'total' => $barangays->count()],
        ]);
    }
}

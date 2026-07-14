<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangayOfficeResource;
use App\Http\Resources\GISReportResource;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use App\Services\BarangayOfficeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GISApiController extends Controller
{
    public function detectBarangay(Request $request, BarangayOfficeService $officeService)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $location = BarangayAssignmentService::assignReportLocation($latitude, $longitude);
        $nearestOffice = $officeService->findNearestValidatedOffice($latitude, $longitude);

        return response()->json([
            'success' => true,
            'message' => 'Location validation completed',
            'data' => array_merge($location, [
                'recommended_office' => $nearestOffice
                    ? (new BarangayOfficeResource($nearestOffice))->resolve($request)
                    : null,
            ]),
        ]);
    }

    public function reports(Request $request)
    {
        $validated = $request->validate([
            'barangay' => ['nullable', 'string', 'max:100'],
            'violation_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $query = $this->visibleReportsQuery($request)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        if (! empty($validated['barangay'])) {
            $query->forEffectiveBarangay($validated['barangay']);
        }

        if (! empty($validated['violation_type'])) {
            $query->where('selected_violation_type', $validated['violation_type']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $reports = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'GIS reports retrieved successfully',
            'data' => GISReportResource::collection($reports)->resolve($request),
        ]);
    }

    public function barangayOffices(Request $request)
    {
        $offices = collect(config('santa_cruz_barangay_halls', []))
            ->filter(fn (array $office) => isset($office['latitude'], $office['longitude']))
            ->values();
        $needsValidation = $offices->where('validation_status', 'Needs manual validation')->count();

        return response()->json([
            'success' => true,
            'message' => 'Barangay offices retrieved successfully',
            'data' => BarangayOfficeResource::collection($offices)->resolve($request),
            'meta' => [
                'total_offices' => $offices->count(),
                'needs_validation' => $needsValidation,
                'note' => $needsValidation > 0
                    ? 'Office markers are provisional and require LGU validation.'
                    : 'All office coordinates are validated.',
            ],
        ]);
    }

    public function hotspotsSummary(Request $request)
    {
        $base = $this->visibleReportsQuery($request)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        $barangayCounts = (clone $base)
            ->selectRaw('COALESCE(detected_barangay, manually_assigned_barangay) as effective_barangay, COUNT(*) as aggregate')
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(detected_barangay, manually_assigned_barangay)')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'effective_barangay')
            ->toArray();

        $violationCounts = (clone $base)->selectRaw('selected_violation_type, COUNT(*) as aggregate')
            ->groupBy('selected_violation_type')->orderByDesc('aggregate')
            ->pluck('aggregate', 'selected_violation_type')->toArray();

        $statusCounts = (clone $base)->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')->orderByDesc('aggregate')
            ->pluck('aggregate', 'status')->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Hotspot summary retrieved successfully',
            'data' => [
                'total_mapped_reports' => (clone $base)->count(),
                'top_hotspot_barangay' => array_key_first($barangayCounts) ?? 'N/A',
                'most_common_violation_type' => array_key_first($violationCounts) ?? 'N/A',
                'most_common_status' => array_key_first($statusCounts) ?? 'N/A',
                'barangay_report_counts' => $barangayCounts,
                'violation_type_counts' => $violationCounts,
                'status_counts' => $statusCounts,
            ],
        ]);
    }

    private function visibleReportsQuery(Request $request): Builder
    {
        $query = ViolationReport::query();
        $user = $request->user();

        if ($user->role === 'barangay_staff') {
            $query->forEffectiveBarangay($user->assigned_barangay);
        }

        return $query;
    }
}

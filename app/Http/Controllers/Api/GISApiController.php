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
use Illuminate\Validation\Rule;

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
        $validated = $this->validatedFilters($request);
        $this->authorizeRequestedBarangay($request, $validated['barangay'] ?? null);

        $query = $this->applyFilters($this->visibleReportsQuery($request), $validated)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

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
            ->when(
                $request->user()->role === 'barangay_staff',
                fn ($items) => $items->filter(
                    fn (array $office) => strcasecmp(
                        (string) ($office['barangay'] ?? ''),
                        (string) $request->user()->assigned_barangay
                    ) === 0
                )
            )
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
        $validated = $this->validatedFilters($request);
        $this->authorizeRequestedBarangay($request, $validated['barangay'] ?? null);

        $base = $this->applyFilters($this->visibleReportsQuery($request), $validated)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        $barangayCounts = (clone $base)
            ->selectRaw('COALESCE(manually_assigned_barangay, citizen_reported_barangay, detected_barangay) as effective_barangay, COUNT(*) as aggregate')
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')
                    ->orWhereNotNull('citizen_reported_barangay')
                    ->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(manually_assigned_barangay, citizen_reported_barangay, detected_barangay)')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'effective_barangay')
            ->toArray();

        $violationColumn = ($validated['dataset'] ?? 'operational') === 'official'
            ? 'official_violation_type'
            : 'selected_violation_type';
        $violationBase = clone $base;
        if (($validated['dataset'] ?? 'operational') === 'operational') {
            $violationBase->citizenClassified();
        }

        $violationCounts = $violationBase
            ->whereNotNull($violationColumn)
            ->selectRaw($violationColumn.', COUNT(*) as aggregate')
            ->groupBy($violationColumn)->orderByDesc('aggregate')
            ->pluck('aggregate', $violationColumn)->toArray();

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

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'barangay' => [
                'nullable',
                'string',
                Rule::in(array_column(config('santa_cruz_barangays.barangays', []), 'name')),
            ],
            'violation_type' => [
                'nullable',
                'string',
                Rule::in(config('santa_cruz_barangays.violation_types', [])),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(config('santa_cruz_barangays.statuses', [])),
            ],
            'dataset' => ['nullable', 'string', Rule::in(['operational', 'official'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('date_from'), 'after_or_equal:date_from'),
            ],
        ]);
    }

    private function authorizeRequestedBarangay(Request $request, ?string $barangay): void
    {
        $user = $request->user();

        if ($user->role === 'barangay_staff'
            && $barangay !== null
            && strcasecmp((string) $user->assigned_barangay, $barangay) !== 0) {
            abort(403, 'Access denied. GIS data is restricted to your assigned barangay.');
        }
    }

    private function applyFilters(Builder $query, array $validated): Builder
    {
        $dataset = $validated['dataset'] ?? 'operational';

        if ($dataset === 'official') {
            $query->officialStatistics();
        }

        if (! empty($validated['barangay'])) {
            $query->forEffectiveBarangay($validated['barangay']);
        }

        if (! empty($validated['violation_type'])) {
            $query->where(
                $dataset === 'official' ? 'official_violation_type' : 'selected_violation_type',
                $validated['violation_type']
            );
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('date_submitted', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('date_submitted', '<=', $validated['date_to']);
        }

        return $query;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReportAiDispatcher;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobileReportResource;
use App\Http\Resources\ReportStatusResource;
use App\Models\ReportTimeline;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use App\Services\ReportCredentialService;
use App\Services\ReportNumberService;
use App\Services\ReportPhotoPipeline;
use App\Services\ReportSubmissionFingerprint;
use App\Support\CitizenViolationType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MobileReportApiController extends Controller
{
    public function store(
        Request $request,
        ReportAiDispatcher $aiDispatcher,
        ReportCredentialService $credentialService,
        ReportNumberService $reportNumberService,
        ReportPhotoPipeline $photoPipeline,
        ReportSubmissionFingerprint $submissionFingerprint,
    ) {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            // Phase 8F-0 permits the server-AI mobile flow to omit a citizen
            // classification. When supplied by a legacy client, the value must
            // still be a genuine citizen-selectable category. The internal
            // Unclassified sentinel is deliberately absent from this allowlist.
            'selected_violation_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(BarangayAssignmentService::getViolationTypes()),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric', 'min:0'],
            'timestamp' => ['required', 'date'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'image_result' => ['nullable', 'string', 'max:100'],
            'image_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'image_validation_status' => ['nullable', 'string', 'in:accepted,low_confidence,no_detection,error'],
            'image_model_version' => ['nullable', 'string', 'max:100'],
            'needs_manual_review' => ['nullable', 'boolean'],
        ]);

        $validated['selected_violation_type'] = CitizenViolationType::forStorage(
            $validated['selected_violation_type'] ?? null
        );

        $photoWasSupplied = $this->photoWasSupplied($request);
        $image = $this->uploadedPhoto($request);
        $payloadHash = $submissionFingerprint->fromValidated($validated);
        $rawIdempotencyKey = $request->header('Idempotency-Key');

        if ($rawIdempotencyKey !== null
            && (! is_string($rawIdempotencyKey)
                || ! preg_match('/^[A-Za-z0-9._:-]{16,255}$/', $rawIdempotencyKey))) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'The Idempotency-Key header must be a high-entropy opaque value between 16 and 255 characters.',
            ]);
        }

        try {
            $idempotencyKey = $rawIdempotencyKey ?: $credentialService->generateFallbackIdempotencyKey();
            $idempotencyHash = $credentialService->hashIdempotencyKey($idempotencyKey);

            if ($rawIdempotencyKey) {
                $existing = ViolationReport::where('idempotency_key_hash', $idempotencyHash)->first();

                if ($existing) {
                    return $this->replayResponse(
                        $request,
                        $existing,
                        $payloadHash,
                        $photoWasSupplied,
                        $image,
                        $credentialService,
                        $photoPipeline,
                        $aiDispatcher,
                        $submissionFingerprint,
                    );
                }
            }

            $credentials = $credentialService->issue();
        } catch (RuntimeException) {
            return response()->json([
                'success' => false,
                'message' => 'Report submission is temporarily unavailable.',
            ], 503);
        }

        $location = BarangayAssignmentService::assignReportLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        try {
            $report = DB::transaction(function () use (
                $validated,
                $location,
                $credentials,
                $idempotencyHash,
                $payloadHash,
                $reportNumberService,
                $photoWasSupplied,
            ) {
                $reportNumber = $reportNumberService->next();
                $report = ViolationReport::create([
                    'report_id' => $reportNumber,
                    'report_number' => $reportNumber,
                    'token_derivation_nonce' => $credentials['token_derivation_nonce'],
                    'tracking_token_hash' => $credentials['tracking_token_hash'],
                    'idempotency_key_hash' => $idempotencyHash,
                    'submission_payload_hash' => $payloadHash,
                    'submitted_by' => 'Anonymous Citizen',
                    'contact_number' => $validated['contact_number'] ?? null,
                    'description' => $validated['description'],
                    'selected_violation_type' => $validated['selected_violation_type'],
                    // Phase 8E accepts the old mobile fields only for transport
                    // compatibility. Server-side FastAPI evidence is the sole source
                    // for new AI result fields.
                    'predicted_violation_category' => null,
                    'confidence_score' => null,
                    'image_validation_status' => null,
                    'image_model_version' => null,
                    'needs_manual_review' => true,
                    'ai_processing_status' => 'pending',
                    'photo_upload_status' => $photoWasSupplied
                        ? ViolationReport::PHOTO_STATUS_PENDING
                        : ViolationReport::PHOTO_STATUS_NOT_PROVIDED,
                    'task_creation_status' => 'not_started',
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'gps_accuracy' => $validated['gps_accuracy'] ?? null,
                    'timestamp' => $validated['timestamp'],
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
                    'is_test_data' => false,
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

                return $report;
            });
        } catch (QueryException $exception) {
            $existing = $rawIdempotencyKey
                ? ViolationReport::where('idempotency_key_hash', $idempotencyHash)->first()
                : null;

            if (! $existing) {
                throw $exception;
            }

            return $this->replayResponse(
                $request,
                $existing,
                $payloadHash,
                $photoWasSupplied,
                $image,
                $credentialService,
                $photoPipeline,
                $aiDispatcher,
                $submissionFingerprint,
            );
        }

        $photoResult = $photoWasSupplied
            ? $photoPipeline->process($report, $image)
            : null;
        $report->refresh();

        // Phase 8E dispatches only after the report and sanitized private photograph
        // are durable. The inline dispatcher returns a controlled result; AI failure
        // must never discard the report or its credentials.
        if (($photoResult['outcome'] ?? null) === 'uploaded') {
            $aiDispatcher->dispatch($report);
        }
        $report->refresh();

        return $this->submissionResponse(
            $request,
            $report,
            $credentials['raw_tracking_token'],
            false,
            201,
            $photoResult,
        );
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

    public function status(
        Request $request,
        string $tracking_token,
        ReportCredentialService $credentialService
    ) {
        if (! preg_match('/^[A-Za-z0-9_-]{43}$/', $tracking_token)) {
            return $this->trackingNotFoundResponse();
        }

        try {
            $trackingHash = $credentialService->hashTrackingToken($tracking_token);
        } catch (RuntimeException) {
            return response()->json([
                'success' => false,
                'message' => 'Report tracking is temporarily unavailable.',
            ], 503);
        }

        $report = ViolationReport::with('timelines')
            ->where('tracking_token_hash', $trackingHash)
            ->first();

        if (! $report) {
            return $this->trackingNotFoundResponse();
        }
        $latestTimeline = $report->timelines->last();
        $report->setAttribute('latest_public_action', $latestTimeline?->action_taken);

        return response()->json([
            'success' => true,
            'message' => 'Report status retrieved successfully',
            'data' => (new ReportStatusResource($report))->resolve($request),
        ]);
    }

    private function trackingNotFoundResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Report not found.',
        ], 404);
    }

    private function submissionResponse(
        Request $request,
        ViolationReport $report,
        string $rawTrackingToken,
        bool $replayed,
        int $statusCode = 200,
        ?array $photoResult = null,
    ) {
        $report->refresh();
        $data = (new MobileReportResource($report))->resolve($request);
        $data['report_number'] = $report->report_number;
        $data['report_id'] = $report->report_number;
        $data['tracking_token'] = $rawTrackingToken;
        $data['tracking_id'] = $rawTrackingToken;
        $data['idempotent_replay'] = $replayed;
        if ($photoResult !== null) {
            $data['photo_result'] = [
                'status' => $photoResult['status'],
                'error_code' => $photoResult['error_code'],
                'message' => $photoResult['message'],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $replayed
                ? 'Existing report returned for idempotent replay'
                : 'Report submitted successfully',
            'data' => $data,
        ], $statusCode);
    }

    private function replayResponse(
        Request $request,
        ViolationReport $report,
        string $payloadHash,
        bool $photoWasSupplied,
        ?UploadedFile $image,
        ReportCredentialService $credentialService,
        ReportPhotoPipeline $photoPipeline,
        ReportAiDispatcher $aiDispatcher,
        ReportSubmissionFingerprint $submissionFingerprint,
    ) {
        $storedHash = $report->submission_payload_hash
            ?: $submissionFingerprint->fromReport($report);
        if (! hash_equals($storedHash, $payloadHash)) {
            return response()->json([
                'success' => false,
                'message' => 'The Idempotency-Key was already used for different report data.',
                'error' => ['code' => 'IDEMPOTENCY_PAYLOAD_CONFLICT'],
            ], 409);
        }

        if (! $report->submission_payload_hash) {
            $report->forceFill(['submission_payload_hash' => $storedHash])->save();
        }

        try {
            $rawTrackingToken = $credentialService->replayToken($report);
        } catch (RuntimeException) {
            return response()->json([
                'success' => false,
                'message' => 'Report submission replay is temporarily unavailable.',
            ], 503);
        }

        $photoResult = $photoWasSupplied
            ? $photoPipeline->process($report, $image)
            : null;

        if (($photoResult['outcome'] ?? null) === 'conflict') {
            return response()->json([
                'success' => false,
                'message' => $photoResult['message'],
                'error' => ['code' => $photoResult['error_code']],
            ], 409);
        }

        $report->refresh();
        if (($photoResult['outcome'] ?? null) === 'uploaded'
            && $report->ai_processing_status === ViolationReport::AI_STATUS_PENDING
            && (int) $report->ai_processing_attempts === 0) {
            // A public replay may complete the first failed Phase 8D photograph
            // upload. It may start the first AI attempt only; it cannot retry AI.
            $aiDispatcher->dispatch($report);
            $report->refresh();
        }

        $statusCode = ($photoResult['status'] ?? null) === ViolationReport::PHOTO_STATUS_PROCESSING
            ? 202
            : 200;

        return $this->submissionResponse(
            $request,
            $report,
            $rawTrackingToken,
            true,
            $statusCode,
            $photoResult,
        );
    }

    private function photoWasSupplied(Request $request): bool
    {
        return $request->file('photo') !== null
            || $request->file('image') !== null
            || $request->has('photo')
            || $request->has('image');
    }

    private function uploadedPhoto(Request $request): ?UploadedFile
    {
        $photo = $request->file('photo') ?? $request->file('image');

        return $photo instanceof UploadedFile ? $photo : null;
    }

    private function barangayAssignmentStatus(array $location): string
    {
        if (! empty($location['detected_barangay'])) {
            return 'auto_detected';
        }

        if (($location['barangay_detection_status'] ?? null) === 'outside_coverage') {
            return 'outside_coverage';
        }

        if (($location['barangay_detection_status'] ?? null) === 'barangay_boundary_unavailable') {
            return 'barangay_boundary_unavailable';
        }

        return 'manual_assignment_required';
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

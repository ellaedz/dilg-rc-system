<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViolationReport;
use App\Services\ProcessReportAi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CloudTaskHandlerController extends Controller
{
    public function __invoke(
        Request $request,
        ProcessReportAi $processor,
    ): JsonResponse {
        $payload = $request->json()->all();
        if (! $this->validPayload($payload)) {
            return $this->acknowledge('TASK_PAYLOAD_INVALID');
        }

        $reportId = (int) $payload['report_id'];
        $generation = (int) $payload['task_generation'];
        $state = DB::transaction(function () use ($reportId, $generation): string {
            $report = ViolationReport::whereKey($reportId)->lockForUpdate()->first();
            if (! $report) {
                return 'missing';
            }

            if ((int) $report->task_generation !== $generation) {
                return 'stale_generation';
            }

            if ($report->ai_processing_status === ViolationReport::AI_STATUS_COMPLETED) {
                return 'completed';
            }

            if (in_array($report->task_creation_status, [
                ViolationReport::TASK_STATUS_CREATING,
                ViolationReport::TASK_STATUS_UNCERTAIN,
                ViolationReport::TASK_STATUS_FAILED,
            ], true)) {
                $report->forceFill([
                    'task_creation_status' => ViolationReport::TASK_STATUS_CREATED,
                    'task_creation_token_hash' => null,
                    'task_creation_started_at' => null,
                    'task_creation_expires_at' => null,
                    'task_created_at' => $report->task_created_at ?: now(),
                    'task_creation_error_code' => null,
                    'task_creation_error_message' => null,
                ])->save();
            }

            return 'process';
        }, 3);

        if ($state !== 'process') {
            return $this->acknowledge(match ($state) {
                'missing' => 'TASK_REPORT_NOT_FOUND',
                'stale_generation' => 'TASK_GENERATION_STALE',
                default => 'AI_ALREADY_COMPLETED',
            });
        }

        $report = ViolationReport::find($reportId);
        if (! $report) {
            return $this->acknowledge('TASK_REPORT_NOT_FOUND');
        }

        $result = $processor->process(
            $report,
            ProcessReportAi::TRIGGER_CLOUD_TASK_DELIVERY,
        );

        if ($result->completed()) {
            return $this->acknowledge('AI_COMPLETED');
        }

        if ($result->outcome === 'already_processing') {
            return $this->retry('AI_PROCESSING_LEASE_ACTIVE', 409);
        }

        if ($result->outcome === 'stale_ownership') {
            $current = ViolationReport::find($reportId);

            return $current?->ai_processing_status === ViolationReport::AI_STATUS_COMPLETED
                ? $this->acknowledge('AI_ALREADY_COMPLETED')
                : $this->retry('AI_PROCESSING_OWNERSHIP_CHANGED', 409);
        }

        if ($result->outcome === 'not_eligible'
            || in_array($result->errorCode, $this->permanentErrorCodes(), true)) {
            return $this->acknowledge($result->errorCode ?: 'TASK_WORK_NOT_ELIGIBLE');
        }

        return $this->retry($result->errorCode ?: 'AI_PROCESSING_RETRY_REQUIRED', 500);
    }

    /** @param array<string, mixed> $payload */
    private function validPayload(array $payload): bool
    {
        return count($payload) === 3
            && ($payload['version'] ?? null) === config('cloud_tasks.payload_version', 'v1')
            && is_int($payload['report_id'] ?? null)
            && $payload['report_id'] > 0
            && is_int($payload['task_generation'] ?? null)
            && $payload['task_generation'] > 0;
    }

    /** @return list<string> */
    private function permanentErrorCodes(): array
    {
        return [
            'AI_PHOTO_NOT_READY',
            'AI_PHOTO_UNAVAILABLE',
            'AI_PHOTO_SIZE_INVALID',
            'AI_PHOTO_READ_FAILED',
            'AI_PHOTO_INTEGRITY_MISMATCH',
            'AI_REPORT_EVIDENCE_INVALID',
            'FASTAPI_REQUEST_REJECTED',
            'FASTAPI_SCHEMA_INVALID',
            'FASTAPI_INVALID_JSON',
            'FASTAPI_INVALID_ERROR_RESPONSE',
            'FASTAPI_RESPONSE_TOO_LARGE',
            'FASTAPI_IMAGE_DIMENSIONS_MISMATCH',
        ];
    }

    private function acknowledge(string $code): JsonResponse
    {
        return response()->json([
            'success' => true,
            'acknowledged' => true,
            'code' => $code,
        ]);
    }

    private function retry(string $code, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'retry' => true,
            'code' => $code,
        ], $status);
    }
}

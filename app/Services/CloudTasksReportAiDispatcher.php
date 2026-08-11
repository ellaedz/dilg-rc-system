<?php

namespace App\Services;

use App\Contracts\CreatesCloudTask;
use App\Contracts\ReportAiDispatcher;
use App\Data\AiProcessingResult;
use App\Data\CloudTaskCreationResult;
use App\Data\CloudTaskDefinition;
use App\Exceptions\CloudTaskCreationException;
use App\Models\ViolationReport;
use Illuminate\Support\Facades\DB;
use Throwable;

class CloudTasksReportAiDispatcher implements ReportAiDispatcher
{
    private const MODE_RECOVERY_SAME_GENERATION = 'recovery_same_generation';

    private const MODE_RECOVERY_NEW_GENERATION = 'recovery_new_generation';

    public function __construct(
        private readonly CreatesCloudTask $creator,
        private readonly CloudTasksConfiguration $configuration,
    ) {}

    public function dispatch(
        ViolationReport $report,
        string $trigger = ReportAiDispatcher::TRIGGER_INITIAL,
    ): AiProcessingResult {
        if (! in_array($trigger, [
            ReportAiDispatcher::TRIGGER_INITIAL,
            ReportAiDispatcher::TRIGGER_STAFF_RETRY,
        ], true)) {
            return $this->notEligible('TASK_TRIGGER_INVALID');
        }

        return $this->dispatchMode($report, $trigger);
    }

    public function recoverSameGeneration(ViolationReport $report): AiProcessingResult
    {
        return $this->dispatchMode($report, self::MODE_RECOVERY_SAME_GENERATION);
    }

    public function recoverWithNewGeneration(ViolationReport $report): AiProcessingResult
    {
        return $this->dispatchMode($report, self::MODE_RECOVERY_NEW_GENERATION);
    }

    private function dispatchMode(ViolationReport $report, string $mode): AiProcessingResult
    {
        try {
            $claim = $this->claim($report, $mode);
        } catch (Throwable) {
            return new AiProcessingResult(
                'failed',
                'TASK_CREATION_CLAIM_FAILED',
                'AI dispatch could not be started safely.'
            );
        }

        if ($claim instanceof AiProcessingResult) {
            return $claim;
        }

        try {
            $this->configuration->assertDispatchReady();
        } catch (Throwable) {
            return $this->finalizeFailure(
                $report->id,
                $claim['generation'],
                $claim['token_hash'],
                CloudTaskCreationException::DEFINITIVE,
            );
        }

        try {
            $creation = $this->creator->create(new CloudTaskDefinition(
                $claim['task_id'],
                [
                    'version' => (string) config('cloud_tasks.payload_version', 'v1'),
                    'report_id' => $report->id,
                    'task_generation' => $claim['generation'],
                ],
            ));

            return $this->finalizeCreated(
                $report->id,
                $claim['generation'],
                $claim['token_hash'],
                $creation,
            );
        } catch (CloudTaskCreationException $exception) {
            return $this->finalizeFailure(
                $report->id,
                $claim['generation'],
                $claim['token_hash'],
                $exception->outcome,
            );
        } catch (Throwable) {
            return $this->finalizeFailure(
                $report->id,
                $claim['generation'],
                $claim['token_hash'],
                CloudTaskCreationException::UNCERTAIN,
            );
        }
    }

    /** @return array{generation:int,task_id:string,token_hash:string}|AiProcessingResult */
    private function claim(ViolationReport $report, string $mode): array|AiProcessingResult
    {
        $tokenHash = hash('sha256', random_bytes(32));
        $now = now();
        $expiresAt = $now->copy()->addSeconds(
            (int) config('cloud_tasks.creation_claim_seconds', 30)
        );

        return DB::transaction(function () use (
            $report,
            $mode,
            $tokenHash,
            $now,
            $expiresAt,
        ): array|AiProcessingResult {
            $locked = ViolationReport::whereKey($report->id)->lockForUpdate()->first();
            if (! $locked) {
                return $this->notEligible('REPORT_NOT_FOUND');
            }

            if ($locked->ai_processing_status === ViolationReport::AI_STATUS_COMPLETED) {
                return $this->notEligible('AI_ALREADY_COMPLETED');
            }

            if ($locked->photo_upload_status !== ViolationReport::PHOTO_STATUS_UPLOADED
                || ! is_string($locked->photo_object_key)
                || $locked->photo_object_key === '') {
                return $this->notEligible('AI_PHOTO_NOT_READY');
            }

            if ($locked->task_creation_status === ViolationReport::TASK_STATUS_CREATING
                && $locked->task_creation_expires_at?->isFuture()) {
                return new AiProcessingResult(
                    'already_dispatching',
                    null,
                    'AI dispatch is already in progress.'
                );
            }

            if ($locked->ai_processing_status === ViolationReport::AI_STATUS_PROCESSING
                && $locked->ai_processing_expires_at?->isFuture()) {
                return new AiProcessingResult(
                    'already_processing',
                    null,
                    'AI processing is already in progress.',
                    $locked->ai_request_id,
                );
            }

            $generation = (int) $locked->task_generation;
            $attempts = (int) $locked->ai_processing_attempts;
            $taskStatus = (string) $locked->task_creation_status;
            $eligible = false;

            if ($mode === ReportAiDispatcher::TRIGGER_INITIAL) {
                $eligible = $taskStatus === ViolationReport::TASK_STATUS_NOT_STARTED
                    && $generation === 0
                    && $locked->ai_processing_status === ViolationReport::AI_STATUS_PENDING
                    && $attempts === 0;
                $generation = 1;
            } elseif ($mode === ReportAiDispatcher::TRIGGER_STAFF_RETRY) {
                $eligible = in_array($locked->ai_processing_status, [
                    ViolationReport::AI_STATUS_PENDING,
                    ViolationReport::AI_STATUS_FAILED,
                    ViolationReport::AI_STATUS_PROCESSING,
                ], true);
                $generation = max(0, $generation) + 1;
            } elseif ($mode === self::MODE_RECOVERY_SAME_GENERATION) {
                $eligible = $locked->ai_processing_status === ViolationReport::AI_STATUS_PENDING
                    && $attempts === 0
                    && (
                        in_array($taskStatus, [
                            ViolationReport::TASK_STATUS_NOT_STARTED,
                            ViolationReport::TASK_STATUS_FAILED,
                            ViolationReport::TASK_STATUS_UNCERTAIN,
                        ], true)
                        || ($taskStatus === ViolationReport::TASK_STATUS_CREATING
                            && $locked->task_creation_expires_at?->isPast())
                    );
                $generation = max(1, $generation);
            } elseif ($mode === self::MODE_RECOVERY_NEW_GENERATION) {
                $eligible = $taskStatus === ViolationReport::TASK_STATUS_CREATED
                    && $locked->ai_processing_status === ViolationReport::AI_STATUS_PENDING
                    && $attempts === 0
                    && $locked->task_created_at?->lte(
                        $now->copy()->subSeconds(
                            (int) config('cloud_tasks.stale_dispatch_seconds', 900)
                        )
                    );
                $generation = max(0, $generation) + 1;
            }

            if (! $eligible) {
                return $this->notEligible('TASK_STATE_NOT_ELIGIBLE');
            }

            $taskId = hash('sha256', "civiclear-ai:v1:{$locked->id}:{$generation}");

            $locked->forceFill([
                'task_creation_status' => ViolationReport::TASK_STATUS_CREATING,
                'task_generation' => $generation,
                'task_creation_attempts' => (int) $locked->task_creation_attempts + 1,
                'task_id_hash' => $taskId,
                'task_creation_token_hash' => $tokenHash,
                'task_creation_started_at' => $now,
                'task_creation_expires_at' => $expiresAt,
                'task_last_attempted_at' => $now,
                'task_created_at' => null,
                'task_creation_error_code' => null,
                'task_creation_error_message' => null,
            ])->save();

            return [
                'generation' => $generation,
                'task_id' => $taskId,
                'token_hash' => $tokenHash,
            ];
        }, 3);
    }

    private function finalizeCreated(
        int $reportId,
        int $generation,
        string $tokenHash,
        CloudTaskCreationResult $creation,
    ): AiProcessingResult {
        $updated = ViolationReport::whereKey($reportId)
            ->where('task_generation', $generation)
            ->where('task_creation_status', ViolationReport::TASK_STATUS_CREATING)
            ->where('task_creation_token_hash', $tokenHash)
            ->update([
                'task_creation_status' => ViolationReport::TASK_STATUS_CREATED,
                'task_creation_token_hash' => null,
                'task_creation_started_at' => null,
                'task_creation_expires_at' => null,
                'task_created_at' => now(),
                'task_creation_error_code' => null,
                'task_creation_error_message' => null,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return $this->reconcileAfterLostOwnership($reportId, $generation);
        }

        return new AiProcessingResult(
            'queued',
            null,
            $creation->outcome === CloudTaskCreationResult::ALREADY_EXISTS
                ? 'AI dispatch was reconciled safely.'
                : 'AI processing was queued.'
        );
    }

    private function finalizeFailure(
        int $reportId,
        int $generation,
        string $tokenHash,
        string $outcome,
    ): AiProcessingResult {
        $uncertain = $outcome === CloudTaskCreationException::UNCERTAIN;
        $code = $uncertain ? 'TASK_CREATION_UNCERTAIN' : 'TASK_CREATION_FAILED';
        $message = $uncertain
            ? 'AI dispatch confirmation is delayed.'
            : 'AI dispatch is temporarily unavailable.';

        $updated = ViolationReport::whereKey($reportId)
            ->where('task_generation', $generation)
            ->where('task_creation_status', ViolationReport::TASK_STATUS_CREATING)
            ->where('task_creation_token_hash', $tokenHash)
            ->update([
                'task_creation_status' => $uncertain
                    ? ViolationReport::TASK_STATUS_UNCERTAIN
                    : ViolationReport::TASK_STATUS_FAILED,
                'task_creation_token_hash' => null,
                'task_creation_started_at' => null,
                'task_creation_expires_at' => null,
                'task_creation_error_code' => $code,
                'task_creation_error_message' => $message,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return $this->reconcileAfterLostOwnership($reportId, $generation);
        }

        return new AiProcessingResult(
            $uncertain ? 'uncertain' : 'failed',
            $code,
            $message,
        );
    }

    private function reconcileAfterLostOwnership(
        int $reportId,
        int $generation,
    ): AiProcessingResult {
        $current = ViolationReport::find($reportId);
        if ($current
            && (int) $current->task_generation === $generation
            && ($current->task_creation_status === ViolationReport::TASK_STATUS_CREATED
                || $current->ai_processing_status === ViolationReport::AI_STATUS_COMPLETED)) {
            return new AiProcessingResult(
                'queued',
                null,
                'AI dispatch was reconciled safely.'
            );
        }

        return new AiProcessingResult(
            'stale_ownership',
            'TASK_CREATION_STALE_OWNERSHIP',
            'The AI dispatch result was superseded.'
        );
    }

    private function notEligible(string $code): AiProcessingResult
    {
        return new AiProcessingResult(
            'not_eligible',
            $code,
            'The report is not eligible for AI dispatch.'
        );
    }
}

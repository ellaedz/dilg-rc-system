<?php

namespace App\Console\Commands;

use App\Models\ViolationReport;
use App\Services\CloudTasksReportAiDispatcher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RecoverPhase10ACloudTaskDispatch extends Command
{
    protected $signature = 'phase10a:recover-ai-dispatch
        {--execute : Enqueue eligible reports after the dry run is reviewed}
        {--limit= : Maximum reports to inspect}';

    protected $description = 'Audit or recover eligible Phase 10A Cloud Task dispatches';

    public function handle(CloudTasksReportAiDispatcher $dispatcher): int
    {
        if (config('cloud_tasks.dispatcher') !== 'cloud_tasks') {
            $this->error('Phase 10A recovery stopped safely: Cloud Tasks is not the active dispatcher.');

            return self::FAILURE;
        }

        $limit = $this->validatedLimit();
        if ($limit === null) {
            return self::FAILURE;
        }

        $reports = $this->eligibleQuery()->limit($limit)->get();
        $sameGeneration = $reports->filter(
            fn (ViolationReport $report): bool => ! $this->isStaleCreated($report)
        );
        $newGeneration = $reports->filter(
            fn (ViolationReport $report): bool => $this->isStaleCreated($report)
        );

        $this->line('eligible_same_generation: '.$sameGeneration->count());
        $this->line('eligible_new_generation: '.$newGeneration->count());
        $this->line('cloud_tasks_called: '.($this->option('execute') ? 'yes' : 'no'));

        if (! $this->option('execute')) {
            $this->info('Phase 10A recovery dry run completed. No state was changed.');

            return self::SUCCESS;
        }

        $results = ['queued' => 0, 'uncertain' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($reports as $report) {
            $result = $this->isStaleCreated($report)
                ? $dispatcher->recoverWithNewGeneration($report)
                : $dispatcher->recoverSameGeneration($report);

            $bucket = match ($result->outcome) {
                'queued' => 'queued',
                'uncertain' => 'uncertain',
                'failed' => 'failed',
                default => 'skipped',
            };
            $results[$bucket]++;
        }

        foreach ($results as $name => $count) {
            $this->line("{$name}: {$count}");
        }

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function eligibleQuery(): Builder
    {
        $now = now();
        $staleCreatedAt = $now->copy()->subSeconds(
            (int) config('cloud_tasks.stale_dispatch_seconds', 900)
        );

        return ViolationReport::query()
            ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_UPLOADED)
            ->where('ai_processing_status', ViolationReport::AI_STATUS_PENDING)
            ->where('ai_processing_attempts', 0)
            ->where(function (Builder $query) use ($now, $staleCreatedAt): void {
                $query->whereIn('task_creation_status', [
                    ViolationReport::TASK_STATUS_NOT_STARTED,
                    ViolationReport::TASK_STATUS_FAILED,
                    ViolationReport::TASK_STATUS_UNCERTAIN,
                ])->orWhere(function (Builder $creating) use ($now): void {
                    $creating->where('task_creation_status', ViolationReport::TASK_STATUS_CREATING)
                        ->where('task_creation_expires_at', '<=', $now);
                })->orWhere(function (Builder $created) use ($staleCreatedAt): void {
                    $created->where('task_creation_status', ViolationReport::TASK_STATUS_CREATED)
                        ->where('task_created_at', '<=', $staleCreatedAt);
                });
            })
            ->orderBy('id');
    }

    private function isStaleCreated(ViolationReport $report): bool
    {
        return $report->task_creation_status === ViolationReport::TASK_STATUS_CREATED;
    }

    private function validatedLimit(): ?int
    {
        $raw = $this->option('limit');
        if ($raw === null || $raw === '') {
            return (int) config('cloud_tasks.recovery_batch_size', 50);
        }

        if (! ctype_digit((string) $raw) || (int) $raw < 1 || (int) $raw > 200) {
            $this->error('The recovery limit must be an integer between 1 and 200.');

            return null;
        }

        return (int) $raw;
    }
}

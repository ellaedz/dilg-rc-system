<?php

namespace App\Console\Commands;

use App\Services\AzureAiQueueWorker;
use Illuminate\Console\Command;
use Throwable;

class ProcessPhase10BAzureQueueMessage extends Command
{
    protected $signature = 'phase10b:process-azure-ai-message';

    protected $description = 'Process at most one Azure AI queue message safely';

    public function handle(AzureAiQueueWorker $worker): int
    {
        try {
            $outcome = $worker->processOne();
        } catch (Throwable) {
            $this->error('Phase 10B Azure queue processing stopped safely.');

            return self::FAILURE;
        }

        $this->info("Phase 10B Azure queue outcome: {$outcome}");

        return self::SUCCESS;
    }
}

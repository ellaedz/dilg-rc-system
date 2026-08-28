<?php

namespace App\Services;

use App\Contracts\CreatesCloudTask;
use App\Contracts\InteractsWithAzureQueue;
use App\Data\CloudTaskCreationResult;
use App\Data\CloudTaskDefinition;
use App\Exceptions\AzureQueueOperationException;
use App\Exceptions\CloudTaskCreationException;
use Throwable;

class AzureQueueTaskCreator implements CreatesCloudTask
{
    public function __construct(
        private readonly InteractsWithAzureQueue $queue,
        private readonly AzureQueueConfiguration $configuration,
    ) {}

    public function create(CloudTaskDefinition $definition): CloudTaskCreationResult
    {
        try {
            $this->configuration->assertSenderReady();
            $this->queue->send($this->configuration->string('primary_queue'), [
                'version' => 'azure-queue-v1',
                'task_id' => $definition->taskId,
                'task' => $definition->payload,
            ]);

            return CloudTaskCreationResult::created();
        } catch (AzureQueueOperationException $exception) {
            throw $exception->definitive
                ? CloudTaskCreationException::definitive()
                : CloudTaskCreationException::uncertain();
        } catch (Throwable) {
            throw CloudTaskCreationException::uncertain();
        }
    }
}

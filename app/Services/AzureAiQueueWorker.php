<?php

namespace App\Services;

use App\Contracts\InteractsWithAzureQueue;
use App\Contracts\ProvidesManagedIdentityToken;
use App\Data\AzureQueueMessage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class AzureAiQueueWorker
{
    public const EMPTY = 'empty';

    public const ACKNOWLEDGED = 'acknowledged';

    public const RETRY_SCHEDULED = 'retry_scheduled';

    public const QUARANTINED = 'quarantined';

    public function __construct(
        private readonly InteractsWithAzureQueue $queue,
        private readonly ProvidesManagedIdentityToken $tokens,
        private readonly AzureQueueConfiguration $configuration,
    ) {}

    public function processOne(): string
    {
        config(['azure.queue.worker_context' => true]);
        $this->configuration->assertWorkerReady();
        $primary = $this->configuration->string('primary_queue');
        $message = $this->queue->receive($primary);
        if (! $message) {
            return self::EMPTY;
        }

        $envelope = $this->decodeEnvelope($message->body);
        if ($envelope === null) {
            return $this->quarantine($message, 'QUEUE_MESSAGE_INVALID');
        }

        try {
            $response = $this->invokeHandler($envelope['task']);
        } catch (Throwable) {
            return $this->retryOrQuarantine($message, 'TASK_HANDLER_UNAVAILABLE');
        }

        $payload = $response->json();
        if ($response->successful()
            && is_array($payload)
            && ($payload['acknowledged'] ?? null) === true) {
            $this->queue->delete($primary, $message);

            return self::ACKNOWLEDGED;
        }

        $code = is_array($payload) && is_string($payload['code'] ?? null)
            ? $payload['code']
            : 'TASK_HANDLER_RETRY_REQUIRED';
        if (in_array($response->status(), [400, 404, 410, 422], true)) {
            return $this->quarantine($message, $code);
        }

        return $this->retryOrQuarantine($message, $code);
    }

    /** @return array{version:string,task_id:string,task:array{version:string,report_id:int,task_generation:int}}|null */
    private function decodeEnvelope(string $body): ?array
    {
        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $task = is_array($payload) ? ($payload['task'] ?? null) : null;
        if (! is_array($payload)
            || count($payload) !== 3
            || ($payload['version'] ?? null) !== 'azure-queue-v1'
            || ! is_string($payload['task_id'] ?? null)
            || ! preg_match('/\A[a-f0-9]{64}\z/D', $payload['task_id'])
            || ! is_array($task)
            || count($task) !== 3
            || ($task['version'] ?? null) !== config('cloud_tasks.payload_version', 'v1')
            || ! is_int($task['report_id'] ?? null)
            || $task['report_id'] < 1
            || ! is_int($task['task_generation'] ?? null)
            || $task['task_generation'] < 1) {
            return null;
        }

        return $payload;
    }

    /** @param array{version:string,report_id:int,task_generation:int} $task */
    private function invokeHandler(array $task): Response
    {
        $resource = $this->configuration->string('task_api_resource');
        $clientId = $this->configuration->string('worker_client_id');
        $token = $this->tokens->forResource($resource, $clientId);

        return Http::acceptJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout((int) config('azure.queue.handler_timeout_seconds', 50))
            ->post($this->configuration->string('task_handler_url'), $task);
    }

    private function retryOrQuarantine(AzureQueueMessage $message, string $code): string
    {
        $maximum = (int) config('azure.queue.maximum_dequeue_count', 5);
        if ($message->dequeueCount >= $maximum) {
            return $this->quarantine($message, $code);
        }

        $initial = (int) config('azure.queue.retry_initial_seconds', 10);
        $maximumDelay = (int) config('azure.queue.retry_max_seconds', 300);
        $delay = min($maximumDelay, $initial * (2 ** max(0, $message->dequeueCount - 1)));
        $this->queue->updateVisibility(
            $this->configuration->string('primary_queue'),
            $message,
            $delay,
        );

        return self::RETRY_SCHEDULED;
    }

    private function quarantine(AzureQueueMessage $message, string $code): string
    {
        $this->queue->send($this->configuration->string('quarantine_queue'), [
            'version' => 'azure-quarantine-v1',
            'failure_code' => $code,
            'dequeue_count' => $message->dequeueCount,
            'message_sha256' => hash('sha256', $message->body),
            'original_message' => $message->body,
            'quarantined_at' => now()->toIso8601String(),
        ]);
        $this->queue->delete($this->configuration->string('primary_queue'), $message);

        return self::QUARANTINED;
    }
}

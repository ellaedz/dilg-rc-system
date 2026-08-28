<?php

namespace App\Services;

use App\Contracts\InteractsWithAzureQueue;
use App\Contracts\ProvidesManagedIdentityToken;
use App\Data\AzureQueueMessage;
use App\Exceptions\AzureQueueOperationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use SimpleXMLElement;
use Throwable;

class AzureQueueRestClient implements InteractsWithAzureQueue
{
    private const API_VERSION = '2023-11-03';

    public function __construct(
        private readonly ProvidesManagedIdentityToken $tokens,
        private readonly AzureQueueConfiguration $configuration,
    ) {}

    public function send(string $queue, array $payload): void
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AzureQueueOperationException(true);
        }

        $encoded = base64_encode($json);
        $xml = '<QueueMessage><MessageText>'
            .htmlspecialchars($encoded, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</MessageText></QueueMessage>';
        $url = $this->configuration->endpoint($queue).'/messages';
        $response = $this->request('POST', $url, [
            'messagettl' => (int) config('azure.queue.message_ttl_seconds', 604800),
            'visibilitytimeout' => 0,
        ], $xml);
        $this->expect($response, [201]);
    }

    public function receive(string $queue): ?AzureQueueMessage
    {
        $url = $this->configuration->endpoint($queue).'/messages';
        $response = $this->request('GET', $url, [
            'numofmessages' => 1,
            'visibilitytimeout' => (int) config('azure.queue.receive_visibility_seconds', 60),
        ]);
        $this->expect($response, [200]);

        $xml = $this->xml($response->body());
        $node = $xml->QueueMessage[0] ?? null;
        if (! $node instanceof SimpleXMLElement) {
            return null;
        }

        $body = base64_decode((string) $node->MessageText, true);
        if (! is_string($body)) {
            $body = '';
        }

        return new AzureQueueMessage(
            (string) $node->MessageId,
            (string) $node->PopReceipt,
            max(1, (int) $node->DequeueCount),
            $body,
        );
    }

    public function updateVisibility(
        string $queue,
        AzureQueueMessage $message,
        int $visibilityTimeoutSeconds,
    ): void {
        $url = $this->messageUrl($queue, $message);
        $response = $this->request('PUT', $url, [
            'popreceipt' => $message->popReceipt,
            'visibilitytimeout' => max(1, min(604800, $visibilityTimeoutSeconds)),
        ], '<QueueMessage><MessageText>'
            .htmlspecialchars(base64_encode($message->body), ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</MessageText></QueueMessage>');
        $this->expect($response, [204]);
    }

    public function delete(string $queue, AzureQueueMessage $message): void
    {
        $response = $this->request('DELETE', $this->messageUrl($queue, $message), [
            'popreceipt' => $message->popReceipt,
        ]);
        $this->expect($response, [204]);
    }

    /** @param array<string, scalar> $query */
    private function request(
        string $method,
        string $url,
        array $query,
        ?string $body = null,
    ): Response {
        if ((bool) config('azure.queue.worker_context', false)) {
            $this->configuration->assertWorkerReady();
        } else {
            $this->configuration->assertSenderReady();
        }
        $token = $this->tokens->forResource(
            $this->configuration->string('storage_resource'),
            $this->identityClientId(),
        );

        try {
            $request = Http::withToken($token)
                ->withHeaders([
                    'x-ms-date' => gmdate('D, d M Y H:i:s').' GMT',
                    'x-ms-version' => self::API_VERSION,
                ])
                ->connectTimeout((int) config('azure.queue.connect_timeout_seconds', 3))
                ->timeout((int) config('azure.queue.request_timeout_seconds', 10));
            if ($body !== null) {
                $request = $request->withBody($body, 'application/xml');
            }

            return $request->send($method, $url, ['query' => $query]);
        } catch (Throwable) {
            throw new AzureQueueOperationException(false);
        }
    }

    private function identityClientId(): string
    {
        return app()->runningInConsole()
            && config('azure.queue.worker_context', false)
                ? $this->configuration->string('worker_client_id')
                : $this->configuration->string('laravel_client_id');
    }

    /** @param list<int> $statuses */
    private function expect(Response $response, array $statuses): void
    {
        if (in_array($response->status(), $statuses, true)) {
            return;
        }

        throw new AzureQueueOperationException(in_array($response->status(), [
            400, 401, 403, 404, 409, 411, 413, 415,
        ], true));
    }

    private function messageUrl(string $queue, AzureQueueMessage $message): string
    {
        if (! preg_match('/\A[0-9a-f-]{16,64}\z/iD', $message->messageId)) {
            throw new AzureQueueOperationException(true);
        }

        return $this->configuration->endpoint($queue).'/messages/'.$message->messageId;
    }

    private function xml(string $body): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $xml instanceof SimpleXMLElement) {
            throw new AzureQueueOperationException(false);
        }

        return $xml;
    }
}

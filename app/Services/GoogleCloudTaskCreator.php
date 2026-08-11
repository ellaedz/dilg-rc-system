<?php

namespace App\Services;

use App\Contracts\CreatesCloudTask;
use App\Data\CloudTaskCreationResult;
use App\Data\CloudTaskDefinition;
use App\Exceptions\CloudTaskCreationException;
use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\CreateTaskRequest;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\OidcToken;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Duration;
use JsonException;
use Throwable;

class GoogleCloudTaskCreator implements CreatesCloudTask
{
    public function __construct(private readonly CloudTasksConfiguration $configuration) {}

    public function create(CloudTaskDefinition $definition): CloudTaskCreationResult
    {
        try {
            $this->configuration->assertDispatchReady();
        } catch (Throwable) {
            throw CloudTaskCreationException::definitive();
        }

        try {
            $body = json_encode($definition->payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CloudTaskCreationException::definitive();
        }

        $project = $this->configuration->string('project');
        $location = $this->configuration->string('location');
        $queue = $this->configuration->string('queue');
        $queueName = CloudTasksClient::queueName($project, $location, $queue);
        $taskName = CloudTasksClient::taskName(
            $project,
            $location,
            $queue,
            $definition->taskId,
        );

        $httpRequest = (new HttpRequest)
            ->setHttpMethod(HttpMethod::POST)
            ->setUrl($this->configuration->string('handler_url'))
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setBody($body)
            ->setOidcToken(
                (new OidcToken)
                    ->setServiceAccountEmail(
                        $this->configuration->string('oidc_service_account_email')
                    )
                    ->setAudience($this->configuration->string('oidc_audience'))
            );

        $task = (new Task)
            ->setName($taskName)
            ->setHttpRequest($httpRequest)
            ->setDispatchDeadline(
                (new Duration)->setSeconds(
                    (int) config('cloud_tasks.dispatch_deadline_seconds', 45)
                )
            );

        $request = (new CreateTaskRequest)
            ->setParent($queueName)
            ->setTask($task);

        $client = null;
        try {
            $client = new CloudTasksClient(['transport' => 'rest']);
            $client->createTask($request, [
                'timeoutMillis' => (int) config('cloud_tasks.create_timeout_seconds', 10) * 1000,
            ]);

            return CloudTaskCreationResult::created();
        } catch (ApiException $exception) {
            if ($exception->getStatus() === ApiStatus::ALREADY_EXISTS) {
                return CloudTaskCreationResult::alreadyExists();
            }

            if (in_array($exception->getStatus(), [
                ApiStatus::INVALID_ARGUMENT,
                ApiStatus::PERMISSION_DENIED,
                ApiStatus::UNAUTHENTICATED,
                ApiStatus::NOT_FOUND,
                ApiStatus::FAILED_PRECONDITION,
            ], true)) {
                throw CloudTaskCreationException::definitive();
            }

            throw CloudTaskCreationException::uncertain();
        } catch (CloudTaskCreationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw CloudTaskCreationException::uncertain();
        } finally {
            $client?->close();
        }
    }
}

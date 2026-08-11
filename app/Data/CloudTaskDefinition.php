<?php

namespace App\Data;

final readonly class CloudTaskDefinition
{
    /**
     * @param  array{version:string,report_id:int,task_generation:int}  $payload
     */
    public function __construct(
        public string $taskId,
        public array $payload,
    ) {}
}

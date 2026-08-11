<?php

namespace App\Contracts;

use App\Data\CloudTaskCreationResult;
use App\Data\CloudTaskDefinition;

interface CreatesCloudTask
{
    public function create(CloudTaskDefinition $definition): CloudTaskCreationResult;
}

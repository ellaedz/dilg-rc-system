<?php

namespace App\Contracts;

interface PrivateReportPhotoStorage
{
    public function diskName(): string;

    public function generateObjectKey(string $extension): string;

    public function put(string $objectKey, string $contents): void;

    /** @return resource */
    public function readStream(string $objectKey);

    public function exists(string $objectKey): bool;

    public function delete(string $objectKey): bool;
}

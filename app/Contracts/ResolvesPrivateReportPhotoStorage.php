<?php

namespace App\Contracts;

interface ResolvesPrivateReportPhotoStorage
{
    public function forDisk(string $diskName): PrivateReportPhotoStorage;
}

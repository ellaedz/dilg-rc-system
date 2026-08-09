<?php

namespace App\Contracts;

use DateTimeInterface;

interface TemporaryPrivateReportPhotoUrlProvider
{
    public function temporaryUrl(string $objectKey, DateTimeInterface $expiration): string;
}

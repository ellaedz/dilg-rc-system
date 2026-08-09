<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use RuntimeException;

class ReportPhotoStorageResolver implements ResolvesPrivateReportPhotoStorage
{
    public function __construct(
        private readonly LocalPrivateReportPhotoStorage $local,
        private readonly SupabasePrivateReportPhotoStorage $supabase,
    ) {}

    public function forDisk(string $diskName): PrivateReportPhotoStorage
    {
        return match ($diskName) {
            $this->local->diskName() => $this->local,
            $this->supabase->diskName() => $this->supabase,
            default => throw new RuntimeException('Unknown private photograph storage disk.'),
        };
    }
}

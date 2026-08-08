<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeReportPhotoQuarantine extends Command
{
    protected $signature = 'photos:purge-quarantine';

    protected $description = 'Delete expired objects from the private report-photo quarantine';

    public function handle(): int
    {
        $disk = Storage::disk((string) config(
            'report_photos.quarantine_disk',
            'report_photo_quarantine'
        ));
        $ttlHours = max(1, (int) config('report_photos.quarantine_ttl_hours', 24));
        $cutoff = now()->subHours($ttlHours)->getTimestamp();
        $deleted = 0;
        $retained = 0;
        $failed = 0;

        foreach ($disk->allFiles('quarantine') as $key) {
            if (! preg_match(
                '/\Aquarantine\/[A-Za-z0-9_-]{43}\.(?:jpg|png|webp|bin)\z/D',
                $key
            )) {
                $retained++;

                continue;
            }

            try {
                if ($disk->lastModified($key) > $cutoff) {
                    $retained++;

                    continue;
                }

                $disk->delete($key) ? $deleted++ : $failed++;
            } catch (Throwable) {
                $failed++;
            }
        }

        $this->info("Expired quarantine cleanup complete: deleted={$deleted}, retained={$retained}, failed={$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

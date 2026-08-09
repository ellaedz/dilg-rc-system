<?php

namespace App\Console\Commands;

use App\Services\Phase9BStorageCanary;
use App\Services\SupabasePrivateReportPhotoStorage;
use Illuminate\Console\Command;
use Throwable;

class VerifyPhase9BStorageCanary extends Command
{
    protected $signature = 'phase9b:verify-storage-canary
        {--execute : Upload, verify, expire, and delete one generated canary image}';

    protected $description = 'Validate Phase 9B Supabase private Storage with a disposable generated canary';

    public function handle(
        SupabasePrivateReportPhotoStorage $storage,
        Phase9BStorageCanary $canary,
    ): int {
        try {
            $storage->assertReady();
            if (! $this->option('execute')) {
                $this->info('Phase 9B Supabase S3 configuration passed local safety validation.');
                $this->warn('No object was written. Re-run with --execute only after private-bucket verification.');

                return self::SUCCESS;
            }

            $result = $canary->run();
            foreach ($result as $check => $value) {
                $this->line($check.': '.(is_bool($value) ? ($value ? 'yes' : 'no') : $value));
            }
            $this->info('Phase 9B disposable private Storage canary passed and was deleted.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9B canary stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

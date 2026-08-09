<?php

namespace App\Console\Commands;

use App\Services\Phase9BBackupManifestVerifier;
use App\Services\Phase9BPhotoMigrationService;
use App\Services\Phase9BStage3Guard;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class MigratePhase9BReportPhotos extends Command
{
    protected $signature = 'phase9b:migrate-report-photos
        {--dry-run : Verify source, remote, metadata, and orphan inventory without changes}
        {--execute : Copy verified photographs and switch their PostgreSQL disk references}
        {--backup-manifest= : Absolute path to the verified Phase 9B PostgreSQL backup manifest}';

    protected $description = 'Guard and migrate local report photographs to private Supabase Storage';

    public function handle(
        Phase9BStage3Guard $guard,
        Phase9BBackupManifestVerifier $manifestVerifier,
        Phase9BPhotoMigrationService $migration,
    ): int {
        try {
            $dryRun = (bool) $this->option('dry-run');
            $execute = (bool) $this->option('execute');
            if ($dryRun === $execute) {
                throw new RuntimeException('Choose exactly one of --dry-run or --execute.');
            }

            $connection = $guard->assertReady();
            if ($dryRun) {
                $result = $migration->inspect();
                $this->info('Phase 9B photograph migration dry run passed.');
                $this->printResult($result);
                $this->line('Cloud objects written: no');
                $this->line('PostgreSQL references changed: no');

                return self::SUCCESS;
            }

            $manifestVerifier->verify(
                trim((string) $this->option('backup-manifest')),
                $connection,
            );
            $result = $migration->migrate();
            $this->info('Phase 9B verified photograph migration passed.');
            $this->printResult($result);
            $this->line('Local source photographs deleted: no');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9B photograph migration stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, int> $result */
    private function printResult(array $result): void
    {
        foreach ($result as $name => $value) {
            $this->line($name.': '.$value);
        }
    }
}

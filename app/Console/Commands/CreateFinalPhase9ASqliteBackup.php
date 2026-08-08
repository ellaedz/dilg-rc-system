<?php

namespace App\Console\Commands;

use App\Services\Phase9APostgresSafetyGuard;
use App\Services\Phase9ASqliteBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateFinalPhase9ASqliteBackup extends Command
{
    protected $signature = 'phase9a:create-final-sqlite-backup
        {--destination= : New absolute directory outside the repository}
        {--dry-run : Inspect the approved SQLite source without creating files}';

    protected $description = 'Create a verified immutable SQLite backup for the guarded Phase 9A final import';

    public function handle(
        Phase9APostgresSafetyGuard $guard,
        Phase9ASqliteBackupService $backupService,
    ): int {
        try {
            $connectionName = (string) config('database.default');
            $connection = DB::connection($connectionName);

            if ($connection->getDriverName() !== 'sqlite') {
                throw new \RuntimeException('The final backup requires SQLite as Laravel default.');
            }

            $sourcePath = realpath((string) $connection->getDatabaseName()) ?: '';
            $approvedPath = realpath(database_path('database.sqlite')) ?: '';

            if ($sourcePath === '' || $approvedPath === '' || $sourcePath !== $approvedPath) {
                throw new \RuntimeException('Laravel is not using the approved CIVICLEAR SQLite database.');
            }

            if ((bool) $this->option('dry-run')) {
                $inspection = $backupService->inspect($sourcePath);
                $this->info('Phase 9A final SQLite backup dry run passed.');
                $this->line('Source SHA-256: '.$inspection['source_sha256']);
                $this->line('Source tables: '.count($inspection['table_counts']));
                $this->line('No backup files were created.');

                return self::SUCCESS;
            }

            $guard->assertFinalGateApproval();
            $destination = trim((string) $this->option('destination'));

            if ($destination === '') {
                throw new \RuntimeException('A new final backup destination is required.');
            }

            $result = $backupService->create($sourcePath, $destination);
            $this->info('Phase 9A final SQLite backup passed all checks.');
            $this->line('Backup path: '.$result['backup_path']);
            $this->line('Manifest path: '.$result['manifest_path']);
            $this->line('Backup SHA-256: '.$result['backup_sha256']);
            $this->line('Verified tables: '.count($result['table_counts']));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A final backup stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge();
        }
    }
}

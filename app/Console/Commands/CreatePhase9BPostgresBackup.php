<?php

namespace App\Console\Commands;

use App\Services\Phase9BPostgresBackupService;
use Illuminate\Console\Command;
use Throwable;

class CreatePhase9BPostgresBackup extends Command
{
    protected $signature = 'phase9b:create-postgres-backup
        {--destination= : New absolute backup directory outside the repository}
        {--pg-dump= : Absolute path to pg_dump}
        {--pg-restore= : Absolute path to pg_restore}';

    protected $description = 'Create and verify the guarded Phase 9B PostgreSQL recovery backup';

    public function handle(Phase9BPostgresBackupService $service): int
    {
        try {
            $result = $service->create(
                destinationDirectory: trim((string) $this->option('destination')),
                pgDumpPath: trim((string) ($this->option('pg-dump')
                    ?: config('phase9b.pg_dump_path'))),
                pgRestorePath: trim((string) ($this->option('pg-restore')
                    ?: config('phase9b.pg_restore_path'))),
            );

            $this->info('Phase 9B PostgreSQL recovery backup passed all checks.');
            $this->line('Backup path: '.$result['backup_path']);
            $this->line('Manifest path: '.$result['manifest_path']);
            $this->line('Backup SHA-256: '.$result['backup_sha256']);
            $this->line('Backup size: '.$result['backup_size_bytes']);
            $this->line('Photograph reference digest: '.$result['photo_reference_digest']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9B PostgreSQL backup stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class Phase9BPostgresBackupService
{
    public function __construct(
        private readonly Phase9BStage3Guard $guard,
    ) {}

    /**
     * @return array{backup_path: string, manifest_path: string, backup_sha256: string, backup_size_bytes: int, photo_reference_digest: string}
     */
    public function create(
        string $destinationDirectory,
        string $pgDumpPath,
        string $pgRestorePath,
    ): array {
        $connection = $this->guard->assertReady(requireStorage: false);
        $destinationDirectory = $this->validateDestination($destinationDirectory);
        $pgDumpPath = $this->validateBinary($pgDumpPath, 'pg_dump');
        $pgRestorePath = $this->validateBinary($pgRestorePath, 'pg_restore');
        $before = $this->inventory($connection);

        if (! File::makeDirectory($destinationDirectory, 0700, true)) {
            throw new RuntimeException('The Phase 9B backup directory could not be created.');
        }

        $backupPath = $destinationDirectory.DIRECTORY_SEPARATOR.'civiclear-postgres.dump';
        $manifestPath = $destinationDirectory.DIRECTORY_SEPARATOR.'manifest.json';
        $configuration = config('database.connections.pgsql');
        $environment = [
            'PGPASSWORD' => (string) ($configuration['password'] ?? ''),
            'PGSSLMODE' => 'verify-full',
            'PGSSLROOTCERT' => (string) ($configuration['sslrootcert'] ?? ''),
            'PGAPPNAME' => 'civiclear-phase9b-backup',
        ];
        $timeout = max(60, (int) config('phase9b.backup_timeout_seconds', 300));
        $command = [
            $pgDumpPath,
            '--host='.(string) ($configuration['host'] ?? ''),
            '--port='.(string) ($configuration['port'] ?? '5432'),
            '--username='.(string) ($configuration['username'] ?? ''),
            '--dbname='.(string) ($configuration['database'] ?? ''),
            '--schema='.(string) config('phase9b.schema', 'civiclear'),
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file='.$backupPath,
        ];

        $dump = Process::env($environment)->timeout($timeout)->run($command);
        clearstatcache(true, $backupPath);
        if (! $dump->successful()
            || ! is_file($backupPath)
            || filesize($backupPath) < 5
            || file_get_contents($backupPath, false, null, 0, 5) !== 'PGDMP') {
            throw new RuntimeException(
                'The PostgreSQL logical backup failed or did not produce a valid custom-format dump.'
            );
        }

        $listing = Process::env($environment)->timeout($timeout)->run([
            $pgRestorePath,
            '--list',
            $backupPath,
        ]);
        if (! $listing->successful()
            || ! str_contains($listing->output(), 'civiclear')) {
            throw new RuntimeException('pg_restore could not verify the civiclear backup catalog.');
        }

        $after = $this->inventory($connection);
        if ($before !== $after) {
            throw new RuntimeException('PostgreSQL photograph references changed during backup.');
        }

        $backupHash = hash_file('sha256', $backupPath);
        $backupSize = filesize($backupPath);
        if (! is_string($backupHash) || ! is_int($backupSize) || $backupSize < 5) {
            throw new RuntimeException('The PostgreSQL backup could not be hashed safely.');
        }

        $manifest = [
            'kind' => 'civiclear-phase9b-postgres-backup',
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'database' => (string) config('phase9b.expected_database', 'postgres'),
            'schema' => (string) config('phase9b.schema', 'civiclear'),
            'backup' => [
                'path' => $backupPath,
                'sha256' => strtolower($backupHash),
                'size_bytes' => $backupSize,
                'format' => 'postgresql-custom',
                'catalog_verified' => true,
            ],
            'inventory' => $after,
        ];
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded)
            || File::put($manifestPath, $encoded.PHP_EOL, true) === false) {
            throw new RuntimeException('The PostgreSQL backup manifest could not be written.');
        }

        return [
            'backup_path' => $backupPath,
            'manifest_path' => $manifestPath,
            'backup_sha256' => strtolower($backupHash),
            'backup_size_bytes' => $backupSize,
            'photo_reference_digest' => $after['photo_reference_digest'],
        ];
    }

    /**
     * @return array{reports: int, uploaded_photos: int, local_photo_references: int, supabase_photo_references: int, migration_rows: int, photo_reference_digest: string}
     */
    public function inventory(ConnectionInterface $connection): array
    {
        $rows = $connection->table('violation_reports')
            ->select([
                'id',
                'photo_upload_status',
                'photo_object_key',
                'photo_storage_disk',
                'photo_size_bytes',
                'photo_sha256',
            ])
            ->orderBy('id')
            ->get()
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'status' => $row->photo_upload_status,
                'key' => $row->photo_object_key,
                'disk' => $row->photo_storage_disk,
                'size' => $row->photo_size_bytes === null ? null : (int) $row->photo_size_bytes,
                'sha256' => $row->photo_sha256,
            ])
            ->all();
        $canonical = json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if (! is_string($canonical)) {
            throw new RuntimeException('The PostgreSQL photograph inventory could not be canonicalized.');
        }

        return [
            'reports' => count($rows),
            'uploaded_photos' => count(array_filter(
                $rows,
                static fn (array $row): bool => $row['status'] === 'uploaded'
            )),
            'local_photo_references' => count(array_filter(
                $rows,
                static fn (array $row): bool => $row['status'] === 'uploaded'
                    && $row['disk'] === 'report_photos'
            )),
            'supabase_photo_references' => count(array_filter(
                $rows,
                static fn (array $row): bool => $row['status'] === 'uploaded'
                    && $row['disk'] === 'supabase_report_photos'
            )),
            'migration_rows' => (int) $connection->table('migrations')->count(),
            'photo_reference_digest' => hash('sha256', $canonical),
        ];
    }

    private function validateDestination(string $destinationDirectory): string
    {
        $destinationDirectory = rtrim(trim($destinationDirectory), '\\/');
        if (! preg_match('/\A(?:[A-Za-z]:[\\\\\/]|\/)/D', $destinationDirectory)) {
            throw new RuntimeException('The Phase 9B backup destination must be absolute.');
        }

        if (file_exists($destinationDirectory)) {
            throw new RuntimeException('The Phase 9B backup destination must not already exist.');
        }

        $parent = realpath(dirname($destinationDirectory));
        if (! is_string($parent) || ! is_dir($parent)) {
            throw new RuntimeException('The Phase 9B backup destination parent does not exist.');
        }

        $candidate = $parent.DIRECTORY_SEPARATOR.basename($destinationDirectory);
        $repository = $this->normalizedPath((string) realpath(base_path()));
        if (str_starts_with($this->normalizedPath($candidate), $repository.'/')) {
            throw new RuntimeException('The PostgreSQL backup must remain outside the repository.');
        }

        return $candidate;
    }

    private function validateBinary(string $path, string $name): string
    {
        $resolved = realpath(trim($path));
        if (! is_string($resolved) || ! is_file($resolved)) {
            throw new RuntimeException("The approved {$name} executable is unavailable.");
        }

        return $resolved;
    }

    private function normalizedPath(string $path): string
    {
        return strtolower(rtrim(str_replace('\\', '/', $path), '/'));
    }
}

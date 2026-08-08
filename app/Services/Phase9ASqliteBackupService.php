<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

class Phase9ASqliteBackupService
{
    /**
     * @return array{source_path: string, source_sha256: string, size_bytes: int, table_counts: array<string, int>}
     */
    public function inspect(string $sourcePath): array
    {
        $sourcePath = realpath($sourcePath) ?: '';

        if ($sourcePath === '' || ! is_file($sourcePath)) {
            throw new RuntimeException('The approved SQLite source does not exist.');
        }

        $source = $this->open($sourcePath, true);
        $verification = $this->verifyDatabase($source);
        $sourceHash = hash_file('sha256', $sourcePath);
        $size = filesize($sourcePath);

        if (! is_string($sourceHash) || ! is_int($size)) {
            throw new RuntimeException('The SQLite source could not be hashed.');
        }

        return [
            'source_path' => $sourcePath,
            'source_sha256' => strtolower($sourceHash),
            'size_bytes' => $size,
            'table_counts' => $verification['table_counts'],
        ];
    }

    /**
     * @return array{backup_path: string, manifest_path: string, source_sha256: string, backup_sha256: string, table_counts: array<string, int>}
     */
    public function create(string $sourcePath, string $destinationDirectory): array
    {
        $sourcePath = realpath($sourcePath) ?: '';

        if ($sourcePath === '' || ! is_file($sourcePath)) {
            throw new RuntimeException('The approved SQLite source does not exist.');
        }

        $destinationDirectory = $this->validateDestination($destinationDirectory);
        $source = $this->open($sourcePath, false);
        $this->checkpoint($source);
        $sourceVerification = $this->verifyDatabase($source);
        $sourceHashBefore = hash_file('sha256', $sourcePath);
        $dataVersionBefore = (int) $source->query('PRAGMA data_version')->fetchColumn();

        if (! is_string($sourceHashBefore)) {
            throw new RuntimeException('The SQLite source could not be hashed before backup.');
        }

        if (! mkdir($destinationDirectory, 0700)) {
            throw new RuntimeException('The final backup directory could not be created.');
        }

        $backupPath = $destinationDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
        $manifestPath = $destinationDirectory.DIRECTORY_SEPARATOR.'manifest.json';
        $escapedBackupPath = str_replace("'", "''", str_replace('\\', '/', $backupPath));

        $source->exec("VACUUM INTO '{$escapedBackupPath}'");
        $this->checkpoint($source);
        $source->exec('BEGIN IMMEDIATE');

        try {
            clearstatcache(true, $sourcePath);
            clearstatcache(true, $backupPath);

            $sourceHashAfter = hash_file('sha256', $sourcePath);
            $dataVersionAfter = (int) $source->query('PRAGMA data_version')->fetchColumn();

            if ($dataVersionBefore !== $dataVersionAfter
                || ! is_string($sourceHashAfter)
                || ! hash_equals(strtolower($sourceHashBefore), strtolower($sourceHashAfter))) {
                throw new RuntimeException(
                    'The SQLite source changed while its final backup was created.'
                );
            }

            $backup = $this->open($backupPath, true);
            $backupVerification = $this->verifyDatabase($backup);

            if ($sourceVerification['table_counts'] !== $backupVerification['table_counts']) {
                throw new RuntimeException('The final SQLite backup table counts do not match the source.');
            }

            $backupHash = hash_file('sha256', $backupPath);
            $sourceSize = filesize($sourcePath);
            $backupSize = filesize($backupPath);

            if (! is_string($backupHash) || ! is_int($sourceSize) || ! is_int($backupSize)) {
                throw new RuntimeException('The final SQLite backup could not be hashed.');
            }

            $manifest = [
                'format_version' => 1,
                'kind' => 'civiclear-phase9a-final-sqlite-backup',
                'created_at_utc' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                    ->format(DATE_ATOM),
                'source' => [
                    'path' => $sourcePath,
                    'sha256' => strtolower($sourceHashAfter),
                    'size_bytes' => $sourceSize,
                ],
                'backup' => [
                    'path' => $backupPath,
                    'sha256' => strtolower($backupHash),
                    'size_bytes' => $backupSize,
                ],
                'verification' => [
                    'integrity_check' => 'ok',
                    'foreign_key_violations' => 0,
                    'table_counts' => $backupVerification['table_counts'],
                ],
            ];
            $encodedManifest = json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

            if (! is_string($encodedManifest)
                || file_put_contents($manifestPath, $encodedManifest.PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('The final SQLite backup manifest could not be written.');
            }

            return [
                'backup_path' => $backupPath,
                'manifest_path' => $manifestPath,
                'source_sha256' => strtolower($sourceHashAfter),
                'backup_sha256' => strtolower($backupHash),
                'table_counts' => $backupVerification['table_counts'],
            ];
        } finally {
            $source->exec('ROLLBACK');
        }
    }

    private function open(string $path, bool $queryOnly): PDO
    {
        $database = new PDO('sqlite:'.$path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $database->exec('PRAGMA busy_timeout = 5000');

        if ($queryOnly) {
            $database->exec('PRAGMA query_only = ON');
        }

        return $database;
    }

    /**
     * @return array{table_counts: array<string, int>}
     */
    private function verifyDatabase(PDO $database): array
    {
        if ($database->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('The SQLite database failed integrity_check.');
        }

        if ($database->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            throw new RuntimeException('The SQLite database contains foreign-key violations.');
        }

        $tables = $database->query(
            "SELECT name
             FROM sqlite_master
             WHERE type = 'table' AND name NOT LIKE 'sqlite_%'
             ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);
        $counts = [];

        foreach ($tables as $table) {
            if (! is_string($table)
                || ! preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $table)) {
                throw new RuntimeException('The SQLite database contains an unsafe table name.');
            }

            $counts[$table] = (int) $database
                ->query('SELECT COUNT(*) FROM "'.$table.'"')
                ->fetchColumn();
        }

        return ['table_counts' => $counts];
    }

    private function checkpoint(PDO $database): void
    {
        $result = $database->query('PRAGMA wal_checkpoint(FULL)')->fetch(PDO::FETCH_NUM);

        if (! is_array($result) || (int) ($result[0] ?? 1) !== 0) {
            throw new RuntimeException('The SQLite write-ahead log could not be checkpointed safely.');
        }
    }

    private function validateDestination(string $destinationDirectory): string
    {
        $destinationDirectory = trim($destinationDirectory);

        if ($destinationDirectory === '' || ! $this->isAbsolutePath($destinationDirectory)) {
            throw new RuntimeException('The final backup destination must be an absolute path.');
        }

        if (file_exists($destinationDirectory)) {
            throw new RuntimeException('The final backup destination must not already exist.');
        }

        $parent = realpath(dirname($destinationDirectory)) ?: '';

        if ($parent === '' || ! is_dir($parent)) {
            throw new RuntimeException('The final backup destination parent does not exist.');
        }

        $destinationDirectory = $parent.DIRECTORY_SEPARATOR.basename($destinationDirectory);
        $repository = strtolower(str_replace('\\', '/', realpath(base_path()) ?: base_path()));
        $destination = strtolower(str_replace('\\', '/', $destinationDirectory));

        if ($destination === $repository || str_starts_with($destination.'/', $repository.'/')) {
            throw new RuntimeException('The final SQLite backup must remain outside the repository.');
        }

        return $destinationDirectory;
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR);
    }
}

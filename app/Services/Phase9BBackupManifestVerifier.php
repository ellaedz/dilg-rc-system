<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

class Phase9BBackupManifestVerifier
{
    public function __construct(
        private readonly Phase9BPostgresBackupService $backupService,
    ) {}

    /** @return array<string, mixed> */
    public function verify(string $manifestPath, ConnectionInterface $connection): array
    {
        $resolvedManifest = realpath(trim($manifestPath));
        if (! is_string($resolvedManifest) || ! is_file($resolvedManifest)) {
            throw new RuntimeException('The approved Phase 9B backup manifest is unavailable.');
        }

        $manifest = json_decode((string) file_get_contents($resolvedManifest), true);
        if (! is_array($manifest)
            || ($manifest['kind'] ?? null) !== 'civiclear-phase9b-postgres-backup'
            || ($manifest['version'] ?? null) !== 1
            || ($manifest['database'] ?? null) !== config('phase9b.expected_database')
            || ($manifest['schema'] ?? null) !== config('phase9b.schema')
            || data_get($manifest, 'backup.format') !== 'postgresql-custom'
            || data_get($manifest, 'backup.catalog_verified') !== true) {
            throw new RuntimeException('The Phase 9B backup manifest contract is invalid.');
        }

        $backupPath = realpath((string) data_get($manifest, 'backup.path'));
        $expectedHash = strtolower((string) data_get($manifest, 'backup.sha256'));
        $actualHash = is_string($backupPath) && is_file($backupPath)
            ? hash_file('sha256', $backupPath)
            : false;
        if (! is_string($backupPath)
            || ! preg_match('/\A[0-9a-f]{64}\z/D', $expectedHash)
            || ! is_string($actualHash)
            || ! hash_equals($expectedHash, strtolower($actualHash))) {
            throw new RuntimeException('The Phase 9B PostgreSQL backup hash does not match its manifest.');
        }

        $current = $this->backupService->inventory($connection);
        if (! hash_equals(
            (string) data_get($manifest, 'inventory.photo_reference_digest'),
            $current['photo_reference_digest'],
        )
            || (int) data_get($manifest, 'inventory.reports') !== $current['reports']
            || (int) data_get($manifest, 'inventory.uploaded_photos') !== $current['uploaded_photos']) {
            throw new RuntimeException(
                'PostgreSQL photograph references changed after the approved backup.'
            );
        }

        return $manifest;
    }
}

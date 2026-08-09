<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Phase9BStage4Guard
{
    public function __construct(
        private readonly Phase9APostgresSafetyGuard $postgresSafetyGuard,
        private readonly Phase9BPhotoMigrationService $migration,
    ) {}

    /** @return array{connection: ConnectionInterface, inventory: array<string, int>} */
    public function assertReady(): array
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Phase 9B Stage 4 requires Laravel maintenance mode.');
        }

        if (config('phase9b.stage4_confirmation') !== 'approved-after-explicit-stage4') {
            throw new RuntimeException('The explicit Phase 9B Stage 4 approval is not present.');
        }

        if (config('report_photos.driver') !== 'supabase') {
            throw new RuntimeException('Stage 4 requires the controlled Supabase new-write selector.');
        }

        $connectionName = (string) config('database.default');
        $schema = (string) config('phase9b.schema', 'civiclear');
        $configuration = config("database.connections.{$connectionName}");
        if ($connectionName !== 'pgsql'
            || ! is_array($configuration)
            || ($configuration['driver'] ?? null) !== 'pgsql'
            || ! empty($configuration['url'])
            || ($configuration['search_path'] ?? null) !== $schema
            || ($configuration['sslmode'] ?? null) !== 'verify-full'
            || ! is_file((string) ($configuration['sslrootcert'] ?? ''))) {
            throw new RuntimeException('Phase 9B Stage 4 PostgreSQL configuration is unsafe.');
        }

        $connection = DB::connection($connectionName);
        $database = (string) $connection->scalar('SELECT current_database()');
        $currentSchema = $connection->scalar('SELECT current_schema()');
        $readOnly = $connection->scalar('SHOW default_transaction_read_only');
        $ssl = $connection->selectOne(
            'SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()'
        );
        if (! hash_equals((string) config('phase9b.expected_database'), $database)
            || $currentSchema !== $schema
            || $readOnly !== 'off'
            || ! in_array($ssl?->ssl ?? false, [true, 1, '1', 't', 'true'], true)) {
            throw new RuntimeException('Phase 9B Stage 4 PostgreSQL state is not approved.');
        }

        $this->postgresSafetyGuard->assertPrivateSchemaPrivileges($connectionName, $schema);
        $storage = app(PrivateReportPhotoStorage::class);
        if (! $storage instanceof SupabasePrivateReportPhotoStorage
            || $storage->diskName() !== 'supabase_report_photos') {
            throw new RuntimeException('The active Stage 4 storage adapter is not Supabase.');
        }
        $storage->assertReady();

        $inventory = $this->migration->inspect();
        if ($inventory['local_references'] !== 0
            || $inventory['supabase_references'] !== $inventory['uploaded_photos']) {
            throw new RuntimeException('Stage 3 photograph parity is not complete.');
        }

        return ['connection' => $connection, 'inventory' => $inventory];
    }
}

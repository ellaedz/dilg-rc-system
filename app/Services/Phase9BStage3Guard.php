<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Phase9BStage3Guard
{
    public function __construct(
        private readonly Phase9APostgresSafetyGuard $postgresSafetyGuard,
    ) {}

    public function assertReady(bool $requireStorage = true): ConnectionInterface
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Phase 9B Stage 3 requires Laravel maintenance mode.');
        }

        if (config('phase9b.stage3_confirmation') !== 'approved-after-explicit-stage3') {
            throw new RuntimeException('The explicit Phase 9B Stage 3 approval is not present.');
        }

        if (config('report_photos.driver') !== 'local') {
            throw new RuntimeException('Stage 3 requires new photograph writes to remain local.');
        }

        $connectionName = (string) config('database.default');
        if ($connectionName !== 'pgsql') {
            throw new RuntimeException('Phase 9B Stage 3 requires the PostgreSQL runtime connection.');
        }

        $configuration = config("database.connections.{$connectionName}");
        $schema = (string) config('phase9b.schema', 'civiclear');
        if (! is_array($configuration)
            || ($configuration['driver'] ?? null) !== 'pgsql'
            || ! empty($configuration['url'])
            || ($configuration['search_path'] ?? null) !== $schema
            || ($configuration['sslmode'] ?? null) !== 'verify-full'
            || ! is_file((string) ($configuration['sslrootcert'] ?? ''))) {
            throw new RuntimeException('Phase 9B PostgreSQL TLS or schema configuration is unsafe.');
        }

        $connection = DB::connection($connectionName);
        $expectedDatabase = (string) config('phase9b.expected_database', 'postgres');
        $database = (string) $connection->scalar('SELECT current_database()');
        $currentSchema = $connection->scalar('SELECT current_schema()');
        $readOnly = $connection->scalar('SHOW default_transaction_read_only');
        $ssl = $connection->selectOne(
            'SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()'
        );

        if ($expectedDatabase === ''
            || ! hash_equals($expectedDatabase, $database)
            || $currentSchema !== $schema
            || $readOnly !== 'off'
            || ! in_array($ssl?->ssl ?? false, [true, 1, '1', 't', 'true'], true)) {
            throw new RuntimeException('Phase 9B PostgreSQL connection state is not approved.');
        }

        $this->postgresSafetyGuard->assertPrivateSchemaPrivileges(
            $connectionName,
            $schema,
        );

        if ($requireStorage) {
            app(SupabasePrivateReportPhotoStorage::class)->assertReady();
        }

        return $connection;
    }
}

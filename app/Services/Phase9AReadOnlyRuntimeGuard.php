<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Phase9AReadOnlyRuntimeGuard
{
    public function __construct(
        private readonly Phase9APostgresSafetyGuard $postgresSafetyGuard,
    ) {}

    public function activate(string $connectionName): void
    {
        $configuration = config("database.connections.{$connectionName}");

        if (! is_array($configuration) || ($configuration['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Phase 9A read-only runtime requires PostgreSQL.');
        }

        if (! empty($configuration['url'])) {
            throw new RuntimeException('DB_URL is forbidden during the Phase 9A cutover.');
        }

        if (($configuration['sslmode'] ?? null) !== 'verify-full') {
            throw new RuntimeException('Phase 9A read-only runtime requires sslmode=verify-full.');
        }

        $certificate = $configuration['sslrootcert'] ?? null;

        if (! is_string($certificate) || ! is_file($certificate)) {
            throw new RuntimeException('The Phase 9A runtime CA certificate is unavailable.');
        }

        $connection = DB::connection($connectionName);
        $expectedDatabase = (string) config('phase9a.runtime_expected_database', 'postgres');
        $expectedSchema = (string) config('phase9a.final_schema', 'civiclear');
        $database = (string) $connection->scalar('SELECT current_database()');
        $schema = $connection->scalar('SELECT current_schema()');

        if ($expectedDatabase === '' || ! hash_equals($expectedDatabase, $database)) {
            throw new RuntimeException('The runtime PostgreSQL database is not the approved database.');
        }

        if ($schema !== $expectedSchema) {
            throw new RuntimeException('The runtime PostgreSQL schema is not civiclear.');
        }

        $ssl = $connection->selectOne(
            'SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()'
        );

        if (! $this->postgresBoolean($ssl?->ssl ?? false)) {
            throw new RuntimeException('The runtime PostgreSQL connection is not protected by TLS.');
        }

        $this->postgresSafetyGuard->assertPrivateSchemaPrivileges(
            $connectionName,
            $expectedSchema,
        );
        $connection->unprepared('SET default_transaction_read_only = on');
        $this->assertServerSessionReadOnly($connection);

        $connection->beforeExecuting(function (
            string $query,
            array $bindings,
            ConnectionInterface $activeConnection,
        ): void {
            $this->assertReadOnlySql($query);
            $this->assertServerSessionReadOnly($activeConnection);
        });
    }

    public function assertReadOnlySql(string $query): void
    {
        $query = trim($query);

        if ($query === '') {
            throw new RuntimeException('An empty SQL statement is forbidden in read-only mode.');
        }

        $withoutTrailingTerminator = rtrim($query, "; \t\n\r\0\x0B");

        if (str_contains($withoutTrailingTerminator, ';')
            || ! preg_match('/\A(?:SELECT|SHOW)\b/iD', $withoutTrailingTerminator)) {
            throw new RuntimeException('A database write was blocked by the Phase 9A read-only guard.');
        }
    }

    public function assertServerSessionReadOnly(ConnectionInterface $connection): void
    {
        $statement = $connection->getPdo()->query('SHOW default_transaction_read_only');
        $state = $statement === false ? false : $statement->fetchColumn();

        if (! in_array($state, ['on', true, 1, '1'], true)) {
            throw new RuntimeException('The PostgreSQL session is not locked to read-only mode.');
        }
    }

    private function postgresBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }
}

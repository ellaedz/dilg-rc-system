<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class Phase9APostgresSafetyGuard
{
    /**
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    public function assertDisposableConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
    ): array {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Disposable PostgreSQL operations require APP_ENV=testing.');
        }

        $this->assertDisposableSchemaName($schema);

        return $this->assertConnection(
            connectionName: $connectionName,
            schema: $schema,
            expectedDatabase: $expectedDatabase,
            requireCurrentSchema: true,
        );
    }

    /**
     * Validate the server before a guarded disposable schema is created.
     *
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    public function assertDisposableReadinessConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
    ): array {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Disposable PostgreSQL operations require APP_ENV=testing.');
        }

        $this->assertDisposableSchemaName($schema);

        return $this->assertConnection(
            connectionName: $connectionName,
            schema: $schema,
            expectedDatabase: $expectedDatabase,
            requireCurrentSchema: false,
        );
    }

    public function assertDisposableSchemaName(string $schema): void
    {
        $pattern = (string) config('phase9a.test_schema_pattern');
        $protected = array_map('strtolower', (array) config('phase9a.protected_schemas'));

        if (! preg_match($pattern, $schema)) {
            throw new RuntimeException(
                'Disposable schema must match civiclear_phase9a_test_[A-Za-z0-9_]+.'
            );
        }

        if (in_array(strtolower($schema), $protected, true)) {
            throw new RuntimeException('The selected schema is protected.');
        }
    }

    /**
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    public function assertFinalReadinessConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
    ): array {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Final PostgreSQL dry runs require APP_ENV=testing.');
        }

        $this->assertFinalSchemaName($schema);

        return $this->assertConnection(
            connectionName: $connectionName,
            schema: $schema,
            expectedDatabase: $expectedDatabase,
            requireCurrentSchema: false,
        );
    }

    /**
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    public function assertFinalPreparationConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
    ): array {
        $this->assertFinalSchemaName($schema);
        $this->assertFinalGateApproval();

        return $this->assertConnection(
            connectionName: $connectionName,
            schema: $schema,
            expectedDatabase: $expectedDatabase,
            requireCurrentSchema: false,
        );
    }

    /**
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    public function assertFinalImportConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
    ): array {
        $this->assertFinalSchemaName($schema);
        $this->assertFinalGateApproval();
        $this->assertOperationalTablePolicy();

        $result = $this->assertConnection(
            connectionName: $connectionName,
            schema: $schema,
            expectedDatabase: $expectedDatabase,
            requireCurrentSchema: true,
        );

        $this->assertPrivateSchemaPrivileges($connectionName, $schema);

        return $result;
    }

    public function assertFinalGateApproval(): void
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Final Phase 9A operations require Laravel maintenance mode.');
        }

        if (config('phase9a.final_import_confirmation') !== 'approved-after-explicit-final-import-gate') {
            throw new RuntimeException('The explicit final-import approval gate is not present.');
        }
    }

    public function assertOperationalTablePolicy(): void
    {
        if (config('phase9a.operational_table_policy') !== 'preserve') {
            throw new RuntimeException(
                'Final import requires an explicit preserve policy for operational tables.'
            );
        }
    }

    /**
     * @return array{database: string, schema: ?string, search_path: string, ssl: bool}
     */
    private function assertConnection(
        string $connectionName,
        string $schema,
        string $expectedDatabase,
        bool $requireCurrentSchema,
    ): array {
        if ($expectedDatabase === '') {
            throw new RuntimeException('The expected PostgreSQL database is required.');
        }

        $configuration = config("database.connections.{$connectionName}");

        if (! is_array($configuration) || ($configuration['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('The Phase 9A connection must use PostgreSQL.');
        }

        if (! empty($configuration['url'])) {
            throw new RuntimeException('DB_URL overrides are forbidden for Phase 9A validation.');
        }

        if (($configuration['search_path'] ?? null) !== $schema) {
            throw new RuntimeException('The effective PostgreSQL search path differs from the approved schema.');
        }

        if (($configuration['sslmode'] ?? null) !== 'verify-full') {
            throw new RuntimeException('Phase 9A requires sslmode=verify-full.');
        }

        $certificate = $configuration['sslrootcert'] ?? null;

        if (! is_string($certificate) || ! is_file($certificate)) {
            throw new RuntimeException('The configured Supabase CA certificate is unavailable.');
        }

        $connection = DB::connection($connectionName);
        $currentDatabase = (string) $connection->scalar('SELECT current_database()');

        if (! hash_equals($expectedDatabase, $currentDatabase)) {
            throw new RuntimeException('Connected PostgreSQL database does not match the approved database.');
        }

        $ssl = $connection->selectOne(
            'SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()'
        );

        if (! $this->postgresBoolean($ssl?->ssl ?? false)) {
            throw new RuntimeException('The PostgreSQL connection is not protected by TLS.');
        }

        $currentSchema = $connection->scalar('SELECT current_schema()');

        if ($requireCurrentSchema && $currentSchema !== $schema) {
            throw new RuntimeException('The current PostgreSQL schema is not the approved schema.');
        }

        return [
            'database' => $currentDatabase,
            'schema' => is_string($currentSchema) ? $currentSchema : null,
            'search_path' => (string) $connection->scalar('SHOW search_path'),
            'ssl' => true,
        ];
    }

    private function assertFinalSchemaName(string $schema): void
    {
        if ($schema !== (string) config('phase9a.final_schema', 'civiclear')) {
            throw new RuntimeException('Final operations are restricted to the approved civiclear schema.');
        }
    }

    public function assertPrivateSchemaPrivileges(string $connectionName, string $schema): void
    {
        $connection = DB::connection($connectionName);
        $schemaAclCount = (int) $connection->scalar(
            "SELECT COUNT(*)
             FROM pg_namespace namespaces
             CROSS JOIN LATERAL aclexplode(
                 COALESCE(namespaces.nspacl, acldefault('n'::\"char\", namespaces.nspowner))
             ) privileges
             LEFT JOIN pg_roles roles ON roles.oid = privileges.grantee
             WHERE namespaces.nspname = ?
               AND (privileges.grantee = 0 OR roles.rolname IN (?, ?, ?))",
            [$schema, 'anon', 'authenticated', 'service_role'],
        );
        $objectGrantCount = (int) $connection->scalar(
            'SELECT
                (SELECT COUNT(*) FROM information_schema.table_privileges
                 WHERE table_schema = ? AND grantee IN (?, ?, ?, ?))
              + (SELECT COUNT(*) FROM information_schema.usage_privileges
                 WHERE object_schema = ? AND grantee IN (?, ?, ?, ?))
              + (SELECT COUNT(*) FROM information_schema.routine_privileges
                 WHERE routine_schema = ? AND grantee IN (?, ?, ?, ?))',
            [
                $schema, 'PUBLIC', 'anon', 'authenticated', 'service_role',
                $schema, 'PUBLIC', 'anon', 'authenticated', 'service_role',
                $schema, 'PUBLIC', 'anon', 'authenticated', 'service_role',
            ],
        );

        if ($schemaAclCount !== 0 || $objectGrantCount !== 0) {
            throw new RuntimeException('The final civiclear schema grants access to a public API role.');
        }
    }

    private function postgresBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }
}

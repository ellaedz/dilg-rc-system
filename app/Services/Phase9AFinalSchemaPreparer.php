<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Phase9AFinalSchemaPreparer
{
    private const ADVISORY_LOCK_KEY = 920260808;

    /**
     * @return array{schema_exists: bool, object_count: int, migration_count: int, owner: ?string, current_user: string, owner_matches_current_user: bool}
     */
    public function inspect(string $connectionName, string $schema): array
    {
        $this->assertIdentifier($schema);
        $connection = DB::connection($connectionName);
        $currentUser = (string) $connection->scalar('SELECT current_user');
        $schemaExists = $this->postgresBoolean($connection->scalar(
            'SELECT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = ?)',
            [$schema],
        ));

        if (! $schemaExists) {
            return [
                'schema_exists' => false,
                'object_count' => 0,
                'migration_count' => 0,
                'owner' => null,
                'current_user' => $currentUser,
                'owner_matches_current_user' => true,
            ];
        }

        $owner = $connection->scalar(
            'SELECT pg_get_userbyid(nspowner) FROM pg_namespace WHERE nspname = ?',
            [$schema],
        );

        return [
            'schema_exists' => true,
            'object_count' => $this->schemaObjectCount($connection, $schema),
            'migration_count' => $this->migrationCount($connection, $schema),
            'owner' => is_string($owner) ? $owner : null,
            'current_user' => $currentUser,
            'owner_matches_current_user' => is_string($owner) && $owner === $currentUser,
        ];
    }

    /**
     * Prepare an empty final schema. This method never drops or resets a schema.
     *
     * @return array{schema_created: bool, migration_count: int, expected_migration_count: int}
     */
    public function prepare(
        string $connectionName,
        string $schema,
        callable $runMigrations,
        Phase9APostgresSafetyGuard $guard,
    ): array {
        $this->assertIdentifier($schema);
        $connection = DB::connection($connectionName);
        $lockAcquired = $this->postgresBoolean($connection->scalar(
            'SELECT pg_try_advisory_lock(?)',
            [self::ADVISORY_LOCK_KEY],
        ));

        if (! $lockAcquired) {
            throw new RuntimeException('Another Phase 9A final-schema operation holds the safety lock.');
        }

        try {
            $inspection = $this->inspect($connectionName, $schema);

            if ($inspection['schema_exists'] && $inspection['object_count'] !== 0) {
                throw new RuntimeException(
                    'The final civiclear schema is not empty; automatic deletion or reset is forbidden.'
                );
            }

            if ($inspection['schema_exists'] && ! $inspection['owner_matches_current_user']) {
                throw new RuntimeException(
                    'The final civiclear schema is not owned by the approved migration role.'
                );
            }

            $schemaCreated = false;

            if (! $inspection['schema_exists']) {
                $connection->statement('CREATE SCHEMA '.$this->quoteIdentifier($schema));
                $schemaCreated = true;
            }

            $connection->statement('SET search_path TO '.$this->quoteIdentifier($schema));

            if ($connection->scalar('SELECT current_schema()') !== $schema) {
                throw new RuntimeException('PostgreSQL did not activate the approved final schema.');
            }

            $this->hardenPrivileges($connection, $schema);
            $guard->assertPrivateSchemaPrivileges($connectionName, $schema);

            $runMigrations();

            $this->hardenPrivileges($connection, $schema);
            $guard->assertPrivateSchemaPrivileges($connectionName, $schema);

            $migrationCount = $this->migrationCount($connection, $schema);
            $expectedMigrationCount = $this->expectedMigrationCount();

            if ($migrationCount !== $expectedMigrationCount) {
                throw new RuntimeException(
                    "Final schema migration count {$migrationCount} does not match {$expectedMigrationCount}."
                );
            }

            return [
                'schema_created' => $schemaCreated,
                'migration_count' => $migrationCount,
                'expected_migration_count' => $expectedMigrationCount,
            ];
        } finally {
            $connection->select('SELECT pg_advisory_unlock(?)', [self::ADVISORY_LOCK_KEY]);
        }
    }

    private function hardenPrivileges(ConnectionInterface $connection, string $schema): void
    {
        $quotedSchema = $this->quoteIdentifier($schema);
        $roles = ['PUBLIC'];
        $availableApiRoles = $connection->table('pg_roles')
            ->whereIn('rolname', ['anon', 'authenticated', 'service_role'])
            ->pluck('rolname')
            ->all();

        foreach ($availableApiRoles as $role) {
            if (is_string($role)) {
                $roles[] = $role;
            }
        }

        foreach ($roles as $role) {
            $quotedRole = $role === 'PUBLIC' ? 'PUBLIC' : $this->quoteIdentifier($role);
            $connection->unprepared(
                "REVOKE ALL PRIVILEGES ON SCHEMA {$quotedSchema} FROM {$quotedRole}"
            );
            $connection->unprepared(
                "REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA {$quotedSchema} FROM {$quotedRole}"
            );
            $connection->unprepared(
                "REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA {$quotedSchema} FROM {$quotedRole}"
            );
            $connection->unprepared(
                "REVOKE ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA {$quotedSchema} FROM {$quotedRole}"
            );
            $connection->unprepared(
                "ALTER DEFAULT PRIVILEGES IN SCHEMA {$quotedSchema} "
                ."REVOKE ALL PRIVILEGES ON TABLES FROM {$quotedRole}"
            );
            $connection->unprepared(
                "ALTER DEFAULT PRIVILEGES IN SCHEMA {$quotedSchema} "
                ."REVOKE ALL PRIVILEGES ON SEQUENCES FROM {$quotedRole}"
            );
            $connection->unprepared(
                "ALTER DEFAULT PRIVILEGES IN SCHEMA {$quotedSchema} "
                ."REVOKE ALL PRIVILEGES ON FUNCTIONS FROM {$quotedRole}"
            );
        }
    }

    private function schemaObjectCount(ConnectionInterface $connection, string $schema): int
    {
        return (int) $connection->scalar(
            'SELECT
                (SELECT COUNT(*) FROM pg_class classes
                 JOIN pg_namespace namespaces ON namespaces.oid = classes.relnamespace
                 WHERE namespaces.nspname = ?)
              + (SELECT COUNT(*) FROM pg_proc routines
                 JOIN pg_namespace namespaces ON namespaces.oid = routines.pronamespace
                 WHERE namespaces.nspname = ?)
              + (SELECT COUNT(*) FROM pg_type types
                 JOIN pg_namespace namespaces ON namespaces.oid = types.typnamespace
                 WHERE namespaces.nspname = ?)',
            [$schema, $schema, $schema],
        );
    }

    private function migrationCount(ConnectionInterface $connection, string $schema): int
    {
        $exists = $this->postgresBoolean($connection->scalar(
            'SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = ? AND table_name = ?
            )',
            [$schema, 'migrations'],
        ));

        if (! $exists) {
            return 0;
        }

        return (int) $connection
            ->table($schema.'.migrations')
            ->count();
    }

    private function expectedMigrationCount(): int
    {
        $files = glob(database_path('migrations/*.php'));

        if (! is_array($files)) {
            throw new RuntimeException('The migration files could not be enumerated.');
        }

        return count($files);
    }

    private function assertIdentifier(string $identifier): void
    {
        if (! preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $identifier)) {
            throw new RuntimeException('Unsafe PostgreSQL identifier.');
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        $this->assertIdentifier($identifier);

        return '"'.$identifier.'"';
    }

    private function postgresBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }
}

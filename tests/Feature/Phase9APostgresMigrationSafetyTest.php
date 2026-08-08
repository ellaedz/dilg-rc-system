<?php

namespace Tests\Feature;

use App\Services\Phase9APostgresSafetyGuard;
use App\Services\Phase9AReadOnlyRuntimeGuard;
use App\Services\Phase9ASqliteBackupService;
use App\Services\Phase9ASqliteSnapshotImporter;
use Illuminate\Filesystem\Filesystem;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class Phase9APostgresMigrationSafetyTest extends TestCase
{
    public function test_postgresql_connections_have_secure_phase_9a_defaults(): void
    {
        $runtime = config('database.connections.pgsql');
        $validation = config('database.connections.phase9a_pgsql');

        $this->assertSame('civiclear', $runtime['search_path']);
        $this->assertSame('verify-full', $runtime['sslmode']);
        $this->assertNull($validation['url']);
        $this->assertSame('verify-full', $validation['sslmode']);
        $defaultConnection = (string) config('database.default');

        if ($defaultConnection === 'sqlite') {
            $this->assertSame(':memory:', config('database.connections.sqlite.database'));

            return;
        }

        $this->assertSame('phase9a_pgsql', $defaultConnection);
        $this->assertMatchesRegularExpression(
            (string) config('phase9a.test_schema_pattern'),
            (string) config('database.connections.phase9a_pgsql.search_path'),
        );
    }

    public function test_disposable_schema_guard_accepts_only_phase_9a_test_names(): void
    {
        $guard = app(Phase9APostgresSafetyGuard::class);

        $guard->assertDisposableSchemaName('civiclear_phase9a_test_20260808');

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('unsafeSchemaProvider')]
    public function test_disposable_schema_guard_rejects_protected_or_malformed_names(
        string $schema,
    ): void {
        $this->expectException(RuntimeException::class);

        app(Phase9APostgresSafetyGuard::class)->assertDisposableSchemaName($schema);
    }

    public static function unsafeSchemaProvider(): array
    {
        return [
            'final schema' => ['civiclear'],
            'public' => ['public'],
            'auth' => ['auth'],
            'storage' => ['storage'],
            'missing suffix' => ['civiclear_phase9a_test_'],
            'SQL punctuation' => ['civiclear_phase9a_test_bad-name'],
        ];
    }

    public function test_validation_command_rejects_a_protected_schema_before_connecting(): void
    {
        $this->artisan('phase9a:validate-postgres', [
            '--schema' => 'public',
            '--expected-database' => 'postgres',
        ])
            ->expectsOutputToContain('stopped safely')
            ->assertFailed();
    }

    public function test_importer_refuses_to_read_from_the_live_sqlite_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('live SQLite database is forbidden');

        app(Phase9ASqliteSnapshotImporter::class)->import(
            sourcePath: database_path('database.sqlite'),
            manifestPath: __FILE__,
            expectedSourceHash: str_repeat('a', 64),
            connectionName: 'phase9a_pgsql',
            schema: 'civiclear_phase9a_test_guard',
        );
    }

    public function test_final_sqlite_backup_is_verified_and_does_not_change_its_source(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'civiclear-phase9a-backup-test-'.bin2hex(random_bytes(8));
        $sourcePath = $root.DIRECTORY_SEPARATOR.'source.sqlite';
        $destination = $root.DIRECTORY_SEPARATOR.'final-backup';
        $filesystem = new Filesystem;
        $filesystem->makeDirectory($root, 0700, true);

        try {
            $source = new PDO('sqlite:'.$sourcePath);
            $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $source->exec('PRAGMA foreign_keys = ON');
            $source->exec('CREATE TABLE parents (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
            $source->exec(
                'CREATE TABLE children (
                    id INTEGER PRIMARY KEY,
                    parent_id INTEGER NOT NULL REFERENCES parents(id)
                )'
            );
            $source->exec("INSERT INTO parents (id, name) VALUES (1, 'approved')");
            $source->exec('INSERT INTO children (id, parent_id) VALUES (1, 1)');
            $source = null;
            $sourceHashBefore = hash_file('sha256', $sourcePath);

            $result = app(Phase9ASqliteBackupService::class)->create(
                $sourcePath,
                $destination,
            );

            $this->assertSame($sourceHashBefore, hash_file('sha256', $sourcePath));
            $this->assertFileExists($result['backup_path']);
            $this->assertFileExists($result['manifest_path']);
            $this->assertSame(['children' => 1, 'parents' => 1], $result['table_counts']);
            $this->assertSame(
                hash_file('sha256', $result['backup_path']),
                $result['backup_sha256'],
            );

            $manifest = json_decode(
                (string) file_get_contents($result['manifest_path']),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $this->assertSame(realpath($result['backup_path']), realpath($manifest['backup']['path']));
            $this->assertSame($result['backup_sha256'], $manifest['backup']['sha256']);
            $this->assertSame(0, $manifest['verification']['foreign_key_violations']);
        } finally {
            if (is_dir($root)
                && str_starts_with(
                    strtolower(str_replace('\\', '/', $root)),
                    strtolower(str_replace('\\', '/', sys_get_temp_dir())).'/civiclear-phase9a-backup-test-',
                )) {
                $filesystem->deleteDirectory($root);
            }
        }
    }

    public function test_final_sqlite_backup_refuses_repository_and_existing_destinations(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'civiclear-phase9a-backup-test-'.bin2hex(random_bytes(8));
        $sourcePath = $root.DIRECTORY_SEPARATOR.'source.sqlite';
        $existingDestination = $root.DIRECTORY_SEPARATOR.'already-exists';
        $filesystem = new Filesystem;
        $filesystem->makeDirectory($existingDestination, 0700, true);
        $source = new PDO('sqlite:'.$sourcePath);
        $source->exec('CREATE TABLE examples (id INTEGER PRIMARY KEY)');
        $source = null;
        $service = app(Phase9ASqliteBackupService::class);

        try {
            try {
                $service->create($sourcePath, base_path('forbidden-phase9a-backup'));
                $this->fail('A repository destination was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('outside the repository', $exception->getMessage());
            }

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('must not already exist');
            $service->create($sourcePath, $existingDestination);
        } finally {
            if (is_dir($root)
                && str_starts_with(
                    strtolower(str_replace('\\', '/', $root)),
                    strtolower(str_replace('\\', '/', sys_get_temp_dir())).'/civiclear-phase9a-backup-test-',
                )) {
                $filesystem->deleteDirectory($root);
            }
        }
    }

    public function test_final_schema_dry_run_rejects_nonfinal_schema_before_connecting(): void
    {
        $this->artisan('phase9a:prepare-final-postgres', [
            '--schema' => 'public',
            '--expected-database' => 'postgres',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('stopped safely')
            ->assertFailed();
    }

    public function test_final_verification_requires_the_separate_maintenance_approval_gate(): void
    {
        config(['phase9a.final_import_confirmation' => null]);
        $expectedSafetyMessage = app()->isDownForMaintenance()
            ? 'explicit final-import approval gate'
            : 'maintenance mode';

        $this->artisan('phase9a:verify-import', [
            '--schema' => 'civiclear',
            '--expected-database' => 'postgres',
            '--mode' => 'final',
        ])
            ->expectsOutputToContain($expectedSafetyMessage)
            ->assertFailed();
    }

    public function test_final_import_requires_an_explicit_operational_table_policy(): void
    {
        config(['phase9a.operational_table_policy' => null]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('explicit preserve policy');

        app(Phase9APostgresSafetyGuard::class)->assertOperationalTablePolicy();
    }

    public function test_gate_2_runtime_guard_allows_only_simple_read_statements(): void
    {
        $guard = app(Phase9AReadOnlyRuntimeGuard::class);

        $guard->assertReadOnlySql('SELECT * FROM violation_reports');
        $guard->assertReadOnlySql('SHOW default_transaction_read_only');
        $guard->assertReadOnlySql("SELECT COUNT(*) FROM users;\n");

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('unsafeReadOnlySqlProvider')]
    public function test_gate_2_runtime_guard_rejects_non_read_sql(string $query): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database write was blocked');

        app(Phase9AReadOnlyRuntimeGuard::class)->assertReadOnlySql($query);
    }

    public static function unsafeReadOnlySqlProvider(): array
    {
        return [
            'insert' => ['INSERT INTO users (name) VALUES (\'blocked\')'],
            'update' => ['UPDATE users SET name = \'blocked\''],
            'delete' => ['DELETE FROM users'],
            'data-changing CTE' => [
                'WITH changed AS (DELETE FROM users RETURNING id) SELECT * FROM changed',
            ],
            'session override' => ['SET default_transaction_read_only = off'],
            'multiple statements' => ['SELECT 1; DELETE FROM users'],
        ];
    }
}

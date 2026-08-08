<?php

namespace App\Console\Commands;

use App\Services\Phase9APostgresSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ValidatePhase9APostgres extends Command
{
    protected $signature = 'phase9a:validate-postgres
        {--schema= : Disposable schema matching civiclear_phase9a_test_*}
        {--expected-database= : Approved Supabase database name}';

    protected $description = 'Create and migrate only a hard-guarded disposable Phase 9A PostgreSQL schema';

    public function handle(Phase9APostgresSafetyGuard $guard): int
    {
        $connectionName = (string) config('phase9a.connection', 'phase9a_pgsql');
        $schema = trim((string) ($this->option('schema') ?: env('PHASE9A_DB_SCHEMA')));
        $expectedDatabase = trim((string) (
            $this->option('expected-database') ?: env('PHASE9A_EXPECTED_DATABASE')
        ));

        try {
            $guard->assertDisposableSchemaName($schema);

            config([
                "database.connections.{$connectionName}.search_path" => $schema,
                'database.default' => $connectionName,
            ]);
            DB::purge($connectionName);

            $before = $guard->assertDisposableReadinessConnection(
                connectionName: $connectionName,
                schema: $schema,
                expectedDatabase: $expectedDatabase,
            );

            $quotedSchema = '"'.str_replace('"', '""', $schema).'"';
            DB::connection($connectionName)->statement("CREATE SCHEMA IF NOT EXISTS {$quotedSchema}");
            DB::purge($connectionName);

            $afterCreation = $guard->assertDisposableConnection(
                connectionName: $connectionName,
                schema: $schema,
                expectedDatabase: $expectedDatabase,
            );

            if ($afterCreation['schema'] !== $schema) {
                throw new RuntimeException('The disposable schema was not selected as current_schema().');
            }

            $exitCode = Artisan::call('migrate:fresh', [
                '--database' => $connectionName,
                '--force' => true,
                '--no-interaction' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                throw new RuntimeException('PostgreSQL migrations failed in the disposable schema.');
            }

            $migrationFileCount = count(File::files(database_path('migrations')));
            $migrationRowCount = (int) DB::connection($connectionName)
                ->table('migrations')
                ->count();

            if ($migrationRowCount !== $migrationFileCount) {
                throw new RuntimeException('PostgreSQL migration count does not match migration files.');
            }

            $this->info('Disposable PostgreSQL migration validation passed.');
            $this->line('Database matched approved value: yes');
            $this->line('TLS active: '.($before['ssl'] ? 'yes' : 'no'));
            $this->line('Current schema: '.$schema);
            $this->line('Migration count: '.$migrationRowCount);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A validation stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($connectionName);
        }
    }
}

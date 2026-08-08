<?php

namespace App\Console\Commands;

use App\Services\Phase9AFinalSchemaPreparer;
use App\Services\Phase9APostgresSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class PrepareFinalPhase9APostgres extends Command
{
    protected $signature = 'phase9a:prepare-final-postgres
        {--schema= : Exact final PostgreSQL schema}
        {--expected-database= : Approved Supabase database name}
        {--dry-run : Inspect readiness without creating a schema or running migrations}';

    protected $description = 'Safely inspect or prepare the empty final Phase 9A PostgreSQL schema';

    public function handle(
        Phase9APostgresSafetyGuard $guard,
        Phase9AFinalSchemaPreparer $preparer,
    ): int {
        $connectionName = (string) config('phase9a.connection', 'phase9a_pgsql');
        $schema = trim((string) ($this->option('schema') ?: env('PHASE9A_DB_SCHEMA')));
        $expectedDatabase = trim((string) (
            $this->option('expected-database') ?: env('PHASE9A_EXPECTED_DATABASE')
        ));

        try {
            config([
                "database.connections.{$connectionName}.search_path" => $schema,
                'database.default' => $connectionName,
            ]);
            DB::purge($connectionName);

            if ((bool) $this->option('dry-run')) {
                $connectionState = $guard->assertFinalReadinessConnection(
                    connectionName: $connectionName,
                    schema: $schema,
                    expectedDatabase: $expectedDatabase,
                );
                $inspection = $preparer->inspect($connectionName, $schema);

                if ($inspection['schema_exists'] && $inspection['object_count'] !== 0) {
                    throw new RuntimeException(
                        'The final civiclear schema already contains objects; preparation would stop.'
                    );
                }

                if ($inspection['schema_exists'] && ! $inspection['owner_matches_current_user']) {
                    throw new RuntimeException(
                        'The final civiclear schema is not owned by the approved migration role.'
                    );
                }

                if ($inspection['schema_exists']) {
                    $guard->assertPrivateSchemaPrivileges($connectionName, $schema);
                }

                $this->info('Phase 9A final PostgreSQL preparation dry run passed.');
                $this->line('Database matched approved value: yes');
                $this->line('TLS active: '.($connectionState['ssl'] ? 'yes' : 'no'));
                $this->line('Final schema exists: '.($inspection['schema_exists'] ? 'yes' : 'no'));
                $this->line('Final schema object count: '.$inspection['object_count']);
                $this->line('No PostgreSQL objects were created or changed.');

                return self::SUCCESS;
            }

            $guard->assertFinalPreparationConnection(
                connectionName: $connectionName,
                schema: $schema,
                expectedDatabase: $expectedDatabase,
            );
            $result = $preparer->prepare(
                connectionName: $connectionName,
                schema: $schema,
                runMigrations: function () use ($connectionName): void {
                    $exitCode = Artisan::call('migrate', [
                        '--database' => $connectionName,
                        '--force' => true,
                        '--no-interaction' => true,
                    ]);

                    if ($exitCode !== self::SUCCESS) {
                        throw new RuntimeException('Laravel migrations did not complete successfully.');
                    }
                },
                guard: $guard,
            );

            $this->info('Phase 9A final PostgreSQL schema preparation passed all guards.');
            $this->line('Schema created: '.($result['schema_created'] ? 'yes' : 'no'));
            $this->line('Migration count: '.$result['migration_count']);
            $this->line('No SQLite rows were imported.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A final preparation stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($connectionName);
        }
    }
}

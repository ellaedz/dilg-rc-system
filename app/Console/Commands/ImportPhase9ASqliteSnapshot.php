<?php

namespace App\Console\Commands;

use App\Services\Phase9APostgresSafetyGuard;
use App\Services\Phase9ASqliteSnapshotImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportPhase9ASqliteSnapshot extends Command
{
    protected $signature = 'phase9a:import-sqlite-snapshot
        {--source= : Absolute path to the immutable SQLite backup}
        {--manifest= : Absolute path to the matching backup manifest}
        {--source-sha256= : Approved SHA-256 of the immutable backup}
        {--schema= : Target PostgreSQL schema}
        {--expected-database= : Approved Supabase database name}
        {--mode=test : test or final}';

    protected $description = 'Import a verified immutable SQLite snapshot into a guarded Phase 9A PostgreSQL schema';

    public function handle(
        Phase9APostgresSafetyGuard $guard,
        Phase9ASqliteSnapshotImporter $importer,
    ): int {
        $connectionName = (string) config('phase9a.connection', 'phase9a_pgsql');
        $source = trim((string) $this->option('source'));
        $manifest = trim((string) $this->option('manifest'));
        $sourceHash = trim((string) $this->option('source-sha256'));
        $schema = trim((string) ($this->option('schema') ?: env('PHASE9A_DB_SCHEMA')));
        $expectedDatabase = trim((string) (
            $this->option('expected-database') ?: env('PHASE9A_EXPECTED_DATABASE')
        ));
        $mode = trim(strtolower((string) $this->option('mode')));

        try {
            if (! in_array($mode, ['test', 'final'], true)) {
                throw new \RuntimeException('Import mode must be test or final.');
            }

            config([
                "database.connections.{$connectionName}.search_path" => $schema,
                'database.default' => $connectionName,
            ]);
            DB::purge($connectionName);

            if ($mode === 'final') {
                $guard->assertFinalImportConnection(
                    connectionName: $connectionName,
                    schema: $schema,
                    expectedDatabase: $expectedDatabase,
                );
            } else {
                $guard->assertDisposableConnection(
                    connectionName: $connectionName,
                    schema: $schema,
                    expectedDatabase: $expectedDatabase,
                );
            }

            $result = $importer->import(
                sourcePath: $source,
                manifestPath: $manifest,
                expectedSourceHash: $sourceHash,
                connectionName: $connectionName,
                schema: $schema,
            );

            $this->info('Phase 9A SQLite snapshot import passed all guarded checks.');
            $this->line('Imported rows: '.$result['imported_rows']);
            $this->line('Imported tables: '.count($result['table_counts']));
            $this->line('Verified source SHA-256: '.$result['source_sha256']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A import stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($connectionName);
        }
    }
}

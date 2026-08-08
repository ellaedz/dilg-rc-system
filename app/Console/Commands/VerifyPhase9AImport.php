<?php

namespace App\Console\Commands;

use App\Services\Phase9AImportVerifier;
use App\Services\Phase9APostgresSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerifyPhase9AImport extends Command
{
    protected $signature = 'phase9a:verify-import
        {--source= : Absolute path to the immutable SQLite backup}
        {--manifest= : Absolute path to its manifest}
        {--source-sha256= : Approved snapshot SHA-256}
        {--schema= : Target PostgreSQL schema}
        {--expected-database= : Approved Supabase database name}
        {--mode=test : test or final}';

    protected $description = 'Read-only canonical parity verification for a guarded Phase 9A import';

    public function handle(
        Phase9APostgresSafetyGuard $guard,
        Phase9AImportVerifier $verifier,
    ): int {
        $connectionName = (string) config('phase9a.connection', 'phase9a_pgsql');
        $schema = trim((string) ($this->option('schema') ?: env('PHASE9A_DB_SCHEMA')));
        $expectedDatabase = trim((string) (
            $this->option('expected-database') ?: env('PHASE9A_EXPECTED_DATABASE')
        ));
        $mode = trim(strtolower((string) $this->option('mode')));

        try {
            if (! in_array($mode, ['test', 'final'], true)) {
                throw new \RuntimeException('Verification mode must be test or final.');
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

            $result = $verifier->verify(
                sourcePath: trim((string) $this->option('source')),
                manifestPath: trim((string) $this->option('manifest')),
                expectedSourceHash: trim((string) $this->option('source-sha256')),
                connectionName: $connectionName,
                schema: $schema,
            );

            $this->info('Phase 9A canonical import parity passed.');
            $this->line('Tables matched: '.count($result['table_counts']));
            $this->line('Canonical digests matched: '.count($result['table_digests']));
            $this->line('PostgreSQL indexes found: '.$result['index_count']);
            $this->line('PostgreSQL foreign keys found: '.$result['foreign_key_count']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A parity stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($connectionName);
        }
    }
}

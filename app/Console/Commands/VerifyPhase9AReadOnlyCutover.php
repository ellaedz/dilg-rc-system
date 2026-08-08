<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\MobileReportApiController;
use App\Models\ViolationReport;
use App\Services\Phase9AImportVerifier;
use App\Services\Phase9APostgresSafetyGuard;
use App\Services\ReportCredentialService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class VerifyPhase9AReadOnlyCutover extends Command
{
    protected $signature = 'phase9a:verify-readonly-cutover
        {--source= : Absolute path to the approved immutable SQLite backup}
        {--manifest= : Absolute path to the approved backup manifest}
        {--source-sha256= : Approved backup SHA-256}';

    protected $description = 'Verify the PostgreSQL runtime cutover while all database writes remain blocked';

    public function handle(
        Phase9AImportVerifier $verifier,
        Phase9APostgresSafetyGuard $postgresSafetyGuard,
        ReportCredentialService $credentialService,
        MobileReportApiController $mobileReportController,
    ): int {
        $connectionName = (string) config('database.default');
        $schema = (string) config('phase9a.final_schema', 'civiclear');

        try {
            if (! app()->isDownForMaintenance()) {
                throw new RuntimeException('Read-only cutover verification requires maintenance mode.');
            }

            if (! (bool) config('phase9a.runtime_read_only')) {
                throw new RuntimeException('The Phase 9A runtime read-only guard is not enabled.');
            }

            if ($connectionName !== 'pgsql') {
                throw new RuntimeException('Laravel has not been cut over to the pgsql runtime connection.');
            }

            foreach (['session.driver', 'cache.default', 'queue.default'] as $configurationKey) {
                if (config($configurationKey) === 'database') {
                    throw new RuntimeException(
                        "{$configurationKey} must not use the database during read-only acceptance."
                    );
                }
            }

            $connection = DB::connection($connectionName);
            $readOnlyState = $connection->scalar('SHOW default_transaction_read_only');

            if ($readOnlyState !== 'on') {
                throw new RuntimeException('The PostgreSQL runtime session is not read-only.');
            }

            $postgresSafetyGuard->assertPrivateSchemaPrivileges($connectionName, $schema);
            $result = $verifier->verify(
                sourcePath: trim((string) $this->option('source')),
                manifestPath: trim((string) $this->option('manifest')),
                expectedSourceHash: trim((string) $this->option('source-sha256')),
                connectionName: $connectionName,
                schema: $schema,
            );

            $roleCounts = $connection->table('users')
                ->selectRaw('role, COUNT(*) AS aggregate')
                ->groupBy('role')
                ->orderBy('role')
                ->pluck('aggregate', 'role');

            if ($roleCounts->isEmpty()) {
                throw new RuntimeException('No staff roles were available for acceptance checks.');
            }

            $trackingReport = ViolationReport::query()
                ->whereNotNull('token_derivation_nonce')
                ->whereNotNull('tracking_token_hash')
                ->orderBy('id')
                ->get()
                ->first(function (ViolationReport $report) use ($credentialService): bool {
                    try {
                        $token = $credentialService->replayToken($report);
                        $derivedHash = $credentialService->hashTrackingToken($token);
                    } catch (RuntimeException) {
                        return false;
                    }

                    return is_string($report->tracking_token_hash)
                        && hash_equals($report->tracking_token_hash, $derivedHash);
                });

            if (! $trackingReport) {
                throw new RuntimeException(
                    'No imported tracking hash matches the current server-side tracking keys.'
                );
            }

            $rawTrackingToken = $credentialService->replayToken($trackingReport);
            $trackingResponse = $mobileReportController->status(
                Request::create('/', 'GET'),
                $rawTrackingToken,
                $credentialService,
            );
            $rawTrackingToken = null;
            $trackingPayload = $trackingResponse->getData(true);

            if ($trackingResponse->getStatusCode() !== 200
                || ($trackingPayload['success'] ?? false) !== true
                || ($trackingPayload['data']['report_number'] ?? null)
                    !== $trackingReport->report_number) {
                throw new RuntimeException('Public tracking did not resolve the approved report safely.');
            }

            $this->info('Phase 9A read-only PostgreSQL cutover verification passed.');
            $this->line('Runtime driver: pgsql');
            $this->line('Runtime schema: '.$schema);
            $this->line('Database writes blocked: yes');
            $this->line('Tables matched: '.count($result['table_counts']));
            $this->line('Canonical digests matched: '.count($result['table_digests']));
            $this->line('Staff role groups found: '.$roleCounts->count());
            $this->line('Opaque public tracking lookup: passed');
            $this->line('PostgreSQL indexes found: '.$result['index_count']);
            $this->line('PostgreSQL foreign keys found: '.$result['foreign_key_count']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9A read-only cutover stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

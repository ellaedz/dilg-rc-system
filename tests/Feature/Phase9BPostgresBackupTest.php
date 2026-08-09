<?php

namespace Tests\Feature;

use App\Models\ViolationReport;
use App\Services\Phase9BPostgresBackupService;
use App\Services\Phase9BStage3Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class Phase9BPostgresBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_uses_argument_array_password_environment_and_verified_manifest(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'civiclear-phase9b-backup-test-'
            .bin2hex(random_bytes(8));
        File::makeDirectory($root);
        $destination = $root.DIRECTORY_SEPARATOR.'new-backup';
        $pgDump = $root.DIRECTORY_SEPARATOR.'pg_dump.exe';
        $pgRestore = $root.DIRECTORY_SEPARATOR.'pg_restore.exe';
        File::put($pgDump, 'test');
        File::put($pgRestore, 'test');
        $this->report();
        config()->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => 'approved.example.test',
            'port' => '5432',
            'database' => 'postgres',
            'username' => 'migration-role',
            'password' => 'not-in-command-or-manifest',
            'sslrootcert' => $root.DIRECTORY_SEPARATOR.'ca.crt',
        ]);
        $guard = Mockery::mock(Phase9BStage3Guard::class);
        $guard->shouldReceive('assertReady')->once()->with(false)
            ->andReturn($this->app->make('db')->connection());
        $service = new Phase9BPostgresBackupService($guard);

        Process::fake(function (PendingProcess $pending) use ($pgDump) {
            if (($pending->command[0] ?? null) === realpath($pgDump)) {
                $fileArgument = collect($pending->command)
                    ->first(fn (string $argument): bool => str_starts_with($argument, '--file='));
                File::put(substr((string) $fileArgument, 7), 'PGDMPtest-backup');

                return Process::result();
            }

            return Process::result(output: 'SCHEMA - civiclear');
        });

        try {
            $result = $service->create($destination, $pgDump, $pgRestore);
            $manifest = json_decode((string) File::get($result['manifest_path']), true);

            $this->assertFileExists($result['backup_path']);
            $this->assertSame(
                hash_file('sha256', $result['backup_path']),
                $result['backup_sha256'],
            );
            $this->assertSame('civiclear-phase9b-postgres-backup', $manifest['kind']);
            $this->assertSame(1, $manifest['inventory']['uploaded_photos']);
            $this->assertStringNotContainsString(
                'not-in-command-or-manifest',
                (string) File::get($result['manifest_path']),
            );
            Process::assertRan(function (PendingProcess $pending): bool {
                return ($pending->environment['PGPASSWORD'] ?? null)
                        === 'not-in-command-or-manifest'
                    && ! str_contains(
                        implode(' ', $pending->command),
                        'not-in-command-or-manifest',
                    );
            });
        } finally {
            File::deleteDirectory($root);
        }
    }

    private function report(): void
    {
        $bytes = 'backup-photo';
        $token = str_repeat('D', 43);
        ViolationReport::create([
            'report_id' => 'RCV-2026-9001',
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Phase 9B backup test.',
            'selected_violation_type' => 'Unclassified',
            'status' => 'Submitted',
            'report_status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => '2026-08-09',
            'photo_object_key' => 'reports/DD/'.$token.'.jpg',
            'photo_storage_disk' => 'report_photos',
            'photo_size_bytes' => strlen($bytes),
            'photo_sha256' => hash('sha256', $bytes),
            'photo_upload_status' => 'uploaded',
        ]);
    }
}

<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Models\ViolationReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Phase9BPhotoMigrationService
{
    public function __construct(
        private readonly LocalPrivateReportPhotoStorage $local,
        private readonly SupabasePrivateReportPhotoStorage $supabase,
    ) {}

    /**
     * @return array{uploaded_photos: int, local_references: int, supabase_references: int, remote_preexisting_matching: int, local_files: int, local_orphans: int}
     */
    public function inspect(): array
    {
        if (ViolationReport::query()->whereNotNull('photo_pending_object_key')->exists()
            || ViolationReport::query()->where(
                'photo_upload_status',
                ViolationReport::PHOTO_STATUS_PROCESSING,
            )->exists()) {
            throw new RuntimeException('Unresolved photograph processing state blocks Stage 3.');
        }

        $reports = ViolationReport::query()
            ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_UPLOADED)
            ->orderBy('id')
            ->get();
        $localReferences = 0;
        $supabaseReferences = 0;
        $remotePreexisting = 0;
        $referencedKeys = [];

        foreach ($reports as $report) {
            $this->assertMetadata($report);
            $objectKey = (string) $report->photo_object_key;
            $referencedKeys[$objectKey] = true;

            if ($report->photo_storage_disk === $this->local->diskName()) {
                $localReferences++;
                $this->verifyObject($this->local, $report);
                if ($this->supabase->exists($objectKey)) {
                    $this->verifyObject($this->supabase, $report);
                    $remotePreexisting++;
                }
            } elseif ($report->photo_storage_disk === $this->supabase->diskName()) {
                $supabaseReferences++;
                $this->verifyObject($this->supabase, $report);
                // Local rollback evidence must remain complete through Stage 3.
                $this->verifyObject($this->local, $report);
            } else {
                throw new RuntimeException('An uploaded photograph has an unknown storage disk.');
            }
        }

        $localFiles = Storage::disk($this->local->diskName())->allFiles('reports');
        $orphans = array_values(array_filter(
            $localFiles,
            static fn (string $key): bool => ! isset($referencedKeys[$key])
        ));

        return [
            'uploaded_photos' => $reports->count(),
            'local_references' => $localReferences,
            'supabase_references' => $supabaseReferences,
            'remote_preexisting_matching' => $remotePreexisting,
            'local_files' => count($localFiles),
            'local_orphans' => count($orphans),
        ];
    }

    /**
     * @return array{migrated: int, reused_verified_remote: int, uploaded_photos: int, local_references: int, supabase_references: int, local_files: int, local_orphans: int}
     */
    public function migrate(): array
    {
        $before = $this->inspect();
        $reportIds = ViolationReport::query()
            ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_UPLOADED)
            ->where('photo_storage_disk', $this->local->diskName())
            ->orderBy('id')
            ->pluck('id');
        $migrated = 0;
        $reused = 0;

        foreach ($reportIds as $reportId) {
            $report = ViolationReport::query()->findOrFail($reportId);
            $bytes = $this->verifyObject($this->local, $report, returnBytes: true);
            $objectKey = (string) $report->photo_object_key;
            if ($this->supabase->exists($objectKey)) {
                $this->verifyObject($this->supabase, $report);
                $reused++;
            } else {
                $this->supabase->put($objectKey, $bytes);
                $this->verifyObject($this->supabase, $report);
            }

            DB::transaction(function () use ($report): void {
                $locked = ViolationReport::query()->lockForUpdate()->findOrFail($report->id);
                if ($locked->photo_storage_disk === $this->supabase->diskName()) {
                    return;
                }

                if ($locked->photo_upload_status !== ViolationReport::PHOTO_STATUS_UPLOADED
                    || $locked->photo_storage_disk !== $this->local->diskName()
                    || $locked->photo_object_key !== $report->photo_object_key
                    || $locked->photo_size_bytes !== $report->photo_size_bytes
                    || ! is_string($locked->photo_sha256)
                    || ! is_string($report->photo_sha256)
                    || ! hash_equals($locked->photo_sha256, $report->photo_sha256)) {
                    throw new RuntimeException(
                        'A photograph reference changed before its storage switch.'
                    );
                }

                $locked->forceFill([
                    'photo_storage_disk' => $this->supabase->diskName(),
                ])->saveQuietly();
            }, 3);
            $migrated++;
        }

        $after = $this->inspect();
        if ($after['local_references'] !== 0
            || $after['supabase_references'] !== $after['uploaded_photos']
            || $after['local_files'] !== $before['local_files']
            || $after['local_orphans'] !== $before['local_orphans']) {
            throw new RuntimeException('Phase 9B post-migration reconciliation failed.');
        }

        return [
            'migrated' => $migrated,
            'reused_verified_remote' => $reused,
            'uploaded_photos' => $after['uploaded_photos'],
            'local_references' => $after['local_references'],
            'supabase_references' => $after['supabase_references'],
            'local_files' => $after['local_files'],
            'local_orphans' => $after['local_orphans'],
        ];
    }

    private function assertMetadata(ViolationReport $report): void
    {
        if (! is_string($report->photo_object_key)
            || $report->photo_object_key === ''
            || ! preg_match('/\A[0-9a-f]{64}\z/D', (string) $report->photo_sha256)
            || ! is_int($report->photo_size_bytes)
            || $report->photo_size_bytes < 1
            || $report->photo_size_bytes > (int) config('report_photos.max_bytes')) {
            throw new RuntimeException('An uploaded photograph has incomplete integrity metadata.');
        }
    }

    private function verifyObject(
        PrivateReportPhotoStorage $storage,
        ViolationReport $report,
        bool $returnBytes = false,
    ): string {
        $objectKey = (string) $report->photo_object_key;
        if (! $storage->exists($objectKey)) {
            throw new RuntimeException('A referenced private photograph is missing.');
        }

        $stream = $storage->readStream($objectKey);
        $hash = hash_init('sha256');
        $size = 0;
        $bytes = '';
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    throw new RuntimeException('A private photograph could not be read completely.');
                }
                $size += strlen($chunk);
                if ($size > (int) config('report_photos.max_bytes')) {
                    throw new RuntimeException('A private photograph exceeds the approved size limit.');
                }
                hash_update($hash, $chunk);
                if ($returnBytes) {
                    $bytes .= $chunk;
                }
            }
        } finally {
            fclose($stream);
        }

        $actualHash = hash_final($hash);
        if ($size !== $report->photo_size_bytes
            || ! hash_equals((string) $report->photo_sha256, $actualHash)) {
            throw new RuntimeException('A private photograph failed byte or SHA-256 verification.');
        }

        return $bytes;
    }
}

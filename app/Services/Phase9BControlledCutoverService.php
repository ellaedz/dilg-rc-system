<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ReportAiDispatcher;
use App\Http\Controllers\Api\MobileReportApiController;
use App\Http\Controllers\ReportPhotoController;
use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Phase9BControlledCutoverService
{
    private const DESCRIPTION = '[PHASE 9B STAGE 4 TEST DATA] Controlled Supabase cutover validation.';

    public function __construct(
        private readonly MobileReportApiController $mobileController,
        private readonly ReportPhotoController $photoController,
        private readonly ReportCredentialService $credentialService,
        private readonly ReportNumberService $reportNumberService,
        private readonly ReportPhotoPipeline $photoPipeline,
        private readonly ReportSubmissionFingerprint $submissionFingerprint,
        private readonly ReportAiDispatcher $aiDispatcher,
        private readonly ReportPhotoStorageResolver $storageResolver,
        private readonly LocalPrivateReportPhotoStorage $local,
        private readonly SupabasePrivateReportPhotoStorage $supabase,
        private readonly Phase9BPhotoMigrationService $migration,
    ) {}

    /** @return array<string, bool|int|string> */
    public function run(): array
    {
        $matches = ViolationReport::query()->where('description', self::DESCRIPTION)->get();
        if ($matches->count() > 1) {
            throw new RuntimeException('Multiple controlled Stage 4 marker rows exist.');
        }

        $created = false;
        $rawTrackingToken = null;
        if ($matches->isNotEmpty()) {
            $report = $matches->first();
            if (! $report?->is_test_data) {
                throw new RuntimeException('An unmarked report already uses the Stage 4 marker.');
            }
            $rawTrackingToken = $this->credentialService->replayToken($report);
        } else {
            [$report, $rawTrackingToken] = $this->submitControlledReport();
            $created = true;
        }

        try {
            $report->refresh();
            $this->assertReportState($report);
            $remoteBytes = $this->verifiedBytes($this->supabase, $report);
            $this->ensureLocalRollbackCopy($report, $remoteBytes);
            $staffBytes = $this->authorizedStaffStream($report);
            if (! hash_equals(hash('sha256', $remoteBytes), hash('sha256', $staffBytes))) {
                throw new RuntimeException('Authorized staff streaming changed the controlled evidence.');
            }

            $this->assertUnauthorizedStaffDenied($report);
            $this->assertSignedRedirect($report);
            $this->assertPublicTrackingSafe($report, $rawTrackingToken);
            $inventory = $this->migration->inspect();
            if ($inventory['local_references'] !== 0
                || $inventory['supabase_references'] !== $inventory['uploaded_photos']) {
                throw new RuntimeException('Controlled cutover reconciliation failed.');
            }

            return [
                'report_number' => (string) $report->report_number,
                'new_test_row_created' => $created,
                'is_test_data' => true,
                'photo_uploaded' => true,
                'photo_storage_disk' => 'supabase_report_photos',
                'remote_integrity_verified' => true,
                'local_rollback_copy_verified' => true,
                'authorized_staff_stream_verified' => true,
                'unauthorized_staff_denied' => true,
                'signed_redirect_private' => true,
                'public_tracking_storage_safe' => true,
                'ai_processing_status' => (string) $report->ai_processing_status,
                'uploaded_photos' => $inventory['uploaded_photos'],
                'supabase_references' => $inventory['supabase_references'],
                'local_files' => $inventory['local_files'],
                'local_orphans' => $inventory['local_orphans'],
            ];
        } finally {
            $rawTrackingToken = null;
        }
    }

    /** @return array{ViolationReport, string} */
    private function submitControlledReport(): array
    {
        $bytes = $this->generatedPng();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'civiclear-phase9b-stage4-');
        if (! is_string($temporaryPath)) {
            throw new RuntimeException('The controlled Stage 4 image could not be prepared.');
        }

        try {
            if (file_put_contents($temporaryPath, $bytes, LOCK_EX) !== strlen($bytes)) {
                throw new RuntimeException('The controlled Stage 4 image could not be prepared.');
            }
            $upload = new UploadedFile(
                $temporaryPath,
                'controlled-evidence.png',
                'image/png',
                null,
                true,
            );
            $request = Request::create(
                '/api/mobile/reports',
                'POST',
                [
                    'description' => self::DESCRIPTION,
                    'latitude' => '14.281',
                    'longitude' => '121.416',
                    'gps_accuracy' => '8.5',
                    'timestamp' => now()->toIso8601String(),
                ],
                [],
                ['photo' => $upload],
            );
            $request->headers->set('Accept', 'application/json');
            $request->headers->set(
                'Idempotency-Key',
                'phase9b-stage4-'.bin2hex(random_bytes(24)),
            );
            $response = $this->mobileController->store(
                $request,
                $this->aiDispatcher,
                $this->credentialService,
                $this->reportNumberService,
                $this->photoPipeline,
                $this->submissionFingerprint,
            );
            $payload = $response->getData(true);
            $reportNumber = data_get($payload, 'data.report_number');
            $rawTrackingToken = data_get($payload, 'data.tracking_token');
            if ($response->getStatusCode() !== 201
                || ! is_string($reportNumber)
                || ! is_string($rawTrackingToken)) {
                throw new RuntimeException('The controlled report submission did not complete safely.');
            }

            $report = ViolationReport::query()->where('report_number', $reportNumber)->firstOrFail();
            DB::transaction(function () use ($report): void {
                $locked = ViolationReport::query()->lockForUpdate()->findOrFail($report->id);
                if ($locked->description !== self::DESCRIPTION
                    || $locked->photo_storage_disk !== $this->supabase->diskName()
                    || $locked->photo_upload_status !== ViolationReport::PHOTO_STATUS_UPLOADED) {
                    throw new RuntimeException('The controlled report cannot be marked as test data.');
                }
                $locked->forceFill(['is_test_data' => true])->saveQuietly();
            }, 3);

            return [$report->fresh(), $rawTrackingToken];
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function assertReportState(ViolationReport $report): void
    {
        if (! $report->is_test_data
            || $report->description !== self::DESCRIPTION
            || $report->photo_upload_status !== ViolationReport::PHOTO_STATUS_UPLOADED
            || $report->photo_storage_disk !== $this->supabase->diskName()
            || ! is_string($report->photo_object_key)
            || ! is_string($report->photo_sha256)
            || ! is_int($report->photo_size_bytes)
            || ! in_array($report->ai_processing_status, [
                ViolationReport::AI_STATUS_COMPLETED,
                ViolationReport::AI_STATUS_FAILED,
            ], true)
            || str_starts_with((string) $report->processing_error_code, 'AI_PHOTO_')) {
            throw new RuntimeException('The controlled Stage 4 report state is invalid.');
        }
    }

    private function ensureLocalRollbackCopy(ViolationReport $report, string $bytes): void
    {
        $objectKey = (string) $report->photo_object_key;
        if ($this->local->exists($objectKey)) {
            $this->verifiedBytes($this->local, $report);

            return;
        }

        $this->local->put($objectKey, $bytes);
        $this->verifiedBytes($this->local, $report);
    }

    private function authorizedStaffStream(ViolationReport $report): string
    {
        $admin = User::query()->where('role', 'dilg_admin')->first();
        if (! $admin) {
            throw new RuntimeException('An existing DILG administrator is required for Stage 4.');
        }

        $request = Request::create('/', 'GET');
        $request->setUserResolver(static fn () => $admin);
        $response = $this->photoController->show($request, $report, $this->storageResolver);
        ob_start();
        try {
            $response->sendContent();
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if (! is_string($bytes)
            || $response->getStatusCode() !== 200
            || ! str_contains(strtolower((string) $response->headers->get('Cache-Control')), 'no-store')) {
            throw new RuntimeException('Authorized staff streaming verification failed.');
        }

        return $bytes;
    }

    private function assertUnauthorizedStaffDenied(ViolationReport $report): void
    {
        try {
            $this->photoController->show(
                Request::create('/', 'GET'),
                $report,
                $this->storageResolver,
            );
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() === 403) {
                return;
            }
        }

        throw new RuntimeException('Unauthenticated photograph access was not denied.');
    }

    private function assertSignedRedirect(ViolationReport $report): void
    {
        $admin = User::query()->where('role', 'dilg_admin')->firstOrFail();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(static fn () => $admin);
        $response = $this->photoController->signed(
            $request,
            $report,
            $this->storageResolver,
        );
        $location = (string) $response->headers->get('Location');
        $endpointHost = parse_url((string) config(
            'filesystems.disks.supabase_report_photos.endpoint'
        ), PHP_URL_HOST);
        if ($response->getStatusCode() !== 302
            || parse_url($location, PHP_URL_HOST) !== $endpointHost
            || stripos($location, 'X-Amz-Signature=') === false
            || ! str_contains(strtolower((string) $response->headers->get('Cache-Control')), 'no-store')
            || strtolower((string) $response->headers->get('Referrer-Policy')) !== 'no-referrer') {
            throw new RuntimeException('The controlled signed redirect failed privacy verification.');
        }
    }

    private function assertPublicTrackingSafe(
        ViolationReport $report,
        string $rawTrackingToken,
    ): void {
        if (! hash_equals(
            (string) $report->tracking_token_hash,
            $this->credentialService->hashTrackingToken($rawTrackingToken),
        )) {
            throw new RuntimeException('The controlled Tracking Token failed verification.');
        }

        $response = $this->mobileController->status(
            Request::create('/', 'GET'),
            $rawTrackingToken,
            $this->credentialService,
        );
        $encoded = json_encode($response->getData(true));
        if ($response->getStatusCode() !== 200
            || ! is_string($encoded)
            || str_contains($encoded, (string) $report->photo_object_key)
            || str_contains($encoded, 'supabase_report_photos')
            || str_contains($encoded, (string) $report->photo_sha256)
            || stripos($encoded, 'X-Amz-Signature') !== false) {
            throw new RuntimeException('Public tracking exposed private storage data.');
        }
    }

    private function verifiedBytes(
        PrivateReportPhotoStorage $storage,
        ViolationReport $report,
    ): string {
        $objectKey = (string) $report->photo_object_key;
        if (! $storage->exists($objectKey)) {
            throw new RuntimeException('The controlled private object is missing.');
        }
        $stream = $storage->readStream($objectKey);
        try {
            $bytes = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
        if (! is_string($bytes)
            || strlen($bytes) !== $report->photo_size_bytes
            || ! hash_equals((string) $report->photo_sha256, hash('sha256', $bytes))) {
            throw new RuntimeException('The controlled private object failed integrity verification.');
        }

        return $bytes;
    }

    private function generatedPng(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD is required for the controlled Stage 4 image.');
        }
        $image = imagecreatetruecolor(2, 2);
        if ($image === false) {
            throw new RuntimeException('The controlled PNG could not be generated.');
        }
        $color = imagecolorallocate($image, 31, 78, 121);
        imagefill($image, 0, 0, $color);
        ob_start();
        try {
            if (! imagepng($image, null, 9)) {
                throw new RuntimeException('The controlled PNG could not be encoded.');
            }
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('The controlled PNG is empty.');
        }

        return $bytes;
    }
}

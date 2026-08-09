<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Data\SanitizedReportPhoto;
use App\Exceptions\PhotoValidationException;
use App\Models\ViolationReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReportPhotoPipeline
{
    public function __construct(
        private readonly ReportPhotoSanitizer $sanitizer,
        private readonly PrivateReportPhotoStorage $storage,
        private readonly ResolvesPrivateReportPhotoStorage $storageResolver,
    ) {}

    public function process(ViolationReport $report, ?UploadedFile $file): array
    {
        $report->refresh();

        if ($report->photo_upload_status === ViolationReport::PHOTO_STATUS_UPLOADED) {
            if (! $file instanceof UploadedFile) {
                return $this->replacementConflict();
            }

            return $this->compareCompletedReplay($report, $file);
        }

        $claim = $this->claim($report);
        if ($claim['outcome'] !== 'claimed') {
            return $claim;
        }

        $tokenHash = $claim['token_hash'];
        $staleObjectKey = $claim['stale_object_key'];
        $staleStorageDisk = $claim['stale_storage_disk'];
        if (is_string($staleObjectKey) && $staleObjectKey !== '') {
            if (! is_string($staleStorageDisk)
                || ! $this->compensateObject($staleObjectKey, $staleStorageDisk)) {
                return $this->finalizeFailure(
                    $report->id,
                    $tokenHash,
                    ViolationReport::PHOTO_STATUS_FAILED_STORAGE,
                    'PHOTO_STALE_OBJECT_CLEANUP_FAILED',
                    'A previous photograph attempt could not be cleaned up safely.',
                    keepPendingObject: true,
                    compensationStatus: 'cleanup_failed',
                );
            }

            $cleared = ViolationReport::whereKey($report->id)
                ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_PROCESSING)
                ->where('photo_processing_token_hash', $tokenHash)
                ->where('photo_pending_object_key', $staleObjectKey)
                ->update([
                    'photo_pending_object_key' => null,
                    'photo_compensation_status' => 'stale_object_deleted',
                    'updated_at' => now(),
                ]);

            if ($cleared !== 1) {
                return [
                    'outcome' => 'processing',
                    'status' => ViolationReport::PHOTO_STATUS_PROCESSING,
                    'error_code' => null,
                    'message' => 'The photograph is already being processed.',
                ];
            }
        }

        if (! $file instanceof UploadedFile) {
            return $this->finalizeFailure(
                $report->id,
                $tokenHash,
                ViolationReport::PHOTO_STATUS_FAILED_VALIDATION,
                'PHOTO_UPLOAD_ERROR',
                'The photograph upload was not received correctly.',
            );
        }

        try {
            $photo = $this->sanitizer->sanitize($file);
        } catch (PhotoValidationException $exception) {
            return $this->finalizeFailure(
                $report->id,
                $tokenHash,
                ViolationReport::PHOTO_STATUS_FAILED_VALIDATION,
                $exception->errorCode,
                $exception->safeMessage,
            );
        }

        $objectKey = $this->storage->generateObjectKey($photo->extension);
        $storageDisk = $this->storage->diskName();
        $associated = ViolationReport::whereKey($report->id)
            ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_PROCESSING)
            ->where('photo_processing_token_hash', $tokenHash)
            ->where('photo_processing_expires_at', '>', now())
            ->whereNull('photo_pending_object_key')
            ->update([
                'photo_pending_object_key' => $objectKey,
                'photo_storage_disk' => $storageDisk,
                'photo_compensation_status' => null,
                'updated_at' => now(),
            ]);

        if ($associated !== 1) {
            return [
                'outcome' => 'lease_expired',
                'status' => ViolationReport::PHOTO_STATUS_PROCESSING,
                'error_code' => 'PHOTO_LEASE_EXPIRED',
                'message' => 'The photograph processing lease expired. Retry safely.',
            ];
        }

        try {
            $this->storage->put($objectKey, $photo->bytes);
        } catch (Throwable) {
            $compensated = $this->compensateObject($objectKey, $storageDisk);

            return $this->finalizeFailure(
                $report->id,
                $tokenHash,
                ViolationReport::PHOTO_STATUS_FAILED_STORAGE,
                'PHOTO_STORAGE_FAILED',
                'The photograph could not be stored. Retry with the same submission key.',
                keepPendingObject: ! $compensated,
                compensationStatus: $compensated ? 'object_absent_or_deleted' : 'delete_failed',
            );
        }

        if ($this->finalizeSuccess(
            $report->id,
            $tokenHash,
            $objectKey,
            $storageDisk,
            $photo
        )) {
            return [
                'outcome' => 'uploaded',
                'status' => ViolationReport::PHOTO_STATUS_UPLOADED,
                'error_code' => null,
                'message' => 'The photograph was stored privately.',
            ];
        }

        $compensated = $this->compensateObject($objectKey, $storageDisk);
        $this->finalizeFailure(
            $report->id,
            $tokenHash,
            ViolationReport::PHOTO_STATUS_FAILED_STORAGE,
            'PHOTO_FINALIZATION_FAILED',
            'The photograph could not be finalized. Retry with the same submission key.',
            keepPendingObject: ! $compensated,
            compensationStatus: $compensated ? 'object_deleted' : 'delete_failed',
        );

        return [
            'outcome' => 'failed_storage',
            'status' => ViolationReport::PHOTO_STATUS_FAILED_STORAGE,
            'error_code' => 'PHOTO_FINALIZATION_FAILED',
            'message' => 'The photograph could not be finalized. Retry with the same submission key.',
        ];
    }

    private function compareCompletedReplay(
        ViolationReport $report,
        UploadedFile $file
    ): array {
        try {
            $photo = $this->sanitizer->sanitize($file);
        } catch (PhotoValidationException) {
            return $this->replacementConflict();
        }

        if (is_string($report->photo_sha256)
            && hash_equals($report->photo_sha256, $photo->sha256)) {
            return [
                'outcome' => 'already_uploaded',
                'status' => ViolationReport::PHOTO_STATUS_UPLOADED,
                'error_code' => null,
                'message' => 'The same photograph is already stored.',
            ];
        }

        return $this->replacementConflict();
    }

    private function replacementConflict(): array
    {
        return [
            'outcome' => 'conflict',
            'status' => ViolationReport::PHOTO_STATUS_UPLOADED,
            'error_code' => 'PHOTO_REPLACEMENT_NOT_ALLOWED',
            'message' => 'An uploaded photograph cannot be replaced through submission replay.',
        ];
    }

    private function claim(ViolationReport $report): array
    {
        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $rawToken);
        $leaseSeconds = max(30, (int) config('report_photos.processing_lease_seconds', 300));
        $now = now();
        $expiresAt = $now->copy()->addSeconds($leaseSeconds);

        return DB::transaction(function () use ($report, $tokenHash, $now, $expiresAt): array {
            $locked = ViolationReport::whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->photo_upload_status === ViolationReport::PHOTO_STATUS_UPLOADED) {
                return [
                    'outcome' => 'conflict',
                    'status' => ViolationReport::PHOTO_STATUS_UPLOADED,
                    'error_code' => 'PHOTO_REPLACEMENT_NOT_ALLOWED',
                    'message' => 'An uploaded photograph cannot be replaced through submission replay.',
                ];
            }

            if ($locked->photo_upload_status === ViolationReport::PHOTO_STATUS_PROCESSING
                && $locked->photo_processing_expires_at?->isFuture()) {
                return [
                    'outcome' => 'processing',
                    'status' => ViolationReport::PHOTO_STATUS_PROCESSING,
                    'error_code' => null,
                    'message' => 'The photograph is already being processed.',
                ];
            }

            $retryable = [
                ViolationReport::PHOTO_STATUS_NOT_PROVIDED,
                ViolationReport::PHOTO_STATUS_PENDING,
                ViolationReport::PHOTO_STATUS_PROCESSING,
                ViolationReport::PHOTO_STATUS_FAILED_VALIDATION,
                ViolationReport::PHOTO_STATUS_FAILED_STORAGE,
            ];
            if (! in_array($locked->photo_upload_status, $retryable, true)) {
                return [
                    'outcome' => 'conflict',
                    'status' => (string) $locked->photo_upload_status,
                    'error_code' => 'PHOTO_STATE_CONFLICT',
                    'message' => 'The photograph cannot be processed from its current state.',
                ];
            }

            $staleObjectKey = $locked->photo_pending_object_key;
            $staleStorageDisk = $locked->photo_storage_disk;
            $locked->forceFill([
                'photo_upload_status' => ViolationReport::PHOTO_STATUS_PROCESSING,
                'photo_upload_attempts' => (int) $locked->photo_upload_attempts + 1,
                'photo_processing_token_hash' => $tokenHash,
                'photo_processing_started_at' => $now,
                'photo_processing_expires_at' => $expiresAt,
                'photo_upload_error_code' => null,
                'photo_upload_error_message' => null,
                'photo_compensation_status' => $staleObjectKey
                    ? 'stale_cleanup_pending'
                    : null,
            ])->save();

            return [
                'outcome' => 'claimed',
                'status' => ViolationReport::PHOTO_STATUS_PROCESSING,
                'token_hash' => $tokenHash,
                'stale_object_key' => $staleObjectKey,
                'stale_storage_disk' => $staleStorageDisk,
            ];
        }, 3);
    }

    private function finalizeSuccess(
        int $reportId,
        string $tokenHash,
        string $objectKey,
        string $storageDisk,
        SanitizedReportPhoto $photo
    ): bool {
        return DB::transaction(function () use (
            $reportId,
            $tokenHash,
            $objectKey,
            $storageDisk,
            $photo
        ): bool {
            $locked = ViolationReport::whereKey($reportId)->lockForUpdate()->firstOrFail();
            if ($locked->photo_upload_status !== ViolationReport::PHOTO_STATUS_PROCESSING
                || ! is_string($locked->photo_processing_token_hash)
                || ! hash_equals($locked->photo_processing_token_hash, $tokenHash)
                || $locked->photo_pending_object_key !== $objectKey
                || $locked->photo_storage_disk !== $storageDisk
                || ! $locked->photo_processing_expires_at?->isFuture()) {
                return false;
            }

            $locked->forceFill([
                'photo_object_key' => $objectKey,
                'photo_pending_object_key' => null,
                'photo_storage_disk' => $storageDisk,
                'photo_mime_type' => $photo->mimeType,
                'photo_size_bytes' => strlen($photo->bytes),
                'photo_width' => $photo->width,
                'photo_height' => $photo->height,
                'photo_sha256' => $photo->sha256,
                'photo_upload_status' => ViolationReport::PHOTO_STATUS_UPLOADED,
                'photo_upload_error_code' => null,
                'photo_upload_error_message' => null,
                'photo_uploaded_at' => now(),
                'photo_processing_token_hash' => null,
                'photo_processing_started_at' => null,
                'photo_processing_expires_at' => null,
                'photo_compensation_status' => null,
                'image_path' => null,
            ])->save();

            return true;
        }, 3);
    }

    private function finalizeFailure(
        int $reportId,
        string $tokenHash,
        string $status,
        string $errorCode,
        string $message,
        bool $keepPendingObject = false,
        ?string $compensationStatus = null,
    ): array {
        ViolationReport::whereKey($reportId)
            ->where('photo_upload_status', ViolationReport::PHOTO_STATUS_PROCESSING)
            ->where('photo_processing_token_hash', $tokenHash)
            ->update([
                'photo_upload_status' => $status,
                'photo_pending_object_key' => $keepPendingObject
                    ? DB::raw('photo_pending_object_key')
                    : null,
                'photo_upload_error_code' => $errorCode,
                'photo_upload_error_message' => $message,
                'photo_processing_token_hash' => null,
                'photo_processing_started_at' => null,
                'photo_processing_expires_at' => null,
                'photo_compensation_status' => $compensationStatus,
                'updated_at' => now(),
            ]);

        return [
            'outcome' => $status,
            'status' => $status,
            'error_code' => $errorCode,
            'message' => $message,
        ];
    }

    private function compensateObject(string $objectKey, string $storageDisk): bool
    {
        try {
            $storage = $storageDisk === $this->storage->diskName()
                ? $this->storage
                : $this->storageResolver->forDisk($storageDisk);

            return ! $storage->exists($objectKey)
                || $storage->delete($objectKey);
        } catch (Throwable) {
            return false;
        }
    }
}

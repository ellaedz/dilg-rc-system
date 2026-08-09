<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Data\AiProcessingResult;
use App\Exceptions\AiProcessingException;
use App\Models\ViolationReport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class ProcessReportAi
{
    public const TRIGGER_INITIAL = 'initial';

    public const TRIGGER_STAFF_RETRY = 'staff_retry';

    public function __construct(
        private readonly ResolvesPrivateReportPhotoStorage $photoStorageResolver,
        private readonly FastApiInferenceResponseValidator $validator,
    ) {}

    public function process(
        ViolationReport $report,
        string $trigger = self::TRIGGER_INITIAL
    ): AiProcessingResult {
        try {
            $claim = $this->claim($report, $trigger);
        } catch (Throwable) {
            return new AiProcessingResult(
                'failed',
                'AI_CLAIM_FAILED',
                'AI processing could not be started safely.'
            );
        }
        if ($claim instanceof AiProcessingResult) {
            return $claim;
        }

        $tokenHash = $claim['token_hash'];
        $requestId = $claim['request_id'];

        try {
            $claimedReport = ViolationReport::findOrFail($report->id);
            $this->verifyStoredPhoto($claimedReport);
            $response = $this->send($claimedReport, $requestId);
            $body = $this->readBoundedResponse($response);

            if (! $response->successful()) {
                throw $this->httpFailure($response, $body);
            }

            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new AiProcessingException(
                    'FASTAPI_INVALID_JSON',
                    'AI processing returned an invalid response.'
                );
            }
            if (! is_array($decoded)) {
                throw new AiProcessingException(
                    'FASTAPI_SCHEMA_INVALID',
                    'AI processing returned an invalid response.'
                );
            }

            $normalized = $this->validator->validate($decoded);
            if ($normalized['image']['input']['original_width'] !== $claimedReport->photo_width
                || $normalized['image']['input']['original_height'] !== $claimedReport->photo_height) {
                throw new AiProcessingException(
                    'FASTAPI_IMAGE_DIMENSIONS_MISMATCH',
                    'AI processing returned an invalid response.'
                );
            }

            return $this->complete($claimedReport->id, $tokenHash, $requestId, $normalized);
        } catch (ConnectionException) {
            return $this->fail(
                $report->id,
                $tokenHash,
                $requestId,
                'FASTAPI_UNAVAILABLE',
                'AI processing is temporarily unavailable.'
            );
        } catch (AiProcessingException $exception) {
            return $this->fail(
                $report->id,
                $tokenHash,
                $requestId,
                $exception->errorCode,
                $exception->safeMessage
            );
        } catch (Throwable) {
            return $this->fail(
                $report->id,
                $tokenHash,
                $requestId,
                'AI_PROCESSING_FAILED',
                'AI processing is temporarily unavailable.'
            );
        }
    }

    private function claim(ViolationReport $report, string $trigger): array|AiProcessingResult
    {
        if (! in_array($trigger, [self::TRIGGER_INITIAL, self::TRIGGER_STAFF_RETRY], true)) {
            return new AiProcessingResult(
                'not_eligible',
                'AI_TRIGGER_INVALID',
                'The AI processing request is not eligible.'
            );
        }

        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $rawToken);
        $requestId = (string) Str::uuid();
        $now = now();
        $leaseSeconds = max(
            (int) config('ai_inference.timeout_seconds', 20) + 15,
            (int) config('ai_inference.processing_lease_seconds', 60)
        );
        $expiresAt = $now->copy()->addSeconds($leaseSeconds);

        return DB::transaction(function () use (
            $report,
            $trigger,
            $tokenHash,
            $requestId,
            $now,
            $expiresAt,
        ): array|AiProcessingResult {
            $locked = ViolationReport::whereKey($report->id)->lockForUpdate()->first();
            if (! $locked) {
                return new AiProcessingResult(
                    'not_eligible',
                    'REPORT_NOT_FOUND',
                    'The report is not available for AI processing.'
                );
            }

            if ($locked->ai_processing_status === ViolationReport::AI_STATUS_COMPLETED) {
                return new AiProcessingResult(
                    'not_eligible',
                    'AI_ALREADY_COMPLETED',
                    'AI processing is already complete.',
                    $locked->ai_request_id,
                );
            }

            if ($locked->ai_processing_status === ViolationReport::AI_STATUS_PROCESSING
                && $locked->ai_processing_expires_at?->isFuture()) {
                return new AiProcessingResult(
                    'already_processing',
                    null,
                    'AI processing is already in progress.',
                    $locked->ai_request_id,
                );
            }

            if ($locked->photo_upload_status !== ViolationReport::PHOTO_STATUS_UPLOADED
                || ! is_string($locked->photo_object_key)
                || $locked->photo_object_key === '') {
                return new AiProcessingResult(
                    'not_eligible',
                    'AI_PHOTO_NOT_READY',
                    'The report photograph is not ready for AI processing.'
                );
            }
            if (! is_string($locked->description)
                || trim($locked->description) === ''
                || strlen($locked->description) > 5000
                || ! is_numeric($locked->latitude)
                || ! is_numeric($locked->longitude)
                || (float) $locked->latitude < -90
                || (float) $locked->latitude > 90
                || (float) $locked->longitude < -180
                || (float) $locked->longitude > 180) {
                return new AiProcessingResult(
                    'not_eligible',
                    'AI_REPORT_EVIDENCE_INVALID',
                    'The report evidence is not eligible for AI processing.'
                );
            }

            $attempts = (int) $locked->ai_processing_attempts;
            $initialEligible = $trigger === self::TRIGGER_INITIAL
                && $locked->ai_processing_status === ViolationReport::AI_STATUS_PENDING
                && $attempts === 0;
            $staffEligible = $trigger === self::TRIGGER_STAFF_RETRY
                && (
                    ($locked->ai_processing_status === ViolationReport::AI_STATUS_PENDING
                        && $attempts === 0)
                    || $locked->ai_processing_status === ViolationReport::AI_STATUS_FAILED
                    || ($locked->ai_processing_status === ViolationReport::AI_STATUS_PROCESSING
                        && $locked->ai_processing_expires_at?->isPast())
                );

            if (! $initialEligible && ! $staffEligible) {
                return new AiProcessingResult(
                    'not_eligible',
                    'AI_STATE_NOT_ELIGIBLE',
                    'The report is not eligible for AI processing.'
                );
            }

            $locked->forceFill([
                'ai_processing_status' => ViolationReport::AI_STATUS_PROCESSING,
                'ai_processing_attempts' => $attempts + 1,
                'ai_request_id' => $requestId,
                'ai_processing_token_hash' => $tokenHash,
                'ai_processing_started_at' => $now,
                'ai_processing_expires_at' => $expiresAt,
                'ai_last_attempted_at' => $now,
                'ai_processed_at' => null,
                'processed_at' => null,
                'processing_error_code' => null,
                'processing_error_message' => null,
            ])->save();

            return [
                'token_hash' => $tokenHash,
                'request_id' => $requestId,
            ];
        });
    }

    private function verifyStoredPhoto(ViolationReport $report): void
    {
        $photoStorage = $this->photoStorage($report);
        if (! in_array($report->photo_mime_type, ['image/jpeg', 'image/png'], true)
            || ! is_int($report->photo_size_bytes)
            || $report->photo_size_bytes <= 0
            || ! is_string($report->photo_sha256)
            || ! preg_match('/\A[a-f0-9]{64}\z/D', $report->photo_sha256)
            || ! is_string($report->photo_object_key)
            || ! $photoStorage->exists($report->photo_object_key)) {
            throw new AiProcessingException(
                'AI_PHOTO_UNAVAILABLE',
                'The stored report photograph is unavailable for AI processing.'
            );
        }

        $maximum = (int) config('report_photos.max_bytes', 5 * 1024 * 1024);
        if ($report->photo_size_bytes > $maximum) {
            throw new AiProcessingException(
                'AI_PHOTO_SIZE_INVALID',
                'The stored report photograph failed integrity verification.'
            );
        }

        $stream = $photoStorage->readStream($report->photo_object_key);
        $bytesRead = 0;
        $hash = hash_init('sha256');

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, min(8192, $maximum + 1 - $bytesRead));
                if ($chunk === false) {
                    throw new AiProcessingException(
                        'AI_PHOTO_READ_FAILED',
                        'The stored report photograph is unavailable for AI processing.'
                    );
                }
                if ($chunk === '') {
                    if (feof($stream)) {
                        break;
                    }
                    throw new AiProcessingException(
                        'AI_PHOTO_READ_FAILED',
                        'The stored report photograph is unavailable for AI processing.'
                    );
                }
                $bytesRead += strlen($chunk);
                if ($bytesRead > $maximum) {
                    throw new AiProcessingException(
                        'AI_PHOTO_SIZE_INVALID',
                        'The stored report photograph failed integrity verification.'
                    );
                }
                hash_update($hash, $chunk);
            }
        } finally {
            fclose($stream);
        }

        if ($bytesRead !== $report->photo_size_bytes
            || ! hash_equals($report->photo_sha256, hash_final($hash))) {
            throw new AiProcessingException(
                'AI_PHOTO_INTEGRITY_MISMATCH',
                'The stored report photograph failed integrity verification.'
            );
        }
    }

    private function send(ViolationReport $report, string $requestId): Response
    {
        $stream = $this->photoStorage($report)->readStream($report->photo_object_key);
        $extension = $report->photo_mime_type === 'image/png' ? 'png' : 'jpg';

        try {
            return Http::acceptJson()
                ->withHeaders(['X-Request-ID' => $requestId])
                ->connectTimeout((int) config('ai_inference.connect_timeout_seconds', 3))
                ->timeout((int) config('ai_inference.timeout_seconds', 20))
                ->withOptions(['stream' => true])
                ->attach(
                    'image',
                    $stream,
                    'report-evidence.'.$extension,
                    ['Content-Type' => $report->photo_mime_type]
                )
                ->post(
                    rtrim((string) config('ai_inference.url'), '/').'/v1/predict/multimodal',
                    [
                        'text_report' => $report->description,
                        'latitude' => (string) $report->latitude,
                        'longitude' => (string) $report->longitude,
                    ]
                );
        } finally {
            fclose($stream);
        }
    }

    private function photoStorage(ViolationReport $report): PrivateReportPhotoStorage
    {
        try {
            return $this->photoStorageResolver->forDisk(
                (string) $report->photo_storage_disk
            );
        } catch (Throwable) {
            throw new AiProcessingException(
                'AI_PHOTO_UNAVAILABLE',
                'The stored report photograph is unavailable for AI processing.'
            );
        }
    }

    private function readBoundedResponse(Response $response): string
    {
        $maximum = (int) config('ai_inference.max_response_bytes', 262144);
        $declaredLength = $response->header('Content-Length');
        if (is_string($declaredLength)
            && ctype_digit($declaredLength)
            && (int) $declaredLength > $maximum) {
            throw new AiProcessingException(
                'FASTAPI_RESPONSE_TOO_LARGE',
                'AI processing returned an invalid response.'
            );
        }

        $stream = $response->toPsrResponse()->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $body = '';
        while (! $stream->eof()) {
            $remaining = $maximum + 1 - strlen($body);
            if ($remaining <= 0) {
                break;
            }
            $body .= $stream->read(min(8192, $remaining));
        }
        if (strlen($body) > $maximum || ! $stream->eof()) {
            throw new AiProcessingException(
                'FASTAPI_RESPONSE_TOO_LARGE',
                'AI processing returned an invalid response.'
            );
        }

        return $body;
    }

    private function httpFailure(Response $response, string $body): AiProcessingException
    {
        $status = $response->status();
        $code = match (true) {
            in_array($status, [400, 413, 415, 422], true) => 'FASTAPI_REQUEST_REJECTED',
            in_array($status, [401, 403], true) => 'FASTAPI_ACCESS_REJECTED',
            $status === 404 => 'FASTAPI_ENDPOINT_NOT_FOUND',
            $status === 408 => 'FASTAPI_TIMEOUT',
            $status === 429 => 'FASTAPI_RATE_LIMITED',
            $status === 500 => 'FASTAPI_INTERNAL_ERROR',
            in_array($status, [502, 503, 504], true) => 'FASTAPI_UNAVAILABLE',
            default => 'FASTAPI_HTTP_ERROR',
        };

        try {
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            $remoteCode = $decoded['error']['code'] ?? null;
            $remoteMessage = $decoded['error']['message'] ?? null;
            if (! is_string($remoteCode)
                || ! preg_match('/\A[a-z0-9_]{1,64}\z/D', $remoteCode)
                || ! is_string($remoteMessage)
                || strlen($remoteMessage) > 255) {
                $code = 'FASTAPI_INVALID_ERROR_RESPONSE';
            }
        } catch (JsonException) {
            $code = 'FASTAPI_INVALID_ERROR_RESPONSE';
        }

        return new AiProcessingException(
            $code,
            'AI processing is temporarily unavailable.'
        );
    }

    private function complete(
        int $reportId,
        string $tokenHash,
        string $requestId,
        array $result
    ): AiProcessingResult {
        $updated = DB::transaction(function () use ($reportId, $tokenHash, $result): bool {
            $report = ViolationReport::whereKey($reportId)->lockForUpdate()->first();
            if (! $report
                || $report->ai_processing_status !== ViolationReport::AI_STATUS_PROCESSING
                || ! is_string($report->ai_processing_token_hash)
                || ! hash_equals($report->ai_processing_token_hash, $tokenHash)) {
                return false;
            }

            $reviewReasons = $result['review']['ai_manual_review_reasons'];
            $report->forceFill([
                'predicted_violation_category' => $result['image']['prediction'],
                'confidence_score' => $result['image']['confidence'],
                'image_validation_status' => $this->compatibilityImageStatus($result['image']),
                'image_model_version' => $result['models']['image']['model_version'],
                'text_prediction' => $result['text']['prediction'],
                'text_confidence' => $result['text']['confidence'],
                'final_ai_prediction' => $result['fusion']['final_violation_type'],
                'final_ai_confidence' => $result['fusion']['final_confidence'],
                'ai_decision_source' => $result['fusion']['decision_source'],
                'ai_needs_manual_review' => $result['review']['ai_needs_manual_review'],
                'ai_manual_review_reason' => $reviewReasons[0] ?? null,
                'ai_manual_review_reasons' => $reviewReasons,
                'needs_manual_review' => $result['review']['ai_needs_manual_review'],
                'ai_processing_status' => ViolationReport::AI_STATUS_COMPLETED,
                'ai_processed_at' => now(),
                'processed_at' => now(),
                'ai_model_version' => $result['text']['model_version'],
                'ai_image_prediction' => $result['image']['prediction'],
                'ai_image_confidence' => $result['image']['confidence'],
                'ai_image_status' => $result['image']['status'],
                'ai_image_detections' => $result['image']['detections'],
                'ai_gis_result' => $result['gis'],
                'ai_model_metadata' => $result['models'],
                'ai_timing' => $result['timing'],
                'ai_raw_response' => $result,
                'ai_possible_violation' => $result['fusion']['final_violation_type'],
                'ai_possible_violation_confidence' => $result['fusion']['final_confidence'],
                'processing_error_code' => null,
                'processing_error_message' => null,
                'ai_processing_token_hash' => null,
                'ai_processing_started_at' => null,
                'ai_processing_expires_at' => null,
            ])->save();

            return true;
        });

        if (! $updated) {
            return new AiProcessingResult(
                'stale_ownership',
                'AI_STALE_OWNERSHIP',
                'The AI processing result was superseded by another attempt.',
                $requestId,
            );
        }

        return new AiProcessingResult(
            'completed',
            null,
            'AI processing completed.',
            $requestId,
        );
    }

    private function fail(
        int $reportId,
        string $tokenHash,
        string $requestId,
        string $errorCode,
        string $message
    ): AiProcessingResult {
        $updated = ViolationReport::whereKey($reportId)
            ->where('ai_processing_status', ViolationReport::AI_STATUS_PROCESSING)
            ->where('ai_processing_token_hash', $tokenHash)
            ->update([
                'ai_processing_status' => ViolationReport::AI_STATUS_FAILED,
                'ai_processed_at' => null,
                'processed_at' => null,
                'processing_error_code' => $errorCode,
                'processing_error_message' => $message,
                'ai_processing_token_hash' => null,
                'ai_processing_started_at' => null,
                'ai_processing_expires_at' => null,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return new AiProcessingResult(
                'stale_ownership',
                'AI_STALE_OWNERSHIP',
                'The AI processing failure was superseded by another attempt.',
                $requestId,
            );
        }

        return new AiProcessingResult('failed', $errorCode, $message, $requestId);
    }

    private function compatibilityImageStatus(array $image): string
    {
        if ($image['status'] === 'no_detection') {
            return 'no_detection';
        }

        return $image['confidence'] < 0.60 ? 'low_confidence' : 'accepted';
    }
}

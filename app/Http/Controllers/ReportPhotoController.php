<?php

namespace App\Http\Controllers;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Contracts\TemporaryPrivateReportPhotoUrlProvider;
use App\Models\ViolationReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportPhotoController extends Controller
{
    public function show(
        Request $request,
        ViolationReport $violationReport,
        ResolvesPrivateReportPhotoStorage $storageResolver,
    ): StreamedResponse {
        $this->authorizeStaffAccess($request, $violationReport);

        $objectKey = $violationReport->photo_object_key;
        $storage = $this->storageFor($violationReport, $storageResolver);
        abort_unless(
            is_string($objectKey)
            && $violationReport->photo_upload_status === ViolationReport::PHOTO_STATUS_UPLOADED
            && $storage->exists($objectKey),
            404
        );

        $stream = $storage->readStream($objectKey);
        $mimeType = in_array($violationReport->photo_mime_type, ['image/jpeg', 'image/png'], true)
            ? $violationReport->photo_mime_type
            : 'application/octet-stream';
        $extension = $mimeType === 'image/png' ? 'png' : 'jpg';

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="report-evidence.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function signed(
        Request $request,
        ViolationReport $violationReport,
        ResolvesPrivateReportPhotoStorage $storageResolver,
    ): RedirectResponse {
        $this->authorizeStaffAccess($request, $violationReport);

        $objectKey = $violationReport->photo_object_key;
        $storage = $this->storageFor($violationReport, $storageResolver);
        abort_unless(
            is_string($objectKey)
            && $violationReport->photo_upload_status === ViolationReport::PHOTO_STATUS_UPLOADED
            && $storage instanceof TemporaryPrivateReportPhotoUrlProvider
            && $storage->exists($objectKey),
            404
        );

        $ttl = max(30, min(
            900,
            (int) config('report_photos.signed_url_ttl_seconds', 120)
        ));
        $response = redirect()->away(
            $storage->temporaryUrl($objectKey, now()->addSeconds($ttl))
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    private function authorizeStaffAccess(
        Request $request,
        ViolationReport $violationReport
    ): void {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($user->role === 'barangay_staff'
            && strcasecmp(
                (string) $violationReport->effective_barangay,
                (string) $user->assigned_barangay
            ) !== 0) {
            abort(403);
        }
        abort_unless(in_array($user->role, ['dilg_admin', 'barangay_staff'], true), 403);
    }

    private function storageFor(
        ViolationReport $violationReport,
        ResolvesPrivateReportPhotoStorage $storageResolver
    ): PrivateReportPhotoStorage {
        try {
            return $storageResolver->forDisk((string) $violationReport->photo_storage_disk);
        } catch (RuntimeException) {
            abort(404);
        }
    }
}

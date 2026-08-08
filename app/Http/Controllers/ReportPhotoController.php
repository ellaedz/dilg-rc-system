<?php

namespace App\Http\Controllers;

use App\Contracts\PrivateReportPhotoStorage;
use App\Models\ViolationReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportPhotoController extends Controller
{
    public function show(
        Request $request,
        ViolationReport $violationReport,
        PrivateReportPhotoStorage $storage,
    ): StreamedResponse {
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

        $objectKey = $violationReport->photo_object_key;
        abort_unless(
            is_string($objectKey)
            && $violationReport->photo_upload_status === ViolationReport::PHOTO_STATUS_UPLOADED
            && $violationReport->photo_storage_disk === $storage->diskName()
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
}

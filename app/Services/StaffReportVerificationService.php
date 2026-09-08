<?php

namespace App\Services;

use App\Models\ReportTimeline;
use App\Models\User;
use App\Models\ViolationReport;
use App\Support\OfficialViolationType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffReportVerificationService
{
    public const REJECTION_OUTCOMES = [
        'Invalid Report',
        'Duplicate',
        'Outside Jurisdiction',
        'Insufficient Evidence',
    ];

    public function verify(
        User $staff,
        string $barangay,
        ViolationReport $report,
        string $officialType,
        ?string $remarks,
        ?string $correctionReason,
    ): ViolationReport {
        $this->authorizeReviewer($staff, $barangay, $report);

        if (! in_array($officialType, OfficialViolationType::all(), true)) {
            throw ValidationException::withMessages([
                'official_violation_type' => 'Select a valid official violation type.',
            ]);
        }

        return DB::transaction(function () use ($staff, $barangay, $report, $officialType, $remarks, $correctionReason) {
            $lockedReport = ViolationReport::query()->lockForUpdate()->findOrFail($report->id);
            $this->authorizeReviewer($staff, $barangay, $lockedReport);
            $this->ensurePending($lockedReport);

            $aiPrediction = $lockedReport->final_ai_prediction ?: $lockedReport->ai_possible_violation;
            $aiOfficialType = OfficialViolationType::fromAi($aiPrediction);
            $agreement = $aiPrediction ? $aiOfficialType === $officialType : null;

            if ($agreement === false && blank($correctionReason)) {
                throw ValidationException::withMessages([
                    'correction_reason' => 'Briefly explain why the official class differs from the AI suggestion.',
                ]);
            }

            $oldStatus = $lockedReport->status;
            $lockedReport->update([
                'status' => 'Verified',
                'verification_status' => 'Valid Violation',
                'official_violation_type' => $officialType,
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'ai_prediction_at_verification' => $aiPrediction,
                'ai_confidence_at_verification' => $lockedReport->final_ai_confidence
                    ?? $lockedReport->ai_possible_violation_confidence,
                'staff_agreed_with_ai' => $agreement,
                'staff_verification_reason' => $agreement === false ? $correctionReason : $remarks,
                'is_duplicate' => false,
                'remarks' => $remarks ?: $lockedReport->remarks,
                'date_updated' => now(),
            ]);

            $agreementText = $agreement === null ? 'AI result unavailable' : ($agreement ? 'agreed with AI' : 'corrected AI');
            ReportTimeline::create([
                'report_id' => $lockedReport->id,
                'status' => 'Verified',
                'old_status' => $oldStatus,
                'remarks' => sprintf('Official classification: %s (%s).%s', $officialType, $agreementText, $correctionReason ? ' '.$correctionReason : ''),
                'updated_by' => $staff->id,
            ]);

            return $lockedReport->fresh();
        });
    }

    public function reject(
        User $staff,
        string $barangay,
        ViolationReport $report,
        string $outcome,
        string $reason,
    ): ViolationReport {
        $this->authorizeReviewer($staff, $barangay, $report);

        if (! in_array($outcome, self::REJECTION_OUTCOMES, true)) {
            throw ValidationException::withMessages([
                'verification_status' => 'Select a valid rejection reason.',
            ]);
        }

        return DB::transaction(function () use ($staff, $barangay, $report, $outcome, $reason) {
            $lockedReport = ViolationReport::query()->lockForUpdate()->findOrFail($report->id);
            $this->authorizeReviewer($staff, $barangay, $lockedReport);
            $this->ensurePending($lockedReport);

            $aiPrediction = $lockedReport->final_ai_prediction ?: $lockedReport->ai_possible_violation;
            $agreement = $outcome === 'Invalid Report' && $aiPrediction
                ? $aiPrediction === 'no_violation'
                : null;
            $oldStatus = $lockedReport->status;

            $lockedReport->update([
                'status' => 'Rejected',
                'verification_status' => $outcome,
                'official_violation_type' => null,
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'ai_prediction_at_verification' => $aiPrediction,
                'ai_confidence_at_verification' => $lockedReport->final_ai_confidence
                    ?? $lockedReport->ai_possible_violation_confidence,
                'staff_agreed_with_ai' => $agreement,
                'staff_verification_reason' => $reason,
                'is_duplicate' => $outcome === 'Duplicate',
                'remarks' => $reason,
                'date_updated' => now(),
            ]);

            ReportTimeline::create([
                'report_id' => $lockedReport->id,
                'status' => 'Rejected',
                'old_status' => $oldStatus,
                'remarks' => $outcome.': '.$reason,
                'updated_by' => $staff->id,
            ]);

            return $lockedReport->fresh();
        });
    }

    private function authorizeReviewer(User $user, string $barangay, ViolationReport $report): void
    {
        if (! RoleService::isBarangayStaff($user)
            || ! RoleService::canAccessBarangay($user, $barangay)
            || strcasecmp((string) $report->effective_barangay, $barangay) !== 0) {
            throw new AuthorizationException('Only the assigned barangay staff may verify this report.');
        }
    }

    private function ensurePending(ViolationReport $report): void
    {
        if ($report->verification_status !== 'Pending'
            || ! in_array($report->status, ['Submitted', 'For Verification'], true)) {
            throw ValidationException::withMessages([
                'report' => 'This report already has a staff verification decision.',
            ]);
        }
    }
}

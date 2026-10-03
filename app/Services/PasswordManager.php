<?php

namespace App\Services;

use App\Enums\SecurityAuditAction;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordManager
{
    public function __construct(
        private readonly SecurityAuditLogger $auditLogger,
        private readonly SecuritySessionManager $sessionManager,
    ) {}

    public function resetByAdministrator(
        User $actor,
        User $target,
        string $temporaryPassword,
        Request $request,
    ): void {
        DB::transaction(function () use ($actor, $target, $temporaryPassword, $request): void {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());

            $this->rememberCurrentHash($lockedTarget);
            $lockedTarget->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
                'session_version' => (int) $lockedTarget->session_version + 1,
            ])->save();
            $this->pruneHistory($lockedTarget);

            $this->auditLogger->record(
                SecurityAuditAction::PasswordReset,
                $actor,
                $lockedTarget,
                $request,
            );
        });

        $target->refresh();
        $this->sessionManager->invalidateAll($target);
    }

    public function changeOwnPassword(
        User $user,
        string $newPassword,
        Request $request,
    ): void {
        DB::transaction(function () use ($user, $newPassword, $request): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $this->rememberCurrentHash($lockedUser);
            $lockedUser->forceFill([
                'password' => Hash::make($newPassword),
                'must_change_password' => false,
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
                'session_version' => (int) $lockedUser->session_version + 1,
            ])->save();
            $this->pruneHistory($lockedUser);

            $this->auditLogger->record(
                SecurityAuditAction::PasswordChanged,
                $lockedUser,
                $lockedUser,
                $request,
            );
        });

        $user->refresh();
        $this->sessionManager->invalidateOthersAndRegenerate($request, $user);
    }

    private function rememberCurrentHash(User $user): void
    {
        PasswordHistory::query()->create([
            'user_id' => $user->getKey(),
            'password_hash' => $user->getRawOriginal('password'),
        ]);
    }

    private function pruneHistory(User $user): void
    {
        $limit = max(1, (int) config('security.password.history_limit', 5));
        $obsoleteIds = $user->passwordHistories()
            ->latest('id')
            ->skip($limit)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($obsoleteIds->isNotEmpty()) {
            PasswordHistory::query()->whereKey($obsoleteIds)->delete();
        }
    }
}

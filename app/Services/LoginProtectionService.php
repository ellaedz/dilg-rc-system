<?php

namespace App\Services;

use App\Enums\SecurityAuditAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginProtectionService
{
    public const GENERIC_ERROR = 'Sign-in failed. Check your credentials or try again later.';

    private static ?string $dummyPasswordHash = null;

    public function __construct(
        private readonly SecurityAuditLogger $auditLogger,
    ) {}

    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function emailKey(string $normalizedEmail): string
    {
        return 'login:email:'.hash('sha256', $normalizedEmail);
    }

    public function ipKey(?string $ipAddress): string
    {
        return 'login:ip:'.hash('sha256', $ipAddress ?: 'unknown');
    }

    public function isRateLimited(string $normalizedEmail, ?string $ipAddress): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->emailKey($normalizedEmail),
            max(1, (int) config('security.login.email_max_attempts', 5)),
        ) || RateLimiter::tooManyAttempts(
            $this->ipKey($ipAddress),
            max(1, (int) config('security.login.ip_max_attempts', 20)),
        );
    }

    public function hit(string $normalizedEmail, ?string $ipAddress): void
    {
        $decaySeconds = max(1, (int) config('security.login.decay_seconds', 300));

        RateLimiter::hit($this->emailKey($normalizedEmail), $decaySeconds);
        RateLimiter::hit($this->ipKey($ipAddress), $decaySeconds);
    }

    public function clearEmailLimit(string $normalizedEmail): void
    {
        RateLimiter::clear($this->emailKey($normalizedEmail));
    }

    public function consumeDummyPasswordCheck(string $candidate): void
    {
        self::$dummyPasswordHash ??= Hash::make(Str::random(32));
        Hash::check($candidate, self::$dummyPasswordHash);
    }

    public function clearExpiredLock(User $user, Request $request): bool
    {
        if (! $user->locked_until || $user->locked_until->isFuture()) {
            return false;
        }

        $cleared = DB::transaction(function () use ($user, $request): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $lockedUser->locked_until || $lockedUser->locked_until->isFuture()) {
                return false;
            }

            $lockedUser->forceFill(['locked_until' => null])->save();
            $this->auditLogger->record(
                SecurityAuditAction::AccountUnlocked,
                null,
                $lockedUser,
                $request,
            );

            return true;
        });

        if ($cleared) {
            $user->refresh();
        }

        return $cleared;
    }

    public function recordFailedPassword(User $user, Request $request): void
    {
        DB::transaction(function () use ($user, $request): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->locked_until?->isFuture()) {
                return;
            }

            $attempts = min(4_294_967_295, (int) $lockedUser->failed_login_attempts + 1);
            $threshold = max(1, (int) config('security.login.lockout_threshold', 5));
            $lockedUntil = null;

            if ($attempts >= $threshold) {
                $durations = collect(config('security.login.lockout_minutes', [5, 15, 30]))
                    ->map(fn (mixed $minutes): int => max(1, (int) $minutes))
                    ->values();
                $level = min(
                    intdiv($attempts - $threshold, $threshold),
                    max(0, $durations->count() - 1),
                );
                $lockedUntil = now()->addMinutes($durations->get($level, 5));
            }

            $lockedUser->forceFill([
                'failed_login_attempts' => $attempts,
                'last_failed_login_at' => now(),
                'locked_until' => $lockedUntil,
            ])->save();

            if ($lockedUntil) {
                $this->auditLogger->record(
                    SecurityAuditAction::AccountLocked,
                    null,
                    $lockedUser,
                    $request,
                );
            }
        });

        $user->refresh();
    }

    public function recordSuccessfulLogin(User $user): void
    {
        if ($user->failed_login_attempts === 0 && ! $user->last_failed_login_at && ! $user->locked_until) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->forceFill([
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
            ])->save();
        });

        $user->refresh();
    }

    public function unlockByAdministrator(User $actor, User $target, Request $request): bool
    {
        $changed = DB::transaction(function () use ($actor, $target, $request): bool {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());

            if (! $lockedTarget->locked_until && $lockedTarget->failed_login_attempts === 0) {
                return false;
            }

            $lockedTarget->forceFill([
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
            ])->save();
            $this->auditLogger->record(
                SecurityAuditAction::AccountUnlocked,
                $actor,
                $lockedTarget,
                $request,
            );

            return true;
        });

        if ($changed) {
            $target->refresh();
            $this->clearEmailLimit($this->normalizeEmail($target->email));
        }

        return $changed;
    }
}

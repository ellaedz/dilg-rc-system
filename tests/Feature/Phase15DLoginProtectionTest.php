<?php

namespace Tests\Feature;

use App\Enums\SecurityAuditAction;
use App\Http\Middleware\EnsureSecuritySession;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Services\LoginProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase15DLoginProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('security.login.email_max_attempts', 2);
        config()->set('security.login.ip_max_attempts', 3);
        config()->set('security.login.decay_seconds', 60);
        config()->set('security.login.lockout_threshold', 2);
        config()->set('security.login.lockout_minutes', [1, 2]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_login_normalizes_email_before_lookup_and_throttling(): void
    {
        $password = Str::random(32);
        $user = $this->barangayUser('Alipit', $password);

        $response = $this->postLogin('  '.Str::upper($user->email).'  ', $password, '203.0.113.10');

        $response->assertRedirect(route('barangay.dashboard', 'Alipit'))
            ->assertSessionHas(EnsureSecuritySession::SESSION_VERSION_KEY, 1);
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_and_ip_rate_limits_are_independent_and_bounded(): void
    {
        $service = app(LoginProtectionService::class);
        $email = 'missing@example.test';
        $firstIp = '203.0.113.11';
        $secondIp = '203.0.113.12';

        $this->postLogin($email, Str::random(32), $firstIp)->assertSessionHasErrors('email');
        $this->postLogin(Str::upper($email), Str::random(32), $firstIp)->assertSessionHasErrors('email');

        $this->assertTrue($service->isRateLimited($email, $secondIp));
        $this->postLogin($email, Str::random(32), $secondIp)->assertSessionHasErrors('email');
        $this->assertSame(2, RateLimiter::attempts($service->emailKey($email)));
        $this->assertSame(2, RateLimiter::attempts($service->ipKey($firstIp)));
        $this->assertSame(0, RateLimiter::attempts($service->ipKey($secondIp)));

        $sharedIp = '203.0.113.13';
        foreach (range(1, 3) as $attempt) {
            $this->postLogin("absent{$attempt}@example.test", Str::random(32), $sharedIp)
                ->assertSessionHasErrors('email');
        }

        $newEmail = 'another-absent@example.test';
        $this->assertTrue($service->isRateLimited($newEmail, $sharedIp));
        $this->postLogin($newEmail, Str::random(32), $sharedIp)->assertSessionHasErrors('email');
        $this->assertSame(3, RateLimiter::attempts($service->ipKey($sharedIp)));
        $this->assertSame(0, RateLimiter::attempts($service->emailKey($newEmail)));
    }

    public function test_repeated_wrong_password_temporarily_locks_and_audits_account(): void
    {
        $user = $this->barangayUser('Alipit');
        $candidate = Str::random(32);

        $this->postLogin($user->email, $candidate, '203.0.113.20');
        $this->postLogin($user->email, $candidate, '203.0.113.20');

        $user->refresh();
        $this->assertSame(2, $user->failed_login_attempts);
        $this->assertTrue($user->locked_until->isFuture());

        $event = SecurityAuditEvent::query()->sole();
        $this->assertSame(SecurityAuditAction::AccountLocked, $event->action);
        $this->assertNull($event->actor_user_id);
        $this->assertSame($user->id, $event->target_user_id);
        $this->assertSame('203.0.113.20', $event->ip_address);
        $this->assertStringNotContainsString($candidate, json_encode($event->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_missing_wrong_and_locked_accounts_receive_the_same_error(): void
    {
        $user = $this->barangayUser('Alipit');
        $wrong = $this->postLogin($user->email, Str::random(32), '203.0.113.30');
        $missing = $this->postLogin('not-present@example.test', Str::random(32), '203.0.113.31');

        $user->forceFill(['locked_until' => now()->addMinutes(5)])->save();
        $locked = $this->postLogin($user->email, Str::random(32), '203.0.113.32');

        $this->assertSame(LoginProtectionService::GENERIC_ERROR, $wrong->getSession()->get('errors')->first('email'));
        $this->assertSame(LoginProtectionService::GENERIC_ERROR, $missing->getSession()->get('errors')->first('email'));
        $this->assertSame(LoginProtectionService::GENERIC_ERROR, $locked->getSession()->get('errors')->first('email'));
    }

    public function test_expired_lock_is_audited_and_correct_login_succeeds(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');
        $password = Str::random(32);
        $user = $this->barangayUser('Alipit', $password);
        $ip = '203.0.113.40';

        $this->postLogin($user->email, Str::random(32), $ip);
        $this->postLogin($user->email, Str::random(32), $ip);
        $this->assertNotNull($user->fresh()->locked_until);

        Carbon::setTestNow(now()->addSeconds(61));
        $response = $this->postLogin($user->email, $password, $ip);

        $response->assertRedirect(route('barangay.dashboard', 'Alipit'));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNull($user->locked_until);
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertDatabaseHas('security_audit_events', [
            'target_user_id' => $user->id,
            'action' => SecurityAuditAction::AccountLocked->value,
        ]);
        $this->assertDatabaseHas('security_audit_events', [
            'target_user_id' => $user->id,
            'action' => SecurityAuditAction::AccountUnlocked->value,
            'actor_user_id' => null,
        ]);
    }

    public function test_escalating_lockout_has_a_fixed_maximum_and_never_becomes_permanent(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');
        $user = $this->barangayUser('Alipit');
        $service = app(LoginProtectionService::class);
        $request = Request::create('/login', 'POST', server: ['REMOTE_ADDR' => '203.0.113.50']);

        $service->recordFailedPassword($user, $request);
        $service->recordFailedPassword($user, $request);
        $this->assertSame(60.0, now()->diffInSeconds($user->fresh()->locked_until));

        Carbon::setTestNow(now()->addSeconds(61));
        $service->clearExpiredLock($user, $request);
        $service->recordFailedPassword($user, $request);
        Carbon::setTestNow(now()->addSeconds(61));
        $service->clearExpiredLock($user, $request);
        $service->recordFailedPassword($user, $request);
        $this->assertSame(120.0, now()->diffInSeconds($user->fresh()->locked_until));

        Carbon::setTestNow(now()->addSeconds(121));
        $service->clearExpiredLock($user, $request);
        $service->recordFailedPassword($user, $request);
        $lockedUntil = $user->fresh()->locked_until;

        $this->assertSame(120.0, now()->diffInSeconds($lockedUntil));
        $this->assertTrue($lockedUntil->isFuture());
    }

    public function test_only_dilg_administrator_can_manually_unlock_and_action_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $staff = $this->barangayUser('Alipit');
        $otherStaff = $this->barangayUser('Calios');
        $protection = app(LoginProtectionService::class);
        $staff->forceFill([
            'failed_login_attempts' => 7,
            'last_failed_login_at' => now(),
            'locked_until' => now()->addMinutes(15),
        ])->save();
        $protection->hit($staff->email, '203.0.113.60');
        $protection->hit($staff->email, '203.0.113.60');
        $this->assertTrue($protection->isRateLimited($staff->email, '203.0.113.61'));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.60'])
            ->actingAs($admin)
            ->put(route('account-management.unlock', $staff))
            ->assertRedirect()
            ->assertSessionHas('success');

        $staff->refresh();
        $this->assertSame(0, $staff->failed_login_attempts);
        $this->assertNull($staff->last_failed_login_at);
        $this->assertNull($staff->locked_until);
        $this->assertFalse($protection->isRateLimited($staff->email, '203.0.113.61'));
        $this->assertDatabaseHas('security_audit_events', [
            'actor_user_id' => $admin->id,
            'target_user_id' => $staff->id,
            'action' => SecurityAuditAction::AccountUnlocked->value,
            'ip_address' => '203.0.113.60',
        ]);

        $staff->forceFill(['locked_until' => now()->addMinutes(5)])->save();
        $this->actingAs($otherStaff)
            ->put(route('account-management.unlock', $staff))
            ->assertForbidden();
        $this->assertNotNull($staff->fresh()->locked_until);
    }

    public function test_login_page_recovery_instructions_name_authorized_dilg_administrator(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Contact the authorized DILG administrator for password assistance.')
            ->assertSee('CIVICLEAR does not display existing passwords.');
    }

    private function postLogin(string $email, string $password, string $ipAddress)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->post(route('login.post'), [
                'email' => $email,
                'password' => $password,
            ]);
    }

    private function barangayUser(string $barangay, ?string $password = null): User
    {
        return User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => $barangay,
            'password' => Hash::make($password ?? Str::random(32)),
        ]);
    }
}

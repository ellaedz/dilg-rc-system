<?php

namespace Tests\Feature;

use App\Enums\SecurityAuditAction;
use App\Models\PasswordHistory;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Services\CiviclearPasswordPolicy;
use App\Services\SecurityAuditLogger;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase15BPasswordDataFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_compatible_accounts_receive_safe_security_defaults(): void
    {
        $user = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $user->refresh();

        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->last_failed_login_at);
        $this->assertNull($user->locked_until);
        $this->assertNull($user->password_changed_at);
        $this->assertSame(1, $user->session_version);
    }

    public function test_password_history_keeps_only_a_hidden_hash(): void
    {
        $user = User::factory()->create();
        $plaintext = Str::random(32);
        $history = PasswordHistory::query()->create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($plaintext),
        ]);

        $this->assertTrue(Hash::check($plaintext, $history->password_hash));
        $this->assertArrayNotHasKey('password_hash', $history->toArray());
        $this->assertDatabaseMissing('password_histories', [
            'password_hash' => $plaintext,
        ]);
    }

    public function test_password_policy_requires_at_least_sixteen_characters(): void
    {
        $this->bindUncompromisedVerifier(true);
        $user = User::factory()->create([
            'password' => Hash::make(Str::random(32)),
        ]);

        $validator = $this->validatePassword($user, Str::random(15));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_password_policy_rejects_a_compromised_password(): void
    {
        $this->bindUncompromisedVerifier(false);
        $user = User::factory()->create([
            'password' => Hash::make(Str::random(32)),
        ]);

        $validator = $this->validatePassword($user, Str::random(32));

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'data leak',
            $validator->errors()->first('password'),
        );
    }

    public function test_password_policy_rejects_a_locally_blocked_password(): void
    {
        $this->bindUncompromisedVerifier(true);
        $blockedPassword = Str::random(32);
        config()->set('security.password.blocked_sha256', [
            hash('sha256', mb_strtolower($blockedPassword, 'UTF-8')),
        ]);
        $user = User::factory()->create([
            'password' => Hash::make(Str::random(32)),
        ]);

        $validator = $this->validatePassword($user, $blockedPassword);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'too common',
            $validator->errors()->first('password'),
        );
    }

    public function test_password_policy_rejects_current_and_recent_passwords(): void
    {
        $this->bindUncompromisedVerifier(true);
        $current = Str::random(32);
        $recent = Str::random(32);
        $user = User::factory()->create([
            'password' => Hash::make($current),
        ]);
        PasswordHistory::query()->create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($recent),
        ]);

        $currentValidator = $this->validatePassword($user, $current);
        $recentValidator = $this->validatePassword($user, $recent);

        $this->assertTrue($currentValidator->fails());
        $this->assertStringContainsString(
            'different from the current password',
            $currentValidator->errors()->first('password'),
        );
        $this->assertTrue($recentValidator->fails());
        $this->assertStringContainsString(
            'used recently',
            $recentValidator->errors()->first('password'),
        );
    }

    public function test_password_policy_accepts_a_long_uncompromised_passphrase(): void
    {
        $this->bindUncompromisedVerifier(true);
        $user = User::factory()->create([
            'password' => Hash::make(Str::random(32)),
        ]);

        $validator = $this->validatePassword(
            $user,
            Str::random(32),
        );

        $this->assertFalse($validator->fails(), $validator->errors()->first());
    }

    public function test_security_audit_records_required_context_without_password_fields(): void
    {
        $actor = User::factory()->create(['role' => 'dilg_admin']);
        $target = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);
        $request = Request::create('/account-management', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.25',
            'HTTP_USER_AGENT' => str_repeat('A', 600),
        ]);

        $event = app(SecurityAuditLogger::class)->record(
            SecurityAuditAction::PasswordReset,
            $actor,
            $target,
            $request,
        );

        $this->assertSame($actor->id, $event->actor_user_id);
        $this->assertSame($target->id, $event->target_user_id);
        $this->assertSame(SecurityAuditAction::PasswordReset, $event->action);
        $this->assertSame('203.0.113.25', $event->ip_address);
        $this->assertSame(512, mb_strlen($event->user_agent));
        $this->assertNotNull($event->created_at);
        $this->assertStringNotContainsString(
            'password_hash',
            json_encode($event->toArray(), JSON_THROW_ON_ERROR),
        );
        $this->assertSame(1, SecurityAuditEvent::query()->count());
    }

    private function validatePassword(User $user, string $password)
    {
        return Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], [
            'password' => app(CiviclearPasswordPolicy::class)->rules($user),
        ]);
    }

    private function bindUncompromisedVerifier(bool $result): void
    {
        $this->app->instance(
            UncompromisedVerifier::class,
            new class($result) implements UncompromisedVerifier
            {
                public function __construct(
                    private readonly bool $result,
                ) {}

                public function verify($data)
                {
                    return $this->result;
                }
            },
        );
    }
}

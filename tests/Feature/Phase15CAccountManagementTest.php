<?php

namespace Tests\Feature;

use App\Enums\SecurityAuditAction;
use App\Http\Middleware\EnsureSecuritySession;
use App\Models\PasswordHistory;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Services\SecuritySessionManager;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase15CAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            UncompromisedVerifier::class,
            new class implements UncompromisedVerifier
            {
                public function verify($data): bool
                {
                    return true;
                }
            },
        );
    }

    public function test_dilg_administrator_lists_all_twenty_six_barangay_accounts_without_passwords(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $accounts = collect(config('santa_cruz_barangays.barangays'))
            ->map(fn (array $barangay) => User::factory()->create([
                'role' => 'barangay_staff',
                'assigned_barangay' => $barangay['name'],
            ]));

        $response = $this->actingAs($admin)
            ->get(route('account-management.index'));

        $response->assertOk()->assertSeeText('26 barangay staff accounts');
        $accounts->each(function (User $account) use ($response): void {
            $response->assertSee($account->assigned_barangay);
            $response->assertSee($account->email);
            $response->assertDontSee($account->getRawOriginal('password'));
        });
    }

    public function test_barangay_staff_cannot_list_or_reset_other_accounts(): void
    {
        $staff = $this->barangayUser('Alipit');
        $target = $this->barangayUser('Calios');
        $candidate = Str::random(32);

        $this->actingAs($staff)
            ->get(route('account-management.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('account-management.password.reset', $target), [
                'password' => $candidate,
                'password_confirmation' => $candidate,
            ])
            ->assertForbidden();
    }

    public function test_administrator_reset_hashes_password_requires_change_invalidates_sessions_and_audits(): void
    {
        config()->set('session.driver', 'database');
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $oldPassword = Str::random(32);
        $temporaryPassword = Str::random(32);
        $target = $this->barangayUser('Alipit', $oldPassword);
        $unrelated = $this->barangayUser('Calios');
        $this->insertSession('target-session', $target);
        $this->insertSession('unrelated-session', $unrelated);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])
            ->actingAs($admin)
            ->put(route('account-management.password.reset', $target), [
                'password' => $temporaryPassword,
                'password_confirmation' => $temporaryPassword,
            ]);

        $response->assertRedirect()->assertSessionHas('success');
        $target->refresh();

        $this->assertTrue(Hash::check($temporaryPassword, $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertSame(2, $target->session_version);
        $this->assertNotNull($target->password_changed_at);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-session']);
        $this->assertTrue(
            PasswordHistory::query()
                ->whereBelongsTo($target)
                ->get()
                ->contains(fn (PasswordHistory $history) => Hash::check($oldPassword, $history->password_hash))
        );

        $event = SecurityAuditEvent::query()->sole();
        $this->assertSame(SecurityAuditAction::PasswordReset, $event->action);
        $this->assertSame($admin->id, $event->actor_user_id);
        $this->assertSame($target->id, $event->target_user_id);
        $this->assertSame('203.0.113.44', $event->ip_address);
        $this->assertStringNotContainsString($temporaryPassword, json_encode($event->toArray(), JSON_THROW_ON_ERROR));
        $response->assertDontSee($temporaryPassword);
    }

    public function test_reset_rejects_short_unconfirmed_and_admin_target_passwords(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $target = $this->barangayUser('Alipit');
        $originalHash = $target->getRawOriginal('password');

        $this->actingAs($admin)
            ->put(route('account-management.password.reset', $target), [
                'password' => Str::random(15),
                'password_confirmation' => Str::random(15),
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->put(route('account-management.password.reset', $admin), [
                'password' => Str::random(32),
                'password_confirmation' => Str::random(32),
            ])
            ->assertForbidden();

        $this->assertSame($originalHash, $target->fresh()->getRawOriginal('password'));
        $this->assertFalse($target->fresh()->must_change_password);
        $this->assertDatabaseCount('security_audit_events', 0);
    }

    public function test_temporary_password_login_redirects_immediately_to_required_change(): void
    {
        $temporaryPassword = Str::random(32);
        $staff = $this->barangayUser('Alipit', $temporaryPassword, true);

        $response = $this->post(route('login.post'), [
            'email' => $staff->email,
            'password' => $temporaryPassword,
        ]);

        $response->assertRedirect(route('password.edit'))
            ->assertSessionHas(EnsureSecuritySession::SESSION_VERSION_KEY, 1);
        $this->assertAuthenticatedAs($staff);
    }

    public function test_required_change_blocks_operational_routes_but_allows_change_and_logout(): void
    {
        $staff = $this->barangayUser('Alipit', mustChange: true);

        $this->actingAs($staff)
            ->get(route('barangay.dashboard', 'Alipit'))
            ->assertRedirect(route('password.edit'));

        $this->get(route('barangay.gis.index', 'Alipit'))
            ->assertRedirect(route('password.edit'));

        $this->get(route('barangay.analytics-reports', 'Alipit'))
            ->assertRedirect(route('password.edit'));

        $this->getJson('/api/gis/reports')
            ->assertStatus(423)
            ->assertJsonPath('message', 'A password change is required before continuing.');

        $this->actingAs($staff)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertSee('Required security step')
            ->assertDontSee('Incoming Reports');

        $this->actingAs($staff)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
    }

    public function test_change_rejects_an_incorrect_current_password_without_mutation(): void
    {
        $currentPassword = Str::random(32);
        $staff = $this->barangayUser('Alipit', $currentPassword);
        $originalHash = $staff->getRawOriginal('password');
        $newPassword = Str::random(32);

        $this->actingAs($staff)
            ->put(route('password.update'), [
                'current_password' => Str::random(32),
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalHash, $staff->fresh()->getRawOriginal('password'));
        $this->assertDatabaseCount('password_histories', 0);
        $this->assertDatabaseCount('security_audit_events', 0);
    }

    public function test_successful_required_change_regenerates_session_and_unlocks_access(): void
    {
        $currentPassword = Str::random(32);
        $newPassword = Str::random(32);
        $staff = $this->barangayUser('Alipit', $currentPassword, true);

        $this->actingAs($staff);
        $oldSessionId = session()->getId();

        $response = $this->put(route('password.update'), [
            'current_password' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('barangay.dashboard', 'Alipit'))
            ->assertSessionHas(EnsureSecuritySession::SESSION_VERSION_KEY, 2);
        $staff->refresh();
        $this->assertFalse($staff->must_change_password);
        $this->assertTrue(Hash::check($newPassword, $staff->password));
        $this->assertSame(2, $staff->session_version);
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertSame(SecurityAuditAction::PasswordChanged, SecurityAuditEvent::query()->sole()->action);

        $this->get(route('barangay.dashboard', 'Alipit'))->assertOk();
    }

    public function test_session_manager_deletes_other_database_sessions_and_rotates_current_session(): void
    {
        config()->set('session.driver', 'database');
        $user = $this->barangayUser('Alipit');
        $this->insertSession('first-session', $user);
        $this->insertSession('second-session', $user);

        $request = Request::create('/change-password', 'PUT');
        $store = new Store('phase15c', new ArraySessionHandler(120));
        $store->start();
        $store->put(EnsureSecuritySession::SESSION_VERSION_KEY, 1);
        $request->setLaravelSession($store);
        $oldSessionId = $store->getId();
        $user->forceFill(['session_version' => 2])->save();

        app(SecuritySessionManager::class)->invalidateOthersAndRegenerate($request, $user);

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertNotSame($oldSessionId, $store->getId());
        $this->assertSame(2, $store->get(EnsureSecuritySession::SESSION_VERSION_KEY));
    }

    public function test_stale_session_version_is_logged_out_after_a_reset(): void
    {
        $staff = $this->barangayUser('Alipit');
        $staff->forceFill(['session_version' => 2])->save();

        $this->withSession([EnsureSecuritySession::SESSION_VERSION_KEY => 1])
            ->actingAs($staff)
            ->get(route('password.edit'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    private function barangayUser(
        string $barangay,
        ?string $password = null,
        bool $mustChange = false,
    ): User {
        return User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => $barangay,
            'password' => Hash::make($password ?? Str::random(32)),
            'must_change_password' => $mustChange,
        ]);
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.50',
            'user_agent' => 'CIVICLEAR test client',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }
}

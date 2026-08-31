<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UserSeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_rejects_a_missing_password(): void
    {
        config(['auth.seed_default_password' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SEED_DEFAULT_PASSWORD');

        $this->seed(UserSeeder::class);
    }

    public function test_user_seeder_uses_an_explicit_strong_temporary_password(): void
    {
        $temporaryPassword = 'temporary-only-strong-value';
        config(['auth.seed_default_password' => $temporaryPassword]);

        $this->seed(UserSeeder::class);

        $expectedCount = count(config('santa_cruz_barangays.barangays', [])) + 1;

        $this->assertSame($expectedCount, User::query()->count());
        $this->assertTrue(
            User::query()->get()->every(
                fn (User $user): bool => Hash::check($temporaryPassword, $user->password)
            )
        );
    }
}

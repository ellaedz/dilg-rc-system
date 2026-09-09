<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class SessionExpiryRecoveryTest extends TestCase
{
    public function test_an_expired_login_form_returns_to_login_with_a_clear_message(): void
    {
        app()->detectEnvironment(fn () => 'local');

        try {
            $response = $this
                ->withMiddleware(ValidateCsrfToken::class)
                ->post('/login', [
                    'email' => 'admin@dilg.gov.ph',
                    'password' => 'password',
                ]);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your session expired. Please sign in again.');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Your session expired. Please sign in again.');
    }
}

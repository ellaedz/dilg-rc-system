<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_https_proxy_headers_generate_secure_asset_urls(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Host' => 'civiclear.example',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/login');

        $response
            ->assertOk()
            ->assertSee('https://civiclear.example/build/assets/', false)
            ->assertDontSee('http://civiclear.example/build/assets/', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dilg_navigation_pages_are_available(): void
    {
        $admin = User::factory()->create([
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $urls = [
            '/dilg-dashboard',
            '/violation-reports',
            '/needs-barangay-review',
            '/response-tracking',
            '/barangay-performance',
            '/analytics-reports',
            '/gis-map',
            '/profile',
            '/barangay/Alipit/dashboard',
            '/barangay/Alipit/incoming-reports',
            '/barangay/Alipit/verified-reports',
            '/barangay/Alipit/response-tracking',
            '/barangay/Alipit/analytics-reports',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($admin)->get($url);

            $response
                ->assertOk("DILG navigation URL failed: {$url}")
                ->assertDontSee('/barangay/unknown/');

            if (str_starts_with($url, '/barangay/')) {
                $response
                    ->assertSee('Barangay Overview')
                    ->assertSee('Back to DILG Overview')
                    ->assertSee('/barangay/Alipit/dashboard', false);
            }
        }
    }

    public function test_barangay_navigation_pages_are_available(): void
    {
        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $barangay = rawurlencode($staff->assigned_barangay);
        $urls = [
            "/barangay/{$barangay}/dashboard",
            "/barangay/{$barangay}/incoming-reports",
            "/barangay/{$barangay}/verified-reports",
            "/barangay/{$barangay}/response-tracking",
            "/barangay/{$barangay}/analytics-reports",
            "/barangay/{$barangay}/profile",
        ];

        foreach ($urls as $url) {
            $this->actingAs($staff)->get($url)->assertOk("Barangay navigation URL failed: {$url}");
        }
    }
}

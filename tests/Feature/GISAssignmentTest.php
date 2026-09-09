<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GISAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_detection_api_assigns_the_mpdo_barangay_polygon(): void
    {
        $response = $this->postJson('/api/gis/detect-barangay', [
            'latitude' => 14.2779932,
            'longitude' => 121.4052455,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_inside_santa_cruz', true)
            ->assertJsonPath('data.detected_barangay', 'Calios')
            ->assertJsonPath('data.assigned_barangay_office', 'Barangay Hall - Calios')
            ->assertJsonPath('data.barangay_detection_status', 'auto_detected')
            ->assertJsonPath('data.needs_manual_barangay_review', false);
    }

    public function test_new_report_is_automatically_routed_by_its_gps_polygon(): void
    {
        $response = $this->postJson('/api/mobile/reports', [
            'description' => 'Road obstruction inside the Calios boundary.',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => 14.2779932,
            'longitude' => 121.4052455,
            'timestamp' => now()->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.detected_barangay', 'Calios')
            ->assertJsonPath('data.assigned_barangay_office', 'Barangay Hall - Calios')
            ->assertJsonPath('data.needs_manual_barangay_review', false);

        $this->assertDatabaseHas('violation_reports', [
            'report_number' => $response->json('data.report_number'),
            'detected_barangay' => 'Calios',
            'assigned_barangay_office' => 'Barangay Hall - Calios',
            'needs_manual_barangay_review' => false,
        ]);
    }
}

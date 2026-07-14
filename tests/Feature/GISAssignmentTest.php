<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GISAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_detection_api_separates_municipality_from_barangay(): void
    {
        $response = $this->postJson('/api/gis/detect-barangay', [
            'latitude' => 14.2800,
            'longitude' => 121.4100,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_inside_santa_cruz', true)
            ->assertJsonPath('data.detected_barangay', null)
            ->assertJsonPath('data.barangay_detection_status', 'barangay_boundary_unavailable')
            ->assertJsonPath('data.needs_manual_barangay_review', true);
    }

    public function test_new_report_is_marked_for_manual_barangay_review(): void
    {
        $response = $this->postJson('/api/mobile/reports', [
            'description' => 'Road obstruction requiring barangay review.',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => 14.2800,
            'longitude' => 121.4100,
            'timestamp' => now()->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.detected_barangay', null)
            ->assertJsonPath('data.needs_manual_barangay_review', true);

        $this->assertDatabaseHas('violation_reports', [
            'report_id' => $response->json('data.tracking_id'),
            'detected_barangay' => null,
            'needs_manual_barangay_review' => true,
        ]);
    }
}

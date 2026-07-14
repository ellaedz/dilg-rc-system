<?php

namespace Tests\Unit;

use App\Services\BarangayOfficeService;
use Tests\TestCase;

class BarangayOfficeServiceTest extends TestCase
{
    public function test_haversine_distance_is_calculated_in_kilometers(): void
    {
        $service = new BarangayOfficeService();

        $distance = $service->calculateDistanceKm(14.2800, 121.4100, 14.2800, 121.4200);

        $this->assertEqualsWithDelta(1.078, $distance, 0.02);
    }

    public function test_unvalidated_nearest_office_is_marked_provisional(): void
    {
        config()->set('santa_cruz_barangay_halls', [[
            'barangay' => 'Bagumbayan',
            'office_name' => 'Barangay Hall - Bagumbayan',
            'latitude' => 14.2801,
            'longitude' => 121.4101,
            'source' => 'config centroid fallback',
            'validation_status' => 'Needs manual validation',
        ]]);

        $office = (new BarangayOfficeService())->findNearestValidatedOffice(14.2800, 121.4100);

        $this->assertSame('Bagumbayan', $office['barangay']);
        $this->assertSame('provisional', $office['recommendation_status']);
        $this->assertStringContainsString('requires validation', $office['recommendation_notice']);
    }
}

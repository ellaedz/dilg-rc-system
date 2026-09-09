<?php

namespace Tests\Unit;

use App\Services\BarangayAssignmentService;
use App\Services\BarangayOfficeService;
use Tests\TestCase;

class BarangayOfficeServiceTest extends TestCase
{
    public function test_all_final_barangay_hall_points_match_their_mpdo_polygons(): void
    {
        $offices = config('santa_cruz_barangay_halls', []);

        $this->assertCount(26, $offices);
        $this->assertCount(26, array_unique(array_column($offices, 'barangay')));
        $this->assertCount(26, array_unique(array_column($offices, 'psgc')));

        foreach ($offices as $office) {
            $this->assertMatchesRegularExpression('/^043426\d{3}$/', $office['psgc']);
            $this->assertSame('Verified', $office['validation_status']);
            $this->assertSame('MPDO polygon verified', $office['boundary_validation']);

            $detected = BarangayAssignmentService::detectBarangay(
                (float) $office['latitude'],
                (float) $office['longitude'],
            );

            $this->assertSame('auto_detected', $detected['barangay_detection_status'], $office['barangay']);
            $this->assertSame($office['barangay'], $detected['detected_barangay'], $office['barangay']);
        }
    }

    public function test_public_barangay_hall_geojson_matches_the_validated_config(): void
    {
        $offices = collect(config('santa_cruz_barangay_halls', []))->keyBy('barangay');
        $geoJson = json_decode(
            (string) file_get_contents(public_path('gis/barangay_halls.geojson')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('FeatureCollection', $geoJson['type']);
        $this->assertCount(26, $geoJson['features']);

        foreach ($geoJson['features'] as $feature) {
            $barangay = $feature['properties']['barangay'];
            $office = $offices->get($barangay);

            $this->assertNotNull($office, $barangay);
            $this->assertSame('Point', $feature['geometry']['type']);
            $this->assertEqualsWithDelta((float) $office['longitude'], (float) $feature['geometry']['coordinates'][0], 1.0e-10, $barangay);
            $this->assertEqualsWithDelta((float) $office['latitude'], (float) $feature['geometry']['coordinates'][1], 1.0e-10, $barangay);
        }
    }

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

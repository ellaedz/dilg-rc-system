<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BarangayAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13AMPDOBarangayPolygonTest extends TestCase
{
    use RefreshDatabase;

    public function test_gis_page_loads_the_mpdo_boundary_layer(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->actingAs($admin)
            ->get(route('gis.index'))
            ->assertOk()
            ->assertSee('26 MPDO barangay boundaries')
            ->assertSee('santa_cruz_barangays.geojson')
            ->assertSee('santa_cruz_municipality.geojson');
    }

    public function test_mpdo_layer_contains_every_configured_barangay_once(): void
    {
        $geoJson = $this->readGeoJson(public_path('gis/santa_cruz_barangays.geojson'));
        $features = $geoJson['features'];

        $actualNames = array_map(fn (array $feature) => $feature['properties']['name'], $features);
        $expectedNames = array_column(config('santa_cruz_barangays.barangays'), 'name');
        sort($actualNames);
        sort($expectedNames);

        $this->assertCount(26, $features);
        $this->assertSame($expectedNames, $actualNames);
        $this->assertCount(26, array_unique($actualNames));
        $this->assertCount(26, array_unique(array_column(array_column($features, 'properties'), 'PSGC')));

        foreach ($features as $feature) {
            $this->assertSame('MultiPolygon', $feature['geometry']['type']);
            $this->assertMatchesRegularExpression('/^\d{9}$/', $feature['properties']['PSGC']);
            $this->assertGreaterThan(0, $feature['properties']['area']);
            $this->assertNotEmpty($feature['geometry']['coordinates']);
        }
    }

    public function test_each_source_derived_acceptance_point_resolves_to_its_barangay(): void
    {
        $points = $this->readGeoJson(base_path('tests/Fixtures/mpdo_barangay_acceptance_points.geojson'));

        $this->assertCount(26, $points['features']);

        foreach ($points['features'] as $feature) {
            [$longitude, $latitude] = $feature['geometry']['coordinates'];
            $expected = $feature['properties']['name'];
            $result = BarangayAssignmentService::assignReportLocation($latitude, $longitude);

            $this->assertTrue($result['municipality_validated'], $expected.' should be inside Santa Cruz.');
            $this->assertSame($expected, $result['detected_barangay']);
            $this->assertSame('auto_detected', $result['barangay_detection_status']);
            $this->assertFalse($result['needs_manual_barangay_review']);
            $this->assertSame('Barangay Hall - '.$expected, $result['assigned_barangay_office']);
        }
    }

    public function test_a_shared_barangay_boundary_is_sent_for_manual_review(): void
    {
        $result = BarangayAssignmentService::assignReportLocation(14.231875, 121.3875028);

        $this->assertTrue($result['municipality_validated']);
        $this->assertNull($result['detected_barangay']);
        $this->assertSame('barangay_boundary_ambiguous', $result['barangay_detection_status']);
        $this->assertTrue($result['needs_manual_barangay_review']);
    }

    private function readGeoJson(string $path): array
    {
        $this->assertFileExists($path);
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('FeatureCollection', $decoded['type'] ?? null);

        return $decoded;
    }
}

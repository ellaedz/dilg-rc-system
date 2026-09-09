<?php

namespace Tests\Unit;

use App\Services\BarangayAssignmentService;
use Tests\TestCase;

class BarangayAssignmentServiceTest extends TestCase
{
    public function test_gps_outside_santa_cruz_is_not_assigned_to_a_barangay(): void
    {
        $result = BarangayAssignmentService::assignReportLocation(15.0000, 121.0000);

        $this->assertFalse($result['is_inside_santa_cruz']);
        $this->assertNull($result['detected_barangay']);
        $this->assertSame('outside_coverage', $result['barangay_detection_status']);
        $this->assertTrue($result['needs_manual_barangay_review']);
    }

    public function test_inside_municipality_is_automatically_assigned_from_mpdo_polygons(): void
    {
        $result = BarangayAssignmentService::assignReportLocation(14.2779932, 121.4052455);

        $this->assertTrue($result['is_inside_santa_cruz']);
        $this->assertSame('Santa Cruz', $result['municipality_name']);
        $this->assertSame('Calios', $result['detected_barangay']);
        $this->assertSame('Barangay Hall - Calios', $result['assigned_barangay_office']);
        $this->assertSame('auto_detected', $result['barangay_detection_status']);
        $this->assertFalse($result['needs_manual_barangay_review']);
    }

    public function test_municipal_feature_is_never_returned_as_a_barangay(): void
    {
        $result = BarangayAssignmentService::assignReportLocation(14.2779932, 121.4052455);

        $this->assertNotSame('Santa Cruz (Capital)', $result['detected_barangay']);
        $this->assertNotSame('Santa Cruz', $result['detected_barangay']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsExportAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_dilg_admin_can_download_csv_and_pdf_exports(): void
    {
        $admin = User::factory()->create([
            'name' => 'DILG Administrator',
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $this->report('RCV-2026-9101', 'Alipit');
        $this->report('RCV-2026-9102', 'Calios');

        $csv = $this->actingAs($admin)->get('/analytics-reports/export?format=csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', (string) $csv->headers->get('content-disposition'));
        $this->assertStringContainsString('RCV-2026-9101', $csv->streamedContent());
        $this->assertStringContainsString('RCV-2026-9102', $csv->streamedContent());

        $pdf = $this->actingAs($admin)->get('/analytics-reports/export?format=pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_barangay_exports_are_limited_to_the_assigned_barangay(): void
    {
        $staff = User::factory()->create([
            'name' => 'Barangay Staff - Alipit',
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $this->report('RCV-2026-9201', 'Alipit');
        $this->report('RCV-2026-9202', 'Calios');

        $csv = $this->actingAs($staff)->get('/barangay/Alipit/analytics-reports/export?format=csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $csv->streamedContent();
        $this->assertStringContainsString('RCV-2026-9201', $content);
        $this->assertStringNotContainsString('RCV-2026-9202', $content);

        $pdf = $this->actingAs($staff)->get('/barangay/Alipit/analytics-reports/export?format=pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $this->actingAs($staff)
            ->get('/barangay/Calios/analytics-reports/export?format=csv')
            ->assertForbidden();
    }

    public function test_every_configured_account_type_can_open_its_own_profile(): void
    {
        $admin = User::factory()->create([
            'name' => 'DILG Administrator',
            'email' => 'admin-profile@example.test',
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('DILG Administrator')
            ->assertSee('admin-profile@example.test')
            ->assertSee('Municipality-wide monitoring');

        foreach (config('santa_cruz_barangays.barangays', []) as $index => $barangayData) {
            $barangay = $barangayData['name'];
            $staff = User::factory()->create([
                'name' => 'Barangay Staff - '.$barangay,
                'email' => "profile-{$index}@example.test",
                'role' => 'barangay_staff',
                'assigned_barangay' => $barangay,
            ]);

            $this->actingAs($staff)
                ->get('/barangay/'.rawurlencode($barangay).'/profile')
                ->assertOk()
                ->assertSee('Barangay Staff - '.$barangay)
                ->assertSee("profile-{$index}@example.test")
                ->assertSee('Assigned barangay records');
        }
    }

    private function report(string $reportNumber, string $barangay): ViolationReport
    {
        return ViolationReport::create([
            'report_id' => $reportNumber,
            'report_number' => $reportNumber,
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'A parked vehicle is blocking the road.',
            'selected_violation_type' => 'Illegal Parking',
            'final_ai_prediction' => 'Illegal Parking',
            'final_ai_confidence' => 0.88,
            'ai_image_confidence' => 0.85,
            'text_confidence' => 0.91,
            'citizen_reported_barangay' => $barangay,
            'detected_barangay' => $barangay,
            'assigned_barangay_office' => 'Barangay Hall - '.$barangay,
            'latitude' => 14.28,
            'longitude' => 121.41,
            'timestamp' => now(),
            'status' => 'Submitted',
            'report_status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => now()->toDateString(),
        ]);
    }
}

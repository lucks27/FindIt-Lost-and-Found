<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ClaimReview;
use App\Models\ItemRelease;
use App\Models\Report;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_analytics_endpoints_return_expected_contract(): void
    {
        $admin = Admin::create([
            'name' => 'Analytics Admin',
            'email' => 'analytics@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $student = Student::create([
            'student_id_number' => '2026-0101',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'tester@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $report = Report::create([
            'student_id' => $student->id,
            'type' => 'found',
            'title' => 'Found Laptop',
            'description' => 'Black laptop near library',
            'location' => 'Library',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'verified',
        ]);

        ClaimReview::create([
            'report_id' => $report->id,
            'claimant_student_id' => $student->id,
            'review_status' => 'pending',
            'notes' => 'Requesting item',
        ]);

        ItemRelease::create([
            'report_id' => $report->id,
            'claimant_student_id' => $student->id,
            'approved_by_admin_id' => $admin->id,
            'release_code' => 'release-123',
            'status' => 'pending',
            'checklist' => [
                'claim_approved' => true,
                'claimant_identity_checked' => true,
                'ownership_proof_confirmed' => true,
                'physical_item_condition_checked' => true,
                'claimant_acknowledgment_completed' => true,
            ],
            'scheduled_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total_reports', 1)
            ->assertJsonPath('data.summary.open_reports', 0);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/claims')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/releases')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

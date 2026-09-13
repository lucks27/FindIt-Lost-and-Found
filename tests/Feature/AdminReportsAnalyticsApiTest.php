<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ClaimReview;
use App\Models\ItemVerification;
use App\Models\ItemRelease;
use App\Models\PotentialMatch;
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

        $pendingFoundReport = Report::create([
            'student_id' => $student->id,
            'type' => 'found',
            'title' => 'Found Keys',
            'description' => 'Keys found near the gate',
            'location' => 'Main gate',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'pending_surrender',
        ]);

        ItemVerification::create([
            'report_id' => $pendingFoundReport->id,
            'status' => 'pending',
        ]);

        $lostReport = Report::create([
            'student_id' => $student->id,
            'type' => 'lost',
            'title' => 'Lost Keys',
            'description' => 'Keys lost near the gate',
            'location' => 'Main gate',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'published',
        ]);

        $potentialMatch = PotentialMatch::create([
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $pendingFoundReport->id,
            'status' => 'pending',
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
            ->assertJsonPath('data.summary.total_reports', 3)
            ->assertJsonPath('data.summary.open_reports', 1)
            ->assertJsonPath('data.summary.pending_surrenders', 1)
            ->assertJsonPath('data.summary.pending_verifications', 1)
            ->assertJsonPath('data.summary.potential_matches', 1);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/claims')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/releases')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/matches/{$potentialMatch->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('archive_items', [
            'report_id' => $pendingFoundReport->id,
            'archive_type' => 'rejected',
            'reason' => 'Potential match rejected by admin review.',
        ]);
    }
}

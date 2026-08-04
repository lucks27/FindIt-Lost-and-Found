<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ClaimReview;
use App\Models\ItemRelease;
use App\Models\ItemVerification;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Report;
use App\Models\Student;
use App\Models\SurrenderRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReturnWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_process_full_return_workflow(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $finder = Student::create([
            'student_id' => '2026-0001',
            'name' => 'Finder Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'finder@example.com',
            'password' => 'secret123',
        ]);

        $owner = Student::create([
            'student_id' => '2026-0002',
            'name' => 'Owner Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $report = Report::create([
            'student_id' => $finder->id,
            'report_type' => 'found',
            'title' => 'Found ID Card',
            'description' => 'Blue student ID card found near library',
            'category' => 'Personal Items',
            'location' => 'Library',
            'status' => 'pending_surrender',
        ]);

        SurrenderRequest::create([
            'report_id' => $report->id,
            'student_id' => $finder->id,
            'status' => 'pending',
            'deadline_at' => now()->addDays(3),
        ]);

        $receiveResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/surrenders/' . $report->surrenderRequest->id . '/receive');

        $receiveResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $verification = ItemVerification::where('report_id', $report->id)->firstOrFail();

        $verifyResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/verifications/' . $verification->id . '/verify', [
                'notes' => 'Item matches the description and is safe to store.',
            ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $claim = ClaimReview::create([
            'report_id' => $report->id,
            'claimant_student_id' => $owner->id,
            'review_status' => 'pending',
            'notes' => 'Owner wants to claim the item.',
        ]);

        $approveClaimResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/claims/' . $claim->id . '/approve');

        $approveClaimResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $release = ItemRelease::where('report_id', $report->id)->firstOrFail();
        $release->update([
            'status' => 'pending',
            'checklist' => [
                'claim_approved' => true,
                'claimant_identity_checked' => true,
                'ownership_proof_confirmed' => true,
                'physical_item_condition_checked' => true,
                'claimant_acknowledgment_completed' => true,
            ],
        ]);

        $completeReleaseResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/releases/' . $release->id . '/complete');

        $completeReleaseResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'completed_return',
        ]);

        $this->assertDatabaseHas('item_releases', [
            'id' => $release->id,
            'status' => 'released',
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'student_id' => $finder->id,
            'type' => 'return_completion',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $finder->id,
            'recipient_role' => 'student',
            'action' => 'return_completed',
        ]);
    }
}

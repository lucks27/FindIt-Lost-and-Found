<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ClaimReview;
use App\Models\ItemCategory;
use App\Models\ItemRelease;
use App\Models\ItemVerification;
use App\Models\Notification;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\Report;
use App\Models\Student;
use App\Models\SurrenderRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReturnWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_found_item_rewards_half_on_publication_and_remaining_half_on_return(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super-found-reward@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $category = ItemCategory::create([
            'name' => 'Phone',
            'slug' => 'phone-half-reward',
            'description' => 'Phone category for reward test',
            'is_active' => true,
        ]);

        PointRule::create([
            'category_id' => $category->id,
            'base_points' => 10,
            'is_active' => true,
        ]);

        $finder = Student::create([
            'student_id_number' => '2026-0101',
            'first_name' => 'Finder',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'finder-half@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $owner = Student::create([
            'student_id_number' => '2026-0102',
            'first_name' => 'Owner',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'owner-half@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $report = Report::create([
            'student_id' => $finder->id,
            'category_id' => $category->id,
            'category' => $category->name,
            'type' => 'found',
            'title' => 'Found Phone',
            'description' => 'Blue phone found near library',
            'location' => 'Library',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'pending_surrender',
            'published_at' => now(),
        ]);

        SurrenderRequest::create([
            'report_id' => $report->id,
            'student_id' => $finder->id,
            'status' => 'pending',
            'deadline_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/surrenders/' . $report->surrenderRequest->id . '/receive')
            ->assertOk();

        $publishedTransaction = PointTransaction::where('student_id', $finder->id)
            ->where('type', 'found_item_published')
            ->where('reference_type', SurrenderRequest::class)
            ->firstOrFail();

        $this->assertSame(5, (int) $publishedTransaction->points);

        $verification = ItemVerification::where('report_id', $report->id)->firstOrFail();
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/verifications/' . $verification->id . '/verify', [
                'notes' => 'Item matches the description and is safe to store.',
            ]);

        $claim = ClaimReview::create([
            'report_id' => $report->id,
            'claimant_student_id' => $owner->id,
            'review_status' => 'pending',
            'notes' => 'Owner wants to claim the item.',
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/claims/' . $claim->id . '/approve')
            ->assertOk();

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

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/releases/' . $release->id . '/complete')
            ->assertOk();

        $returnTransaction = PointTransaction::where('student_id', $finder->id)
            ->where('type', 'return_completion')
            ->where('reference_type', ItemRelease::class)
            ->firstOrFail();

        $this->assertSame(5, (int) $returnTransaction->points);
        $this->assertSame(10, (int) PointTransaction::where('student_id', $finder->id)->sum('points'));
    }

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
            'student_id_number' => '2026-0001',
            'first_name' => 'Finder',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'finder@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $owner = Student::create([
            'student_id_number' => '2026-0002',
            'first_name' => 'Owner',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $report = Report::create([
            'student_id' => $finder->id,
            'type' => 'found',
            'title' => 'Found ID Card',
            'description' => 'Blue student ID card found near library',
            'location' => 'Library',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'pending_surrender',
            'published_at' => now(),
        ]);

        SurrenderRequest::create([
            'report_id' => $report->id,
            'student_id' => $finder->id,
            'status' => 'pending',
            'deadline_at' => now()->addDays(3),
        ]);

        $report->refresh();

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

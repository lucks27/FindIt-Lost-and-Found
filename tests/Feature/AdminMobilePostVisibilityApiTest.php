<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ArchiveItem;
use App\Models\Report;
use App\Models\Student;
use App\Models\VisiblePostedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMobilePostVisibilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_mobile_visibility_and_archive_a_post(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $student = Student::create([
            'student_id_number' => '2026-0001',
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $report = Report::create([
            'student_id' => $student->id,
            'type' => 'lost',
            'title' => 'Lost Wallet',
            'description' => 'Brown leather wallet',
            'location' => 'Library',
            'date_occurred' => now()->subDay()->toDateString(),
            'status' => 'approved',
            'published_at' => now(),
        ]);

        VisiblePostedItem::create([
            'report_id' => $report->id,
            'post_type' => 'public',
            'is_visible' => true,
            'posted_at' => now(),
            'posted_by_admin_id' => $admin->id,
        ]);

        $toggleResponse = $this->actingAs($admin, 'admin')
            ->patchJson('/api/admin/mobile-posts/' . $report->id . '/visibility', [
                'is_visible' => false,
            ]);

        $toggleResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('visible_posted_items', [
            'report_id' => $report->id,
            'is_visible' => false,
        ]);

        $archiveResponse = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-posts/' . $report->id . '/archive');

        $archiveResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('archive_items', [
            'report_id' => $report->id,
            'archive_type' => 'mobile_archive',
        ]);

        $this->assertDatabaseMissing('visible_posted_items', [
            'report_id' => $report->id,
        ]);
    }
}

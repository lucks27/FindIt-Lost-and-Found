<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ArchiveItem;
use App\Models\Report;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArchiveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_listing_includes_reporter_information(): void
    {
        $admin = Admin::create([
            'name' => 'Archive Admin',
            'email' => 'archive@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $student = Student::create([
            'student_id_number' => '2026-0042',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'course' => 'Computer Science',
            'department' => 'Engineering',
            'year_level' => '3rd Year',
            'profile_photo' => 'uploads/students/alex.jpg',
            'email' => 'alex@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $report = Report::create([
            'student_id' => $student->id,
            'type' => 'found',
            'title' => 'Blue Backpack',
            'description' => 'Found near the library.',
            'date_occurred' => now()->subDay()->toDateString(),
            'location' => 'Library entrance',
            'status' => 'rejected',
            'archived_at' => now(),
        ]);

        ArchiveItem::create([
            'report_id' => $report->id,
            'archive_type' => 'rejected',
            'reason' => 'Duplicate report',
            'archived_by_admin_id' => $admin->id,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/archive');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.student.full_name', 'Alex Rivera')
            ->assertJsonPath('data.0.student.student_id_number', '2026-0042')
            ->assertJsonPath('data.0.student.course', 'Computer Science')
            ->assertJsonPath('data.0.student.department', 'Engineering')
            ->assertJsonPath('data.0.student.year_level', '3rd Year')
            ->assertJsonPath('data.0.student.profile_photo', 'uploads/students/alex.jpg');
    }
}

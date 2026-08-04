<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\SurrenderRequest;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_lost_and_found_reports_with_workflow_statuses(): void
    {
        $student = Student::create([
            'student_number' => '2026-0001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '1',
            'department' => 'Engineering',
            'email' => 'student@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $lostResponse = $this->actingAs($student, 'sanctum')
            ->postJson('/api/reports', [
                'type' => 'lost',
                'title' => 'Lost Notebook',
                'description' => 'Blue notebook with stickers',
                'category' => 'Stationery',
                'location' => 'Library',
            ]);

        $lostResponse->assertCreated()
            ->assertJsonPath('data.status', 'pending_approval');

        $foundResponse = $this->actingAs($student, 'sanctum')
            ->postJson('/api/reports', [
                'type' => 'found',
                'title' => 'Found Calculator',
                'description' => 'Black scientific calculator',
                'category' => 'Electronics',
                'location' => 'Auditorium',
            ]);

        $foundResponse->assertCreated()
            ->assertJsonPath('data.status', 'pending_surrender');

        $this->assertDatabaseHas('reports', [
            'id' => $lostResponse->json('data.id'),
            'report_type' => 'lost',
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('reports', [
            'id' => $foundResponse->json('data.id'),
            'report_type' => 'found',
            'status' => 'pending_surrender',
        ]);

        $this->assertDatabaseHas('surrender_requests', [
            'report_id' => $foundResponse->json('data.id'),
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_student_can_submit_potential_match_for_approved_and_verified_reports(): void
    {
        $owner = Student::create([
            'student_number' => '2026-0002',
            'first_name' => 'Owner',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $finder = Student::create([
            'student_number' => '2026-0003',
            'first_name' => 'Finder',
            'last_name' => 'Student',
            'course' => 'Computer Science',
            'year_level' => '2',
            'department' => 'Engineering',
            'email' => 'finder@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $lostReport = Report::create([
            'student_id' => $owner->id,
            'report_type' => 'lost',
            'title' => 'Lost Card',
            'description' => 'Yellow ID card',
            'category' => 'Personal Items',
            'location' => 'Cafeteria',
            'status' => 'approved',
        ]);

        $foundReport = Report::create([
            'student_id' => $finder->id,
            'report_type' => 'found',
            'title' => 'Found Card',
            'description' => 'Yellow ID card found near cafeteria',
            'category' => 'Personal Items',
            'location' => 'Cafeteria',
            'status' => 'verified',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/reports/{$lostReport->id}/matches/{$foundReport->id}/confirm");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('potential_matches', [
            'source_report_id' => $lostReport->id,
            'matched_report_id' => $foundReport->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $finder->id,
            'recipient_role' => 'student',
            'action' => 'potential_match_submitted',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $owner->id,
            'recipient_role' => 'student',
            'action' => 'potential_match_submitted',
        ]);
    }
}

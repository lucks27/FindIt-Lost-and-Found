<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\EventFine;
use App\Models\PointTransaction;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_super_admin_account_can_exist(): void
    {
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $this->expectException(\RuntimeException::class);

        Admin::create([
            'name' => 'Second Super Admin',
            'email' => 'second-super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);
    }

    public function test_super_admin_can_fetch_audit_logs(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => 'logged_in',
            'subject_type' => 'admin',
            'subject_id' => $admin->id,
            'description' => 'Super Admin logged in',
            'old_values' => null,
            'new_values' => ['module' => 'auth'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/audit-logs');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_login_persists_session_for_protected_requests(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $loginResponse = $this->postJson('/api/admin/login/request', [
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->getJson('/api/admin/dashboard')->assertOk();
    }

    public function test_admin_points_endpoint_returns_student_academic_details_from_students_table(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        Student::create([
            'student_id_number' => '2026-1001',
            'first_name' => 'Bryll',
            'middle_name' => 'Benny',
            'last_name' => 'Rose',
            'email' => 'bryll@example.com',
            'password' => 'secret123',
            'course' => 'Bachelor of Science in Information Technology (BSIT)',
            'department' => 'College of Computing and Information Sciences (CCIS)',
            'year_level' => '3rd Year',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/points');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.course', 'Bachelor of Science in Information Technology (BSIT)')
            ->assertJsonPath('data.0.department', 'College of Computing and Information Sciences (CCIS)')
            ->assertJsonPath('data.0.year_level', '3rd Year');
    }

    public function test_admin_event_fines_are_grouped_by_student_balance(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $student = Student::create([
            'student_id_number' => '2026-2001',
            'first_name' => 'Ari',
            'last_name' => 'Dela Cruz',
            'email' => 'ari@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        EventFine::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'school_id' => $student->student_id_number,
            'event_name' => 'Late attendance',
            'amount' => 120.00,
            'original_amount' => 120.00,
            'amount_to_pay' => 120.00,
            'paid_amount' => 0,
            'paid' => 0,
            'remaining_amount' => 120.00,
            'status' => 'pending',
            'reason' => 'Late attendance',
            'issued_by_admin_id' => $admin->id,
        ]);

        EventFine::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'school_id' => $student->student_id_number,
            'event_name' => 'Disruption',
            'amount' => 80.00,
            'original_amount' => 80.00,
            'amount_to_pay' => 80.00,
            'paid_amount' => 0,
            'paid' => 0,
            'remaining_amount' => 80.00,
            'status' => 'pending',
            'reason' => 'Disruption',
            'issued_by_admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/event-fines');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_id', $student->id)
            ->assertJsonPath('data.0.original_amount', 200)
            ->assertJsonPath('data.0.remaining_amount', 200);
    }

    public function test_event_fine_conversion_can_be_created_without_event_fine_id(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        $student = Student::create([
            'student_id_number' => '2026-3001',
            'first_name' => 'Lia',
            'last_name' => 'Santos',
            'email' => 'lia@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        EventFine::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'school_id' => $student->student_id_number,
            'event_name' => 'Misconduct',
            'amount' => 150.00,
            'original_amount' => 150.00,
            'amount_to_pay' => 150.00,
            'paid_amount' => 0,
            'paid' => 0,
            'remaining_amount' => 150.00,
            'status' => 'pending',
            'reason' => 'Misconduct',
            'issued_by_admin_id' => $admin->id,
        ]);

        PointTransaction::create([
            'student_id' => $student->id,
            'type' => 'report_reward',
            'direction' => 'credit',
            'points' => 1000,
            'amount' => 10.00,
            'description' => 'Available points for fine conversion.',
            'notes' => 'Available points for conversion.',
            'processed_by_admin_id' => $admin->id,
            'created_by_admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson('/api/admin/event-fine-conversions', [
            'student_id' => $student->id,
            'requested_points' => 100,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.student_id', $student->id)
            ->assertJsonPath('data.total_original_amount', '150.00')
            ->assertJsonPath('data.converted_points', 100)
            ->assertJsonPath('data.peso_value_converted', '1.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('event_fine_conversions', [
            'student_id' => $student->id,
            'converted_points' => 100,
            'status' => 'pending',
        ]);
    }
}

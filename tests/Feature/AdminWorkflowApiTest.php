<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
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
}

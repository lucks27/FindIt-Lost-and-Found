<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
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
}

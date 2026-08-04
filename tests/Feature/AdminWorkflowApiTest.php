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
            'actor_admin_id' => $admin->id,
            'action' => 'logged_in',
            'subject_type' => 'admin',
            'subject_id' => $admin->id,
            'subject_label' => 'Super Admin',
            'details' => ['module' => 'auth'],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/audit-logs');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEmailDomainValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_accepts_ssct_email_domain(): void
    {
        $response = $this->postJson('/api/register', [
            'student_id' => '2026-1234',
            'name' => 'Ana Santos',
            'course' => 'BS Information Technology',
            'email' => 'ana.santos@ssct.edu.ph',
            'year_level' => '2nd Year',
            'department' => 'College of Computing and Information Sciences',
            'password' => 'StrongPass!1',
            'password_confirmation' => 'StrongPass!1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('students', [
            'email' => 'ana.santos@ssct.edu.ph',
        ]);
    }

    public function test_student_registration_rejects_snsu_email_domain(): void
    {
        $response = $this->postJson('/api/register', [
            'student_id' => '2026-5678',
            'name' => 'Liam Cruz',
            'course' => 'BS Information Technology',
            'email' => 'liam.cruz@snsu.edu.ph',
            'year_level' => '1st Year',
            'department' => 'College of Computing and Information Sciences',
            'password' => 'StrongPass!1',
            'password_confirmation' => 'StrongPass!1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_student_registration_requires_strong_password_policy(): void
    {
        $response = $this->postJson('/api/register', [
            'student_id' => '2026-9999',
            'name' => 'Ella Dela Cruz',
            'course' => 'BS Information Technology',
            'email' => 'ella.dela.cruz@ssct.edu.ph',
            'year_level' => '3rd Year',
            'department' => 'College of Computing and Information Sciences',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .');
    }
}

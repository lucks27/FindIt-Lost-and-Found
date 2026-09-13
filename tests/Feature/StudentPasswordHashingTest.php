<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPasswordHashingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_password_is_hashed_when_created(): void
    {
        $student = Student::create([
            'student_id_number' => '2026-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@ssct.edu.ph',
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'department' => 'IT',
            'password' => 'student123',
            'status' => 'active',
        ]);

        $this->assertNotSame('student123', $student->password);
        $this->assertTrue(Hash::check('student123', $student->password));
    }
}

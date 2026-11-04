<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'student_id_number' => '2026-0001',
                'first_name' => 'John',
                'middle_name' => null,
                'last_name' => 'Doe',
                'email' => 'stratus@ssct.edu.ph',
                'course' => 'BSIT',
                'department' => 'IT',
                'year_level' => '3rd Year',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ],
            [
                'student_id_number' => '2026-0002',
                'first_name' => 'Jane',
                'middle_name' => null,
                'last_name' => 'Smith',
                'email' => 'airx@ssct.edu.ph',
                'course' => 'BSIT',
                'department' => 'IT',
                'year_level' => '2nd Year',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($students as $student) {
            Student::updateOrCreate(
                [
                    'student_id_number' => $student['student_id_number'],
                ],
                [
                    'first_name' => $student['first_name'],
                    'middle_name' => $student['middle_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'course' => $student['course'],
                    'department' => $student['department'],
                    'year_level' => $student['year_level'],
                    'status' => $student['status'],
                    'password' => $student['password'],
                ]
            );
        }
    }
}

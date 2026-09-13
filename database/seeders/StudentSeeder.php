<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $students = [
            [
                'student_id' => '2026-0001',
                'name' => 'John Doe',
                'email' => 'stratus@ssct.edu.ph',
                'course' => 'BSIT',
                'year_level' => '3rd Year',
                'department' => 'IT',
                'password' => Hash::make('password123'),
            ],
            [
                'student_id' => '2026-0002',
                'name' => 'Jane Smith',
                'email' => 'airx@ssct.edu.ph',
                'course' => 'BSIT',
                'year_level' => '2nd Year',
                'department' => 'IT',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($students as $s) {
            Student::create($s);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('bookings')->insert([
            [
                'student_id' => 1,
                'facility_id' => 1,
                'year_section' => 'BS-CS-3A',
                'purpose' => 'Team Meeting',
                'date' => '2025-12-12',
                'time_in' => '09:00',
                'time_out' => '12:00',
                'status' => 'PENDING',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 2,
                'facility_id' => 2,
                'year_section' => 'BS-IT-2B',
                'purpose' => 'Sports Training',
                'date' => '2026-09-02',
                'time_in' => '13:00',
                'time_out' => '15:00',
                'status' => 'CANCELLED',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

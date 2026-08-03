<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notifications')->insert([
            [
                'student_id' => 1,
                'recipient_id' => 1,
                'booking_id' => 1,
                'title' => 'Booking Reserved',
                'message' => 'Your conference room booking has been reserved for 2025-12-12.',
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 2,
                'recipient_id' => 1,
                'booking_id' => 2,
                'title' => 'Booking Cancelled',
                'message' => 'Your gym booking for 2026-09-02 has been cancelled.',
                'is_read' => false,
                'created_at' => now()->subWeek(),
                'updated_at' => now()->subWeek(),
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('facilities')->insert([
            [
                'administrator_name' => 'Admin One',
                'facility_name' => 'Conference Room',
                'type' => 'Room',
                'location' => 'Building A',
                'description' => 'Professional conference room with AV equipment',
                'time_open' => '08:00',
                'time_close' => '17:00',
                'status' => 'Available',
                'image' => 'conference-room.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'administrator_name' => 'Admin Two',
                'facility_name' => 'Gymnasium',
                'type' => 'Sports',
                'location' => 'Building B',
                'description' => 'Fully equipped gym with multiple sports facilities',
                'time_open' => '06:00',
                'time_close' => '21:00',
                'status' => 'Available',
                'image' => 'gym.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'administrator_name' => 'Admin Three',
                'facility_name' => 'Learning Resource Center',
                'type' => 'Academic',
                'location' => 'Main Library',
                'description' => 'Study and research facilities with modern technology',
                'time_open' => '07:00',
                'time_close' => '22:00',
                'status' => 'Available',
                'image' => 'lrc.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'administrator_name' => 'Admin Four',
                'facility_name' => 'Academic Hall',
                'type' => 'Academic',
                'location' => 'Building C',
                'description' => 'Lecture halls and classrooms for academic activities',
                'time_open' => '08:00',
                'time_close' => '18:00',
                'status' => 'Available',
                'image' => 'acadhall.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

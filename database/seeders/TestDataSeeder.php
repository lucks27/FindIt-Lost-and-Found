<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Facility;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create a test student
        $student = Student::firstOrCreate([
            'student_id' => '2024-00157'
        ], [
            'name' => 'Test Student',
            'email' => 'test@student.com',
            'password' => Hash::make('password123'),
            'course' => 'BS Information Technology',
            'year_level' => '2nd Year',
            'department' => 'IT Department',
        ]);

        echo "Test student created: " . $student->student_id . "\n";

        // Create a test facility if it doesn't exist
        $facility = Facility::firstOrCreate([
            'facility_name' => 'Conference Room A'
        ], [
            'administrator_name' => 'Admin User',
            'type' => 'Meeting Room',
            'location' => 'Building 1, 2nd Floor',
            'time_open' => '08:00',
            'status' => 'Available'
        ]);

        echo "Test facility created: " . $facility->facility_name . "\n";

        // Create a test booking
        $booking = Booking::firstOrCreate([
            'student_id' => $student->id,
            'facility_id' => $facility->id,
            'date' => Carbon::today()->addDays(1)->format('Y-m-d'),
        ], [
            'time_in' => '10:00:00',
            'time_out' => '12:00:00',
            'year_section' => '2nd Year - IT Department',
            'purpose' => 'Team Meeting',
            'status' => 'PENDING',
        ]);

        echo "Test booking created with ID: " . $booking->id . "\n";
        echo "Test data seeding complete!\n";
    }
}

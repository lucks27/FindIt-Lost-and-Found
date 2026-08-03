<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()
            ->where('role', 'super_admin')
            ->where('email', '!=', 'superadmin@snsu.edu.ph')
            ->delete();

        // 1. Create or Update Super Admin
        Admin::updateOrCreate(
            ['email' => 'superadmin@snsu.edu.ph'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => false,
                'role' => 'super_admin',
                'profile_picture' => null,
            ]
        );

        // 2. Create or Update 6 Facility Admins
        for ($i = 1; $i <= 6; $i++) {
            Admin::updateOrCreate(
                ['email' => "admin{$i}@snsu.edu"],
                [
                    'name' => "Facility Admin {$i}",
                    'password' => Hash::make("password{$i}"),
                    'status' => false,
                    'role' => 'admin',
                    'profile_picture' => null,
                ]
            );
        }
    }
}

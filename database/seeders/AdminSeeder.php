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
        Admin::query()->delete();

        Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@snsu.edu.ph',
            'password' => Hash::make('password'),
            'status' => false,
            'role' => 'super_admin',
            'profile_picture' => null,
        ]);
    }
}

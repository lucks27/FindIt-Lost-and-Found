<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ItemCategory;
use App\Models\PointRule;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LostFoundDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Phone', 'slug' => 'phone', 'description' => 'Mobile phones and accessories'],
            ['name' => 'Wallet', 'slug' => 'wallet', 'description' => 'Wallets, cards, and cash holders'],
            ['name' => 'Identification Card', 'slug' => 'identification-card', 'description' => 'IDs and student credentials'],
            ['name' => 'Bag', 'slug' => 'bag', 'description' => 'Bags and carrying cases'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Documents', 'slug' => 'documents', 'description' => 'Documents and papers'],
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Clothing and wearables'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Jewelry and accessories'],
            ['name' => 'Keys', 'slug' => 'keys', 'description' => 'Keys and keychains'],
            ['name' => 'Others', 'slug' => 'others', 'description' => 'Other miscellaneous items'],
        ];

        foreach ($categories as $category) {
            ItemCategory::firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'description' => $category['description'], 'is_active' => true]);
        }

        $pointRuleMap = [
            'Phone' => 10,
            'Wallet' => 10,
            'Identification Card' => 5,
        ];

        foreach ($pointRuleMap as $name => $points) {
            $category = ItemCategory::where('name', $name)->first();
            if ($category) {
                PointRule::firstOrCreate(['category_id' => $category->id], ['base_points' => $points, 'is_active' => true]);
            }
        }

        Student::firstOrCreate(
            ['student_id_number' => '2026-0001'],
            [
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => 'student@example.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'course' => 'Computer Science',
                'department' => 'Engineering',
                'year_level' => '2',
            ]
        );

        Admin::query()->firstOrCreate(['email' => 'superadmin@snsu.edu.ph'], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'status' => true,
            'role' => 'super_admin',
        ]);
    }
}

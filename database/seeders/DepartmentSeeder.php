<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Business Administration', 'code' => 'BA'],
            ['name' => 'Education', 'code' => 'EDUC'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Hospitality Management', 'code' => 'HM'],
            ['name' => 'Arts and Sciences', 'code' => 'AS'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']]
            );
        }
    }
}

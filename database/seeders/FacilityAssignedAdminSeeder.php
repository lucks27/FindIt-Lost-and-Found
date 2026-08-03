<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacilityAssignedAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Facility::each(function (Facility $facility) {
            if (!$facility->administrator_name) {
                $facility->administrator_name = 'Super Admin';
            }

            if (!$facility->assigned_admin) {
                $facility->assigned_admin = $facility->administrator_name;
            }

            $facility->save();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $superAdminName = DB::table('admin')->where('role', 'super_admin')->value('name');

        if ($superAdminName) {
            DB::table('facilities')->update(['administrator_name' => $superAdminName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Down migration is intentionally left empty as this migration is data fix only.
    }
};

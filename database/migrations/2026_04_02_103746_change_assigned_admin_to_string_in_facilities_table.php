<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL to avoid requiring doctrine/dbal package in this environment.

        if (Schema::hasColumn('facilities', 'assigned_admin')) {
            DB::statement("ALTER TABLE `facilities` MODIFY `assigned_admin` VARCHAR(191) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('facilities', 'assigned_admin')) {
            DB::statement("ALTER TABLE `facilities` MODIFY `assigned_admin` BIGINT UNSIGNED NULL");
        }
    }
};

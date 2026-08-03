<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add booking details columns if they don't exist
            if (!Schema::hasColumn('notifications', 'facility_name')) {
                $table->string('facility_name')->nullable()->after('facility_id');
            }
            if (!Schema::hasColumn('notifications', 'booking_date')) {
                $table->date('booking_date')->nullable()->after('facility_name');
            }
            if (!Schema::hasColumn('notifications', 'time_in')) {
                $table->time('time_in')->nullable()->after('booking_date');
            }
            if (!Schema::hasColumn('notifications', 'time_out')) {
                $table->time('time_out')->nullable()->after('time_in');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumnIf('facility_name');
            $table->dropColumnIf('booking_date');
            $table->dropColumnIf('time_in');
            $table->dropColumnIf('time_out');
        });
    }
};

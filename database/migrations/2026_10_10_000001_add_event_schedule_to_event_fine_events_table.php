<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds schedule information without touching any existing fine records.
     * Event details are nullable for older events that have not been scheduled.
     */
    public function up(): void
    {
        if (!Schema::hasTable('event_fine_events')) {
            throw new RuntimeException(
                'Missing event_fine_events table. Run the earlier table creation migration first.'
            );
        }

        if (!Schema::hasColumn('event_fine_events', 'event_date')) {
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->date('event_date')->nullable();
            });
        }

        if (!Schema::hasColumn('event_fine_events', 'event_time')) {
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->time('event_time')->nullable();
            });
        }

        if (!Schema::hasColumn('event_fine_events', 'venue')) {
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->string('venue', 191)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Do not drop schedule data on rollback; historical event details must be preserved.
    }
};

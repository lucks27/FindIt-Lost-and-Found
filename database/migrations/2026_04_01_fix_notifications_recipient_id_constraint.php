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
        // Drop the foreign key constraint on recipient_id to allow it to reference any table.
        // Use a safe wrapper so the migration does not fail if the constraint does not exist.
        try {
            DB::statement('ALTER TABLE notifications DROP FOREIGN KEY notifications_recipient_id_foreign');
        } catch (\Exception $e) {
            // Ignore if the constraint does not exist or cannot be dropped.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_recipient_id_foreign FOREIGN KEY (recipient_id) REFERENCES students(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Ignore if the constraint already exists or cannot be added.
        }
    }
};

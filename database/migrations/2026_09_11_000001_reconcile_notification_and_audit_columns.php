<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'type')) {
                    $table->string('type')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'status')) {
                    $table->string('status')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'booking_id')) {
                    $table->unsignedBigInteger('booking_id')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'facility_id')) {
                    $table->unsignedBigInteger('facility_id')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'facility_name')) {
                    $table->string('facility_name')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'booking_date')) {
                    $table->date('booking_date')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'time_in')) {
                    $table->string('time_in')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'time_out')) {
                    $table->string('time_out')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'review_status')) {
                    $table->string('review_status')->nullable();
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'actor_type')) {
                    $table->string('actor_type')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'actor_id')) {
                    $table->unsignedBigInteger('actor_id')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'old_values')) {
                    $table->json('old_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'new_values')) {
                    $table->json('new_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // These columns reconcile multiple historical schemas and are intentionally retained on rollback.
    }
};
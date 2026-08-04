<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('admin')) {
            Schema::table('admin', function (Blueprint $table) {
                if (Schema::hasColumn('admin', 'facility_id')) {
                    $table->dropColumn('facility_id');
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'facility_id')) {
                    $table->dropForeign(['facility_id']);
                    $table->dropColumn('facility_id');
                }
            });
        }

        Schema::dropIfExists('facilities');
    }

    public function down(): void
    {
        if (!Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table) {
                $table->id();
                $table->string('administrator_name')->nullable();
                $table->string('assigned_admin')->nullable();
                $table->string('facility_name');
                $table->string('type')->nullable();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->string('time_open')->nullable();
                $table->string('time_close')->nullable();
                $table->string('operating_hours')->nullable();
                $table->string('image')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('admin') && !Schema::hasColumn('admin', 'facility_id')) {
            Schema::table('admin', function (Blueprint $table) {
                $table->foreignId('facility_id')
                    ->nullable()
                    ->constrained('facilities')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'facility_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->after('recipient_id')
                    ->constrained('facilities')->nullOnDelete();
            });
        }
    }
};

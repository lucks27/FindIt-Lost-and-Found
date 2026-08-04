<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'booking_id')) {
                    $table->dropForeign(['booking_id']);
                    $table->dropColumn('booking_id');
                }

                if (Schema::hasColumn('notifications', 'facility_name')) {
                    $table->dropColumn('facility_name');
                }

                if (Schema::hasColumn('notifications', 'booking_date')) {
                    $table->dropColumn('booking_date');
                }

                if (Schema::hasColumn('notifications', 'time_in')) {
                    $table->dropColumn('time_in');
                }

                if (Schema::hasColumn('notifications', 'time_out')) {
                    $table->dropColumn('time_out');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::dropIfExists('bookings');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
                $table->string('year_section');
                $table->string('purpose');
                $table->date('date');
                $table->time('time_in');
                $table->time('time_out');
                $table->string('status')->default('PENDING');
                $table->string('administrator_name')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
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
                if (!Schema::hasColumn('notifications', 'booking_id')) {
                    $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');
                }
            });
        }
    }
};

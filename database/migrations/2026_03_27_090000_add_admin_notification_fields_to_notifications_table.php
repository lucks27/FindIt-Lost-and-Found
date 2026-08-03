<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->default('generic')->after('booking_id');
            }

            if (!Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (!Schema::hasColumn('notifications', 'message')) {
                $table->text('message')->nullable()->after('title');
            }

            if (!Schema::hasColumn('notifications', 'facility_id')) {
                $table->foreignId('facility_id')->nullable()->after('recipient_id')
                    ->constrained('facilities')->nullOnDelete();
            }

            if (!Schema::hasColumn('notifications', 'actor_admin_id')) {
                $table->foreignId('actor_admin_id')->nullable()->after('facility_id')
                    ->constrained('admin')->nullOnDelete();
            }

            if (!Schema::hasColumn('notifications', 'recipient_role')) {
                $table->string('recipient_role')->nullable()->after('actor_admin_id');
            }

            if (!Schema::hasColumn('notifications', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('recipient_role');
            }

            if (!Schema::hasColumn('notifications', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }

            if (!Schema::hasColumn('notifications', 'review_status')) {
                $table->string('review_status')->nullable()->after('subject_id');
            }

            if (!Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('review_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actor_admin_id');
            $table->dropConstrainedForeignId('facility_id');
            $table->dropColumn([
                'type',
                'title',
                'message',
                'recipient_role',
                'subject_type',
                'subject_id',
                'review_status',
                'data',
            ]);
        });
    }
};

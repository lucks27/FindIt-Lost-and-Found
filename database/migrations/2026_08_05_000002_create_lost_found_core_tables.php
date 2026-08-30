<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('admin')) {
            Schema::create('admin', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->string('profile_photo')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('student_id_number')->unique();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('course')->nullable();
                $table->string('department')->nullable();
                $table->string('year_level')->nullable();
                $table->string('profile_photo')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('item_categories')) {
            Schema::create('item_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('description');
                $table->string('color')->nullable();
                $table->date('date_occurred');
                $table->string('location');
                $table->text('additional_details')->nullable();
                $table->text('private_identifying_details')->nullable();
                $table->string('status');
                $table->text('review_notes')->nullable();
                $table->foreignId('approved_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['student_id', 'status']);
                $table->index(['category_id', 'status']);
                $table->index(['type', 'status']);
                $table->index('date_occurred');
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('report_images')) {
            Schema::create('report_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('surrender_requests')) {
            Schema::create('surrender_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
                $table->foreignId('finder_student_id')->constrained('students')->restrictOnDelete();
                $table->string('status')->default('pending');
                $table->timestamp('deadline_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->foreignId('received_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->foreignId('rejected_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('item_verifications')) {
            Schema::create('item_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
                $table->foreignId('surrender_request_id')->unique()->constrained('surrender_requests')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->foreignId('rejected_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('details_requested_at')->nullable();
                $table->foreignId('details_requested_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('inventory_code')->unique();
                $table->foreignId('found_report_id')->unique()->constrained('reports')->cascadeOnDelete();
                $table->foreignId('surrender_request_id')->constrained('surrender_requests')->cascadeOnDelete();
                $table->foreignId('verification_id')->nullable()->constrained('item_verifications')->nullOnDelete();
                $table->string('status')->default('pending_verification');
                $table->string('storage_location')->nullable();
                $table->text('condition_notes')->nullable();
                $table->timestamp('received_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->foreignId('received_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->foreignId('verified_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('potential_matches')) {
            Schema::create('potential_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lost_report_id')->constrained('reports')->restrictOnDelete();
                $table->foreignId('found_report_id')->constrained('reports')->restrictOnDelete();
                $table->unsignedTinyInteger('score')->default(0);
                $table->json('matched_fields')->nullable();
                $table->string('source')->default('system');
                $table->string('status')->default('pending');
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('created_by_student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->timestamps();
                $table->unique(['lost_report_id', 'found_report_id']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('claims')) {
            Schema::create('claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('found_report_id')->constrained('reports')->restrictOnDelete();
                $table->foreignId('lost_report_id')->nullable()->constrained('reports')->nullOnDelete();
                $table->foreignId('potential_match_id')->nullable()->constrained('potential_matches')->nullOnDelete();
                $table->foreignId('claimant_student_id')->constrained('students')->restrictOnDelete();
                $table->string('ownership_proof');
                $table->text('additional_proof')->nullable();
                $table->string('status')->default('pending');
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['found_report_id', 'status']);
                $table->index(['claimant_student_id', 'found_report_id', 'status']);
            });
        }

        if (!Schema::hasTable('claim_attachments')) {
            Schema::create('claim_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('claim_status_histories')) {
            Schema::create('claim_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
                $table->string('previous_status')->nullable();
                $table->string('new_status');
                $table->text('notes')->nullable();
                $table->string('changed_by_type');
                $table->unsignedBigInteger('changed_by_id');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('item_releases')) {
            Schema::create('item_releases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->unique()->constrained('claims')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->unique()->constrained('inventory_items')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->json('checklist')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->foreignId('released_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->boolean('claimant_acknowledgment')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('completed_returns')) {
            Schema::create('completed_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('release_id')->unique()->constrained('item_releases')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->unique()->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('found_report_id')->constrained('reports')->restrictOnDelete();
                $table->foreignId('lost_report_id')->nullable()->constrained('reports')->nullOnDelete();
                $table->foreignId('finder_student_id')->constrained('students')->restrictOnDelete();
                $table->foreignId('claimant_student_id')->constrained('students')->restrictOnDelete();
                $table->foreignId('completed_by_admin_id')->constrained('admin')->restrictOnDelete();
                $table->timestamp('completed_at');
                $table->unsignedInteger('points_awarded')->default(0);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('point_rules')) {
            Schema::create('point_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->unique()->constrained('item_categories')->cascadeOnDelete();
                $table->unsignedInteger('base_points')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('updated_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_fines')) {
            Schema::create('event_fines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
                $table->string('event_name');
                $table->text('description')->nullable();
                $table->decimal('original_amount', 10, 2);
                $table->decimal('remaining_amount', 10, 2);
                $table->date('due_date')->nullable();
                $table->string('status')->default('unpaid');
                $table->foreignId('created_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('event_fine_conversions')) {
            Schema::create('event_fine_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_fine_id')->constrained('event_fines')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
                $table->unsignedInteger('requested_points');
                $table->decimal('conversion_rate', 10, 2);
                $table->decimal('credit_amount', 10, 2);
                $table->string('status')->default('pending');
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
                $table->foreignId('completed_return_id')->nullable()->constrained('completed_returns')->nullOnDelete();
                $table->foreignId('event_fine_conversion_id')->nullable()->constrained('event_fine_conversions')->nullOnDelete();
                $table->string('type');
                $table->string('direction');
                $table->unsignedInteger('points')->default(0);
                $table->unsignedInteger('balance_after')->nullable();
                $table->string('reference_key')->nullable()->unique();
                $table->text('notes')->nullable();
                $table->foreignId('processed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
                $table->index(['student_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('push_tokens')) {
            Schema::create('push_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('expo_push_token')->unique();
                $table->string('device_id')->nullable();
                $table->string('platform')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->index('student_id');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id']);
                $table->index('read_at');
            });
        }

        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('message');
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('created_by_admin_id')->constrained('admin')->restrictOnDelete();
                $table->foreignId('updated_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('actor_type')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->text('description');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['actor_type', 'actor_id']);
                $table->index(['subject_type', 'subject_id']);
                $table->index('action');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('push_tokens');
        Schema::dropIfExists('event_fine_conversions');
        Schema::dropIfExists('event_fines');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('point_rules');
        Schema::dropIfExists('completed_returns');
        Schema::dropIfExists('item_releases');
        Schema::dropIfExists('claim_status_histories');
        Schema::dropIfExists('claim_attachments');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('potential_matches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('item_verifications');
        Schema::dropIfExists('surrender_requests');
        Schema::dropIfExists('report_images');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('students');
        Schema::dropIfExists('admin');
    }
};

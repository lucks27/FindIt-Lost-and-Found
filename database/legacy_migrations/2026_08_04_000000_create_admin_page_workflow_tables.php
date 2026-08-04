<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('admin_announcements')) {
            Schema::create('admin_announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('admin')->cascadeOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('audience')->default('all');
                $table->json('target_filters')->nullable();
                $table->string('status')->default('draft');
                $table->dateTime('published_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('subject_label')->nullable();
                $table->json('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('claim_reviews')) {
            Schema::create('claim_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                $table->foreignId('claimant_student_id')->constrained('students')->cascadeOnDelete();
                $table->string('review_status')->default('pending');
                $table->json('evidence')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('item_verifications')) {
            Schema::create('item_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                $table->foreignId('verifier_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->json('checklist')->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('item_releases')) {
            Schema::create('item_releases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                $table->foreignId('claimant_student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('approved_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->string('release_code')->unique();
                $table->string('status')->default('pending');
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('released_at')->nullable();
                $table->json('checklist')->nullable();
                $table->string('receipt_number')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('potential_matches')) {
            Schema::create('potential_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_report_id')->constrained('reports')->cascadeOnDelete();
                $table->foreignId('matched_report_id')->constrained('reports')->cascadeOnDelete();
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('surrender_requests')) {
            Schema::create('surrender_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->dateTime('deadline_at')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->foreignId('received_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_fines')) {
            Schema::create('event_fines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
                $table->string('event_name');
                $table->string('reference_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('open');
                $table->text('reason')->nullable();
                $table->foreignId('issued_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->date('due_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admin_settings')) {
            Schema::create('admin_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('updated_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('event_fines');
        Schema::dropIfExists('surrender_requests');
        Schema::dropIfExists('potential_matches');
        Schema::dropIfExists('item_releases');
        Schema::dropIfExists('item_verifications');
        Schema::dropIfExists('claim_reviews');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('admin_announcements');
    }
};

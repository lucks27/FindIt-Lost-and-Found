<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->string('report_type');
                $table->string('category')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('location')->nullable();
                $table->date('event_date')->nullable();
                $table->string('status')->default('open');
                $table->decimal('reward_amount', 10, 2)->nullable();
                $table->string('reward_type')->nullable();
                $table->string('image')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rewards')) {
            Schema::create('rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('type')->default('reward');
                $table->string('status')->default('pending');
                $table->date('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->string('token')->unique();
                $table->string('platform')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('reports');
    }
};

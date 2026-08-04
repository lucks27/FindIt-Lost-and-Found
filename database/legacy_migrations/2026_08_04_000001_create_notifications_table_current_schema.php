<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('type')->default('generic');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('status')->nullable();
            $table->string('action')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->string('facility_name')->nullable();
            $table->date('booking_date')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->unsignedBigInteger('actor_admin_id')->nullable();
            $table->string('recipient_role')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('review_status')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            $table->foreign('actor_admin_id')->references('id')->on('admin')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

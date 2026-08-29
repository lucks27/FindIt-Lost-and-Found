<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_reviews');
    }
};

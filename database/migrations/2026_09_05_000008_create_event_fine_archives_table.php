<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fine_archives')) {
            Schema::create('event_fine_archives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_fine_id')->unique()->constrained('event_fines')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->string('student_name')->nullable();
                $table->string('school_id')->nullable();
                $table->string('event_name');
                $table->decimal('original_amount', 10, 2)->default(0);
                $table->decimal('amount_to_pay', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('remaining_amount', 10, 2)->default(0);
                $table->string('status')->default('paid');
                $table->text('reason')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_fines')) {
            return;
        }

        DB::table('event_fines')->where('status', 'paid')->orderBy('id')->eachById(function ($fine) {
            DB::table('event_fine_archives')->updateOrInsert(
                ['event_fine_id' => $fine->id],
                [
                    'student_id' => $fine->student_id,
                    'student_name' => $fine->student_name,
                    'school_id' => $fine->school_id,
                    'event_name' => $fine->event_name,
                    'original_amount' => $fine->original_amount ?? $fine->amount ?? 0,
                    'amount_to_pay' => $fine->amount_to_pay ?? $fine->amount ?? 0,
                    'paid_amount' => $fine->paid ?? $fine->paid_amount ?? 0,
                    'remaining_amount' => $fine->remaining_amount ?? 0,
                    'status' => 'paid',
                    'reason' => $fine->reason,
                    'paid_at' => $fine->updated_at,
                    'created_at' => $fine->created_at,
                    'updated_at' => now(),
                ]
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_fine_archives');
    }
};

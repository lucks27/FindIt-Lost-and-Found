<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add only missing columns for the existing student fine assignment API.
 * Safe for existing fine records: nullable/defaulted columns only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_fines')) {
            throw new \RuntimeException('event_fines table must exist before this migration.');
        }

        $existing = array_flip(Schema::getColumnListing('event_fines'));

        Schema::table('event_fines', function (Blueprint $table) use ($existing) {
            if (!isset($existing['event_fine_event_id'])) {
                $table->unsignedBigInteger('event_fine_event_id')->nullable();
            }
            if (!isset($existing['student_name'])) {
                $table->string('student_name', 191)->nullable();
            }
            if (!isset($existing['school_id'])) {
                $table->string('school_id', 100)->nullable();
            }
            if (!isset($existing['amount'])) {
                $table->decimal('amount', 12, 2)->nullable();
            }
            if (!isset($existing['amount_to_pay'])) {
                $table->decimal('amount_to_pay', 12, 2)->nullable();
            }
            if (!isset($existing['paid_amount'])) {
                $table->decimal('paid_amount', 12, 2)->default(0);
            }
            if (!isset($existing['paid'])) {
                $table->decimal('paid', 12, 2)->default(0);
            }
            if (!isset($existing['reason'])) {
                $table->text('reason')->nullable();
            }
            if (!isset($existing['due_at'])) {
                $table->date('due_at')->nullable();
            }
            if (!isset($existing['reference_number'])) {
                $table->string('reference_number', 64)->nullable();
            }
            if (!isset($existing['issued_by_admin_id'])) {
                $table->unsignedBigInteger('issued_by_admin_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep financial history and existing data intact.
        // Reversing this additive migration must not remove fields in use.
    }
};

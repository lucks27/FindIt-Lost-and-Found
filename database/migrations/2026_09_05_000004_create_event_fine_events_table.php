<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fine_events')) {
            Schema::create('event_fine_events', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->decimal('fine_amount', 10, 2);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_fines')) {
            return;
        }

        Schema::table('event_fines', function (Blueprint $table) {
            if (!Schema::hasColumn('event_fines', 'event_fine_event_id')) {
                $table->foreignId('event_fine_event_id')->nullable()->constrained('event_fine_events')->nullOnDelete();
            }
            if (!Schema::hasColumn('event_fines', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('event_fines', 'amount_to_pay')) {
                $table->decimal('amount_to_pay', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('event_fines', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('event_fines', 'remaining_amount')) {
                $table->decimal('remaining_amount', 10, 2)->nullable();
            }
        });

        DB::table('event_fines')->whereNull('original_amount')->update([
            'original_amount' => DB::raw('amount'),
            'amount_to_pay' => DB::raw('amount'),
            'remaining_amount' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('event_fines')) {
            Schema::table('event_fines', function (Blueprint $table) {
                foreach (['event_fine_event_id', 'original_amount', 'amount_to_pay', 'paid_amount', 'remaining_amount'] as $column) {
                    if (Schema::hasColumn('event_fines', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('event_fine_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        DB::table('event_fines')
            ->whereNull('amount_to_pay')
            ->update(['amount_to_pay' => DB::raw('COALESCE(amount, original_amount, 0)')]);

        DB::table('event_fines')
            ->where(function ($query) {
                $query->whereNull('paid_amount')->orWhere('paid_amount', 0);
            })
            ->whereNotNull('original_amount')
            ->whereNotNull('remaining_amount')
            ->whereColumn('remaining_amount', '<', 'original_amount')
            ->update([
                'paid_amount' => DB::raw('original_amount - remaining_amount'),
                'paid' => DB::raw('original_amount - remaining_amount'),
            ]);

        DB::table('event_fines')
            ->where(function ($query) {
                $query->whereNull('paid')->orWhere('paid', 0);
            })
            ->update(['paid' => DB::raw('COALESCE(paid_amount, 0)')]);
    }

    public function down(): void
    {
        // Preserve transaction snapshots when rolling back later migrations.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make sure event_fines exists
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Add missing payment snapshot columns
        |--------------------------------------------------------------------------
        |
        | The existing event_fines table contains:
        | - original_amount
        | - remaining_amount
        | - paid
        |
        | Some application code and later migrations expect:
        | - amount_to_pay
        | - paid_amount
        |
        | Add those columns here if they do not already exist.
        |
        */

        if (!Schema::hasColumn('event_fines', 'amount_to_pay')) {
            Schema::table('event_fines', function (Blueprint $table) {
                $table->decimal('amount_to_pay', 10, 2)
                    ->nullable()
                    ->after('remaining_amount');
            });
        }

        if (!Schema::hasColumn('event_fines', 'paid_amount')) {
            Schema::table('event_fines', function (Blueprint $table) {
                $table->decimal('paid_amount', 10, 2)
                    ->nullable()
                    ->after('amount_to_pay');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure the paid column exists
        |--------------------------------------------------------------------------
        |
        | Migration 000006 normally creates this column, but this check
        | makes this migration safer if the database schema differs.
        |
        */

        if (!Schema::hasColumn('event_fines', 'paid')) {
            Schema::table('event_fines', function (Blueprint $table) {
                $table->decimal('paid', 10, 2)
                    ->default(0)
                    ->after('paid_amount');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill amount_to_pay
        |--------------------------------------------------------------------------
        |
        | If an older version of the database has an "amount" column,
        | use it first. Otherwise, use the current original_amount field.
        |
        */

        $amountExpression = Schema::hasColumn('event_fines', 'amount')
            ? 'COALESCE(amount, original_amount, 0)'
            : 'COALESCE(original_amount, 0)';

        DB::table('event_fines')
            ->where(function ($query) {
                $query->whereNull('amount_to_pay')
                    ->orWhere('amount_to_pay', 0);
            })
            ->update([
                'amount_to_pay' => DB::raw($amountExpression),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Backfill paid_amount
        |--------------------------------------------------------------------------
        |
        | The existing schema stores:
        |
        | original_amount = total fine
        | remaining_amount = unpaid balance
        |
        | Therefore:
        |
        | paid_amount = original_amount - remaining_amount
        |
        */

        DB::table('event_fines')
            ->where(function ($query) {
                $query->whereNull('paid_amount')
                    ->orWhere('paid_amount', 0);
            })
            ->whereNotNull('original_amount')
            ->whereNotNull('remaining_amount')
            ->update([
                'paid_amount' => DB::raw(
                    'GREATEST(original_amount - remaining_amount, 0)'
                ),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Synchronize the paid field
        |--------------------------------------------------------------------------
        |
        | Keep the existing "paid" field synchronized with "paid_amount".
        |
        */

        DB::table('event_fines')
            ->where(function ($query) {
                $query->whereNull('paid')
                    ->orWhere('paid', 0);
            })
            ->update([
                'paid' => DB::raw('COALESCE(paid_amount, 0)'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove only the snapshot columns introduced by this migration
        |--------------------------------------------------------------------------
        |
        | Do not remove "paid" because that column is created by migration
        | 000006.
        |
        */

        $columns = [];

        if (Schema::hasColumn('event_fines', 'paid_amount')) {
            $columns[] = 'paid_amount';
        }

        if (Schema::hasColumn('event_fines', 'amount_to_pay')) {
            $columns[] = 'amount_to_pay';
        }

        if (!empty($columns)) {
            Schema::table('event_fines', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

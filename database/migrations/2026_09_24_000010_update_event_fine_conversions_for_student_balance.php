<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fine_conversions')) {
            return;
        }

        Schema::table('event_fine_conversions', function (Blueprint $table) {
            if (Schema::hasColumn('event_fine_conversions', 'event_fine_id')) {
                $table->unsignedBigInteger('event_fine_id')->nullable()->change();
            }

            foreach (['total_original_amount', 'converted_points', 'peso_value_converted', 'remaining_balance'] as $column) {
                if (!Schema::hasColumn('event_fine_conversions', $column)) {
                    switch ($column) {
                        case 'total_original_amount':
                            $table->decimal('total_original_amount', 10, 2)->default(0)->after('student_id');
                            break;
                        case 'converted_points':
                            $table->unsignedInteger('converted_points')->default(0)->after('total_original_amount');
                            break;
                        case 'peso_value_converted':
                            $table->decimal('peso_value_converted', 10, 2)->default(0)->after('converted_points');
                            break;
                        case 'remaining_balance':
                            $table->decimal('remaining_balance', 10, 2)->default(0)->after('peso_value_converted');
                            break;
                    }
                }
            }
        });

        DB::table('event_fine_conversions')->whereNull('total_original_amount')->update([
            'total_original_amount' => DB::raw('COALESCE(credit_amount, 0)'),
            'converted_points' => DB::raw('COALESCE(requested_points, 0)'),
            'peso_value_converted' => DB::raw('COALESCE(credit_amount, 0)'),
            'remaining_balance' => DB::raw('COALESCE(credit_amount, 0) * 0'),
        ]);

        DB::table('event_fine_conversions')
            ->whereNotNull('student_id')
            ->whereNull('event_fine_id')
            ->update(['status' => DB::raw('COALESCE(status, "pending")')]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_fine_conversions')) {
            return;
        }

        Schema::table('event_fine_conversions', function (Blueprint $table) {
            foreach (['total_original_amount', 'converted_points', 'peso_value_converted', 'remaining_balance'] as $column) {
                if (Schema::hasColumn('event_fine_conversions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

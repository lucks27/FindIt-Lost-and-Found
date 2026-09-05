<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('point_rules') || Schema::hasColumn('point_rules', 'category_name')) {
            return;
        }

        Schema::table('point_rules', function (Blueprint $table) {
            $table->string('category_name', 120)->nullable()->after('id');
        });

        $pointRules = DB::table('point_rules')->whereNull('category_name');

        if (Schema::hasColumn('point_rules', 'category_id')) {
            $categoryNames = DB::table('item_categories')->pluck('name', 'id');

            foreach ($pointRules->get() as $pointRule) {
                $legacyName = Schema::hasColumn('point_rules', 'item_category')
                    ? $pointRule->item_category
                    : null;

                DB::table('point_rules')
                    ->where('id', $pointRule->id)
                    ->update(['category_name' => $legacyName ?: $categoryNames[$pointRule->category_id] ?? null]);
            }
        } elseif (Schema::hasColumn('point_rules', 'item_category')) {
            foreach ($pointRules->pluck('item_category', 'id') as $id => $categoryName) {
                DB::table('point_rules')->where('id', $id)->update(['category_name' => $categoryName]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('point_rules') && Schema::hasColumn('point_rules', 'category_name')) {
            Schema::table('point_rules', function (Blueprint $table) {
                $table->dropColumn('category_name');
            });
        }
    }
};
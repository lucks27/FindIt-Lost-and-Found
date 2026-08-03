<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('facilities', function (Blueprint $table) {
            // Only rename if category column exists
            if (Schema::hasColumn('facilities', 'category')) {
                $table->renameColumn('category', 'type');
            }
        });
    }

    public function down()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->renameColumn('type', 'category');  // Revert rename if needed
        });
    }
};

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
            // Keep as string type to support all existing values
            $table->string('type')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('type')->change(); // Revert to string if needed
        });
    }
};

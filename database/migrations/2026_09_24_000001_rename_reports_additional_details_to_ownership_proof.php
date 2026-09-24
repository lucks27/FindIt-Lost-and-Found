<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'additional_details') && !Schema::hasColumn('reports', 'ownership_proof')) {
                $table->renameColumn('additional_details', 'ownership_proof');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'ownership_proof') && !Schema::hasColumn('reports', 'additional_details')) {
                $table->renameColumn('ownership_proof', 'additional_details');
            }
        });
    }
};

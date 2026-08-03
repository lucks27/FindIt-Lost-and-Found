<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * What path i input if i use inside the images folder? You should use '/assets/images/facilities/' and store only the filename here.
     */
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
        // We check if 'type' exists before adding it
        if (!Schema::hasColumn('facilities', 'type')) {
            $table->string('type')->nullable();
        }
        
        if (!Schema::hasColumn('facilities', 'location')) {
            $table->string('location')->nullable();
        }
        
        if (!Schema::hasColumn('facilities', 'operating_hours')) {
            $table->string('operating_hours')->nullable();
        }
        
        if (!Schema::hasColumn('facilities', 'image_filename')) {
            $table->string('image_filename')->nullable();
        }
        });
    }

    /**
     * Reverse the migrations.
     * Failed to resolve import error for "/assets/images/snsu-bg.jpg" is solved.
     */
    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['type', 'location', 'operating_hours', 'image_filename']);
        });
    }
};
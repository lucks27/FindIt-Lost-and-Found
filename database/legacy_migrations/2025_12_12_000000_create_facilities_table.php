<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('facilities')) {
            return;
        }

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('administrator_name')->nullable();
            $table->string('assigned_admin')->nullable();
            $table->string('facility_name');
            $table->string('type')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('time_open')->nullable();
            $table->string('time_close')->nullable();
            $table->string('operating_hours')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};

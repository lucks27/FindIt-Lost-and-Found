<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
  // In ...create_bookings_table.php
public function up(): void
{
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        
        // Use this specific syntax for foreign keys:
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

        $table->string('year_section');
        $table->string('purpose');
        $table->date('date');
        $table->time('time_in');
        $table->time('time_out');
        $table->string('status')->default('PENDING');
        $table->timestamps();
    });
}
};

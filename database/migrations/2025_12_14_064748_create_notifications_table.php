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
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        
        // This is the specific order needed for your later "after" migrations:
        $table->unsignedBigInteger('recipient_id')->nullable(); 
        $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');

        $table->string('title');
        $table->text('message');
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });
}

};
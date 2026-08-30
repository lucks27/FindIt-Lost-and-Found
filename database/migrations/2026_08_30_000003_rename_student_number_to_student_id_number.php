<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('students', 'student_number') && !Schema::hasColumn('students', 'student_id_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->renameColumn('student_number', 'student_id_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'student_id_number') && !Schema::hasColumn('students', 'student_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->renameColumn('student_id_number', 'student_number');
            });
        }
    }
};

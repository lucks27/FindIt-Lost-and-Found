<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        Schema::table('event_fines', function (Blueprint $table) {
            if (!Schema::hasColumn('event_fines', 'student_name')) {
                $table->string('student_name')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('event_fines', 'school_id')) {
                $table->string('school_id')->nullable()->after('student_name');
            }
            if (!Schema::hasColumn('event_fines', 'paid')) {
                $table->decimal('paid', 10, 2)->default(0)->after('paid_amount');
            }
        });

        $students = DB::table('students')->select('id', 'student_id_number', 'first_name', 'middle_name', 'last_name')->get();
        foreach ($students as $student) {
            $name = trim(implode(' ', array_filter([
                $student->first_name,
                $student->middle_name,
                $student->last_name,
            ])));

            DB::table('event_fines')
                ->where('student_id', $student->id)
                ->update([
                    'student_name' => $name ?: null,
                    'school_id' => $student->student_id_number,
                    'paid' => DB::raw('COALESCE(paid_amount, 0)'),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        Schema::table('event_fines', function (Blueprint $table) {
            foreach (['student_name', 'school_id', 'paid'] as $column) {
                if (Schema::hasColumn('event_fines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

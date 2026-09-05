<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('student_recognition_records')) {
            return;
        }

        Schema::create('student_recognition_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('recognition_points')->default(0);
            $table->decimal('peso_value', 10, 2)->default(0);
            $table->unsignedInteger('reward_count')->default(0);
            $table->unsignedInteger('report_count')->default(0);
            $table->timestamps();
        });

        $now = now();
        $students = DB::table('students')
            ->leftJoin('point_transactions', 'students.id', '=', 'point_transactions.student_id')
            ->select(
                'students.id',
                'students.reward_count',
                DB::raw('COALESCE(SUM(point_transactions.points), 0) as recognition_points'),
                DB::raw('(SELECT COUNT(*) FROM reports WHERE reports.student_id = students.id) as report_count')
            )
            ->groupBy('students.id', 'students.reward_count')
            ->get();

        DB::table('student_recognition_records')->insert($students->map(function ($student) use ($now) {
            $points = (int) $student->recognition_points;

            return [
                'student_id' => $student->id,
                'recognition_points' => $points,
                'peso_value' => round($points / 100, 2),
                'reward_count' => (int) ($student->reward_count ?? 0),
                'report_count' => (int) $student->report_count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('student_recognition_records');
    }
};
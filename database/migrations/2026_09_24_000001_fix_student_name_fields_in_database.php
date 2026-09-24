<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        $students = DB::table('students')->get();

        foreach ($students as $student) {
            $firstName = trim((string) ($student->first_name ?? ''));
            $middleName = trim((string) ($student->middle_name ?? ''));
            $lastName = trim((string) ($student->last_name ?? ''));

            DB::table('students')
                ->where('id', $student->id)
                ->update([
                    'first_name' => $firstName,
                    'middle_name' => $middleName !== '' ? $middleName : null,
                    'last_name' => $lastName,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank because this is a data correction migration.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make sure the event_fines table exists
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Add transaction snapshot fields
        |--------------------------------------------------------------------------
        */
        Schema::table('event_fines', function (Blueprint $table) {
            if (!Schema::hasColumn('event_fines', 'student_name')) {
                $table->string('student_name')->nullable()->after('student_id');
            }

            if (!Schema::hasColumn('event_fines', 'school_id')) {
                $table->string('school_id')->nullable()->after('student_name');
            }

            /*
             * The original event_fines table does not contain a
             * paid_amount column.
             *
             * Existing fields:
             * - original_amount
             * - remaining_amount
             *
             * Therefore, the paid amount will be calculated as:
             *
             * paid = original_amount - remaining_amount
             */
            if (!Schema::hasColumn('event_fines', 'paid')) {
                $table->decimal('paid', 10, 2)
                    ->default(0)
                    ->after('remaining_amount');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Populate Student Snapshot Information
        |--------------------------------------------------------------------------
        |
        | Copy the student's current name and student ID number into the
        | event_fines table so the historical fine record retains the
        | student's information even if the student profile changes later.
        |
        */
        if (
            Schema::hasColumn('event_fines', 'student_name') &&
            Schema::hasColumn('event_fines', 'school_id')
        ) {
            $students = DB::table('students')
                ->select(
                    'id',
                    'student_id_number',
                    'first_name',
                    'middle_name',
                    'last_name'
                )
                ->get();

            foreach ($students as $student) {
                $name = trim(
                    implode(
                        ' ',
                        array_filter([
                            $student->first_name,
                            $student->middle_name,
                            $student->last_name,
                        ])
                    )
                );

                DB::table('event_fines')
                    ->where('student_id', $student->id)
                    ->update([
                        'student_name' => $name ?: null,
                        'school_id' => $student->student_id_number,
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Populate Paid Amount
        |--------------------------------------------------------------------------
        |
        | paid = original_amount - remaining_amount
        |
        | GREATEST(..., 0) prevents a negative paid amount in case the
        | existing data contains an inconsistent remaining amount.
        |
        */
        if (Schema::hasColumn('event_fines', 'paid')) {
            DB::table('event_fines')->update([
                'paid' => DB::raw(
                    'GREATEST(' .
                    'COALESCE(original_amount, 0) - ' .
                    'COALESCE(remaining_amount, 0), ' .
                    '0' .
                    ')'
                ),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        Schema::table('event_fines', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('event_fines', 'student_name')) {
                $columns[] = 'student_name';
            }

            if (Schema::hasColumn('event_fines', 'school_id')) {
                $columns[] = 'school_id';
            }

            if (Schema::hasColumn('event_fines', 'paid')) {
                $columns[] = 'paid';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

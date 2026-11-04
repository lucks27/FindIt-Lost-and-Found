<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Departments Table
        |--------------------------------------------------------------------------
        |
        | Explicitly use InnoDB because this table is referenced by foreign
        | keys from the students and users tables.
        |
        */

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->engine('InnoDB');

                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Add Department to Students
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('students') &&
            !Schema::hasColumn('students', 'department_id')
        ) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')
                    ->nullable()
                    ->after('department');

                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Add Department to Users
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('users') &&
            !Schema::hasColumn('users', 'department_id')
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')
                    ->nullable()
                    ->after('email');

                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Student Emails
        |--------------------------------------------------------------------------
        |
        | Convert student email addresses to the university email domain.
        | Only modify addresses that contain an @ symbol and do not already
        | end with @ssct.edu.ph.
        |
        */

        if (
            Schema::hasTable('students') &&
            Schema::hasColumn('students', 'email')
        ) {
            DB::table('students')
                ->select('id', 'email')
                ->get()
                ->each(function ($student) {
                    $email = strtolower(trim((string) $student->email));

                    if (
                        $email !== '' &&
                        str_contains($email, '@') &&
                        !str_ends_with($email, '@ssct.edu.ph')
                    ) {
                        $username = strstr($email, '@', true);

                        if ($username !== false && $username !== '') {
                            DB::table('students')
                                ->where('id', $student->id)
                                ->update([
                                    'email' => $username . '@ssct.edu.ph',
                                ]);
                        }
                    }
                });
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize User Emails
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'email')
        ) {
            DB::table('users')
                ->select('id', 'email')
                ->get()
                ->each(function ($user) {
                    $email = strtolower(trim((string) $user->email));

                    if (
                        $email !== '' &&
                        str_contains($email, '@') &&
                        !str_ends_with($email, '@ssct.edu.ph')
                    ) {
                        $username = strstr($email, '@', true);

                        if ($username !== false && $username !== '') {
                            DB::table('users')
                                ->where('id', $user->id)
                                ->update([
                                    'email' => $username . '@ssct.edu.ph',
                                ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Department Foreign Key from Users
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'department_id')
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Department Foreign Key from Students
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('students') &&
            Schema::hasColumn('students', 'department_id')
        ) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Departments Table
        |--------------------------------------------------------------------------
        */

        Schema::dropIfExists('departments');
    }
};

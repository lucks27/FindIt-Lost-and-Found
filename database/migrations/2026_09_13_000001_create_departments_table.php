<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'department_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('department');
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('email');
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'email')) {
            DB::table('students')->select('id', 'email')->get()->each(function ($student) {
                $email = strtolower(trim((string) $student->email));
                if ($email !== '' && !str_ends_with($email, '@ssct.edu.ph')) {
                    DB::table('students')->where('id', $student->id)->update([
                        'email' => strstr($email, '@', true) . '@ssct.edu.ph',
                    ]);
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            DB::table('users')->select('id', 'email')->get()->each(function ($user) {
                $email = strtolower(trim((string) $user->email));
                if ($email !== '' && !str_ends_with($email, '@ssct.edu.ph')) {
                    DB::table('users')->where('id', $user->id)->update([
                        'email' => strstr($email, '@', true) . '@ssct.edu.ph',
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'department_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::dropIfExists('departments');
        }
    }
};

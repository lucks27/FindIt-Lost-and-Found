<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'users'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'email')) {
                continue;
            }

            DB::table($table)->select('id', 'email')->get()->each(function ($record) use ($table) {
                $email = strtolower(trim((string) $record->email));
                if ($email !== '' && !str_ends_with($email, '@ssct.edu.ph')) {
                    DB::table($table)->where('id', $record->id)->update([
                        'email' => strstr($email, '@', true) . '@ssct.edu.ph',
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        // The original domains are not recoverable after normalization.
    }
};

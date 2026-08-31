<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        // Check which columns are missing
        $requiredColumns = ['id', 'actor_admin_id', 'action', 'subject_type', 'subject_id', 'subject_label', 'details', 'ip_address', 'created_at'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('audit_logs', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (empty($missingColumns)) {
            return;
        }

        // For SQLite, we need to recreate the table since it doesn't support adding foreign keys
        if ($driver === 'sqlite') {
            $rows = DB::table('audit_logs')->get()->all();

            Schema::dropIfExists('audit_logs');

            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->string('action');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('subject_label')->nullable();
                $table->json('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });

            // Re-insert the rows
            foreach ($rows as $row) {
                DB::table('audit_logs')->insert((array) $row);
            }
        } else {
            // For other databases, add columns as needed
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'actor_admin_id')) {
                    $table->foreignId('actor_admin_id')->nullable()->constrained('admin')->nullOnDelete()->after('id');
                }
                if (!Schema::hasColumn('audit_logs', 'subject_label')) {
                    $table->string('subject_label')->nullable()->after('subject_id');
                }
                if (!Schema::hasColumn('audit_logs', 'details')) {
                    $table->json('details')->nullable()->after('subject_label');
                }
                if (!Schema::hasColumn('audit_logs', 'created_at')) {
                    $table->timestamps()->after('ip_address');
                }
            });
        }
    }

    public function down(): void
    {
        // Cannot safely down this migration
    }
};

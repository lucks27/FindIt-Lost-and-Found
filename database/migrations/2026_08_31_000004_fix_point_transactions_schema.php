<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('point_transactions')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        // Check which columns are needed by the endpoint
        $requiredColumns = ['student_id', 'amount', 'type', 'description', 'reference_type', 'reference_id', 'created_by_admin_id'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('point_transactions', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (empty($missingColumns)) {
            return;
        }

        // For SQLite, we need to recreate the table since it doesn't support adding foreign keys
        if ($driver === 'sqlite') {
            $rows = DB::table('point_transactions')->get()->all();

            Schema::dropIfExists('point_transactions');

            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('type'); // return_completion, event_fine, etc.
                $table->text('description')->nullable();
                $table->string('reference_type')->nullable(); // App\Models\ItemRelease, etc.
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->foreignId('created_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                
                // Legacy columns for compatibility
                $table->foreignId('completed_return_id')->nullable()->constrained('completed_returns')->nullOnDelete();
                $table->foreignId('event_fine_conversion_id')->nullable()->constrained('event_fine_conversions')->nullOnDelete();
                $table->string('direction')->nullable();
                $table->unsignedInteger('points')->nullable()->default(0);
                $table->unsignedInteger('balance_after')->nullable();
                $table->string('reference_key')->nullable()->unique();
                $table->text('notes')->nullable();
                $table->foreignId('processed_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                
                $table->timestamps();
                $table->index(['student_id', 'created_at']);
            });

            // Re-insert the rows
            foreach ($rows as $row) {
                DB::table('point_transactions')->insert((array) $row);
            }
        } else {
            // For other databases, add columns as needed
            Schema::table('point_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('point_transactions', 'amount')) {
                    $table->decimal('amount', 10, 2)->nullable()->after('student_id');
                }
                if (!Schema::hasColumn('point_transactions', 'description')) {
                    $table->text('description')->nullable()->after('type');
                }
                if (!Schema::hasColumn('point_transactions', 'reference_type')) {
                    $table->string('reference_type')->nullable()->after('reference_key');
                }
                if (!Schema::hasColumn('point_transactions', 'reference_id')) {
                    $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
                }
                if (!Schema::hasColumn('point_transactions', 'created_by_admin_id')) {
                    $table->foreignId('created_by_admin_id')->nullable()->constrained('admin')->nullOnDelete()->after('processed_by_admin_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Cannot safely down this migration
    }
};

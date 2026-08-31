<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('admin') && !Schema::hasColumn('admin', 'profile_picture')) {
            Schema::table('admin', function (Blueprint $table) {
                $table->string('profile_picture')->nullable()->after('role');
            });
        }

        if (Schema::hasTable('surrender_requests')) {
            if (!Schema::hasColumn('surrender_requests', 'student_id') && Schema::hasColumn('surrender_requests', 'finder_student_id')) {
                Schema::table('surrender_requests', function (Blueprint $table) {
                    $table->renameColumn('finder_student_id', 'student_id');
                });
            }

            if (!Schema::hasColumn('surrender_requests', 'student_id')) {
                Schema::table('surrender_requests', function (Blueprint $table) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('item_verifications')) {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $columns = collect(DB::select('PRAGMA table_info(item_verifications)'));
                $surrenderRequestColumn = $columns->firstWhere('name', 'surrender_request_id');
                $checklistColumn = $columns->firstWhere('name', 'checklist');
                $verifierColumn = $columns->firstWhere('name', 'verifier_admin_id');
                $hasRequiredSurrenderRequest = (bool) ($surrenderRequestColumn->notnull ?? false);
            } else {
                $columns = collect(DB::select('DESCRIBE item_verifications'));
                $surrenderRequestColumn = $columns->firstWhere('Field', 'surrender_request_id');
                $checklistColumn = $columns->firstWhere('Field', 'checklist');
                $verifierColumn = $columns->firstWhere('Field', 'verifier_admin_id');
                $hasRequiredSurrenderRequest = (($surrenderRequestColumn->Null ?? 'YES') === 'NO');
            }

            $needsItemVerificationRepair = $hasRequiredSurrenderRequest
                || !$checklistColumn
                || !$verifierColumn;

            if ($needsItemVerificationRepair) {
                $rows = DB::table('item_verifications')->get()->all();

                // Disable foreign key checks before dropping the table
                if ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                }

                Schema::dropIfExists('item_verifications');

                // Re-enable foreign key checks
                if ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }

                Schema::create('item_verifications', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
                    $table->foreignId('surrender_request_id')->nullable()->constrained('surrender_requests')->nullOnDelete();
                    $table->foreignId('verifier_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                    $table->string('status')->default('pending');
                    $table->json('checklist')->nullable();
                    $table->text('notes')->nullable();
                    $table->dateTime('verified_at')->nullable();
                    $table->timestamps();
                });

                foreach ($rows as $row) {
                    DB::table('item_verifications')->insert([
                        'id' => $row->id,
                        'report_id' => $row->report_id,
                        'surrender_request_id' => $row->surrender_request_id ?? null,
                        'verifier_admin_id' => $row->verifier_admin_id ?? $row->verified_by_admin_id ?? null,
                        'status' => $row->status ?? 'pending',
                        'checklist' => $row->checklist ?? null,
                        'notes' => $row->notes ?? null,
                        'verified_at' => $row->verified_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            } else {
                if (!Schema::hasColumn('item_verifications', 'surrender_request_id')) {
                    Schema::table('item_verifications', function (Blueprint $table) {
                        $table->foreignId('surrender_request_id')->nullable()->constrained('surrender_requests')->nullOnDelete();
                    });
                }

                if (!Schema::hasColumn('item_verifications', 'checklist')) {
                    Schema::table('item_verifications', function (Blueprint $table) {
                        $table->json('checklist')->nullable();
                    });
                }

                if (!Schema::hasColumn('item_verifications', 'verifier_admin_id') && Schema::hasColumn('item_verifications', 'verified_by_admin_id')) {
                    Schema::table('item_verifications', function (Blueprint $table) {
                        $table->renameColumn('verified_by_admin_id', 'verifier_admin_id');
                    });
                }

                if (!Schema::hasColumn('item_verifications', 'verifier_admin_id')) {
                    Schema::table('item_verifications', function (Blueprint $table) {
                        $table->foreignId('verifier_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // No-op; this repair is only for compatibility with the active workflow model.
    }
};

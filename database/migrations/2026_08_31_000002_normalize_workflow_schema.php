<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->normalizeReports();
        $this->normalizeNotifications();
        $this->normalizePotentialMatches();
        $this->normalizeSurrenderRequests();
        $this->normalizeItemReleases();
        $this->normalizeItemVerifications();
        $this->normalizePointRules();
        $this->normalizeEventFines();
    }

    protected function normalizeReports(): void
    {
        if (!Schema::hasTable('reports')) {
            return;
        }

        if (!Schema::hasColumn('reports', 'type') && Schema::hasColumn('reports', 'report_type')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('type')->nullable()->after('report_type');
            });
            DB::table('reports')->whereNotNull('report_type')->update(['type' => DB::raw('report_type')]);
        }

        if (!Schema::hasColumn('reports', 'category') && Schema::hasColumn('reports', 'category_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('category')->nullable()->after('category_id');
            });
        }

        foreach (['time_found','metadata','requires_images'] as $column) {
            if (!Schema::hasColumn('reports', $column)) {
                Schema::table('reports', function (Blueprint $table) use ($column) {
                    if ($column === 'metadata') {
                        $table->json('metadata')->nullable();
                    } elseif ($column === 'requires_images') {
                        $table->boolean('requires_images')->default(true);
                    } else {
                        $table->string('time_found')->nullable();
                    }
                });
            }
        }

        if (Schema::hasColumn('reports', 'report_type') && !Schema::hasColumn('reports', 'type')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('type')->nullable()->after('report_type');
            });
            DB::table('reports')->whereNotNull('report_type')->update(['type' => DB::raw('report_type')]);
        }
    }

    protected function normalizeNotifications(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        $rows = DB::table('notifications')->get()->all();

        $hasAllColumns = true;
        foreach (['title', 'message', 'student_id', 'recipient_id', 'recipient_role', 'subject_type', 'subject_id', 'data', 'actor_admin_id', 'is_read', 'action'] as $column) {
            if (!Schema::hasColumn('notifications', $column)) {
                $hasAllColumns = false;
                break;
            }
        }

        if (!$hasAllColumns) {
            Schema::dropIfExists('notifications');

            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('message')->nullable();
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->string('recipient_role')->nullable();
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('data')->nullable();
                $table->foreignId('actor_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->boolean('is_read')->default(false);
                $table->string('action')->nullable();
                $table->timestamps();
            });

            foreach ($rows as $row) {
                DB::table('notifications')->insert([
                    'id' => $row->id,
                    'title' => $row->title ?? null,
                    'message' => $row->message ?? null,
                    'student_id' => $row->student_id ?? $row->recipient_id ?? null,
                    'recipient_id' => $row->recipient_id ?? null,
                    'recipient_role' => $row->recipient_role ?? null,
                    'subject_type' => $row->subject_type ?? null,
                    'subject_id' => $row->subject_id ?? null,
                    'data' => $row->data ?? null,
                    'actor_admin_id' => $row->actor_admin_id ?? null,
                    'is_read' => $row->is_read ?? false,
                    'action' => $row->action ?? null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        } else {
            foreach (['title', 'message', 'student_id', 'recipient_id', 'recipient_role', 'subject_type', 'subject_id', 'data', 'actor_admin_id', 'is_read', 'action'] as $column) {
                if (!Schema::hasColumn('notifications', $column)) {
                    Schema::table('notifications', function (Blueprint $table) use ($column) {
                        switch ($column) {
                            case 'title':
                                $table->string('title')->nullable();
                                break;
                            case 'message':
                                $table->text('message')->nullable();
                                break;
                            case 'student_id':
                                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                                break;
                            case 'recipient_id':
                                $table->unsignedBigInteger('recipient_id')->nullable();
                                break;
                            case 'recipient_role':
                                $table->string('recipient_role')->nullable();
                                break;
                            case 'subject_type':
                                $table->string('subject_type')->nullable();
                                break;
                            case 'subject_id':
                                $table->unsignedBigInteger('subject_id')->nullable();
                                break;
                            case 'data':
                                $table->json('data')->nullable();
                                break;
                            case 'actor_admin_id':
                                $table->foreignId('actor_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                                break;
                            case 'is_read':
                                $table->boolean('is_read')->default(false);
                                break;
                            case 'action':
                                $table->string('action')->nullable();
                                break;
                        }
                    });
                }
            }

            if (Schema::hasColumn('notifications', 'recipient_id') && Schema::hasColumn('notifications', 'student_id')) {
                DB::table('notifications')->whereNull('student_id')->whereNotNull('recipient_id')->update(['student_id' => DB::raw('recipient_id')]);
            }
        }
    }

    protected function normalizePotentialMatches(): void
    {
        if (!Schema::hasTable('potential_matches')) {
            return;
        }

        foreach (['lost_report_id' => 'source_report_id', 'found_report_id' => 'matched_report_id'] as $newColumn => $legacyColumn) {
            if (!Schema::hasColumn('potential_matches', $newColumn) && Schema::hasColumn('potential_matches', $legacyColumn)) {
                Schema::table('potential_matches', function (Blueprint $table) use ($newColumn) {
                    $table->foreignId($newColumn)->nullable()->constrained('reports')->cascadeOnDelete();
                });

                DB::table('potential_matches')->whereNotNull($legacyColumn)->update([$newColumn => DB::raw($legacyColumn)]);
            }
        }
    }

    protected function normalizeSurrenderRequests(): void
    {
        if (!Schema::hasTable('surrender_requests')) {
            return;
        }

        if (!Schema::hasColumn('surrender_requests', 'student_id') && Schema::hasColumn('surrender_requests', 'finder_student_id')) {
            Schema::table('surrender_requests', function (Blueprint $table) {
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            });
            DB::table('surrender_requests')->whereNotNull('finder_student_id')->update(['student_id' => DB::raw('finder_student_id')]);
        }

        if (!Schema::hasColumn('surrender_requests', 'student_id')) {
            Schema::table('surrender_requests', function (Blueprint $table) {
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('surrender_requests', 'deadline_at')) {
            Schema::table('surrender_requests', function (Blueprint $table) {
                $table->timestamp('deadline_at')->nullable();
            });
        }
    }

    protected function normalizeItemReleases(): void
    {
        if (!Schema::hasTable('item_releases')) {
            return;
        }

        $columns = collect(DB::select('PRAGMA table_info(item_releases)'));

        if ($columns->contains(fn($column) => $column->name === 'report_id')) {
            return;
        }

        $rows = DB::table('item_releases')->get()->all();

        Schema::dropIfExists('item_releases');

        Schema::create('item_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('claimant_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
            $table->string('release_code')->unique();
            $table->string('status')->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->json('checklist')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamps();
        });

        foreach ($rows as $row) {
            DB::table('item_releases')->insert([
                'id' => $row->id,
                'report_id' => $row->report_id ?? $row->claim_id ?? null,
                'claimant_student_id' => $row->claimant_student_id ?? $row->inventory_item_id ?? null,
                'approved_by_admin_id' => $row->approved_by_admin_id ?? $row->released_by_admin_id ?? null,
                'release_code' => $row->release_code ?? ('release-' . ($row->id ?? 'legacy')),
                'status' => $row->status ?? 'pending',
                'scheduled_at' => $row->scheduled_at ?? null,
                'released_at' => $row->released_at ?? null,
                'checklist' => $row->checklist ?? null,
                'receipt_number' => $row->receipt_number ?? null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    protected function normalizeItemVerifications(): void
    {
        if (!Schema::hasTable('item_verifications')) {
            return;
        }

        foreach (['surrender_request_id','verifier_admin_id','checklist','notes','verified_at'] as $column) {
            if (!Schema::hasColumn('item_verifications', $column)) {
                Schema::table('item_verifications', function (Blueprint $table) use ($column) {
                    switch ($column) {
                        case 'surrender_request_id':
                            $table->foreignId('surrender_request_id')->nullable()->constrained('surrender_requests')->nullOnDelete();
                            break;
                        case 'verifier_admin_id':
                            $table->foreignId('verifier_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                            break;
                        case 'checklist':
                            $table->json('checklist')->nullable();
                            break;
                        case 'notes':
                            $table->text('notes')->nullable();
                            break;
                        case 'verified_at':
                            $table->timestamp('verified_at')->nullable();
                            break;
                    }
                });
            }
        }
    }

    protected function normalizePointRules(): void
    {
        if (!Schema::hasTable('point_rules')) {
            return;
        }

        if (!Schema::hasColumn('point_rules', 'item_category') && !Schema::hasColumn('point_rules', 'category_id')) {
            Schema::table('point_rules', function (Blueprint $table) {
                $table->string('item_category')->nullable();
            });
        }

        if (!Schema::hasColumn('point_rules', 'base_points')) {
            Schema::table('point_rules', function (Blueprint $table) {
                $table->unsignedInteger('base_points')->default(0);
            });
        }

        if (!Schema::hasColumn('point_rules', 'active') && Schema::hasColumn('point_rules', 'is_active')) {
            Schema::table('point_rules', function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
            DB::table('point_rules')->update(['active' => DB::raw('is_active')]);
        }
    }

    protected function normalizeEventFines(): void
    {
        if (!Schema::hasTable('event_fines')) {
            return;
        }

        foreach (['event_name','reference_number','amount','status','reason','issued_by_admin_id','due_at'] as $column) {
            if (!Schema::hasColumn('event_fines', $column)) {
                Schema::table('event_fines', function (Blueprint $table) use ($column) {
                    switch ($column) {
                        case 'event_name':
                            $table->string('event_name')->nullable();
                            break;
                        case 'reference_number':
                            $table->string('reference_number')->nullable();
                            break;
                        case 'amount':
                            $table->decimal('amount', 10, 2)->default(0);
                            break;
                        case 'status':
                            $table->string('status')->default('open');
                            break;
                        case 'reason':
                            $table->text('reason')->nullable();
                            break;
                        case 'issued_by_admin_id':
                            $table->foreignId('issued_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                            break;
                        case 'due_at':
                            $table->date('due_at')->nullable();
                            break;
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for compatibility migration.
    }
};

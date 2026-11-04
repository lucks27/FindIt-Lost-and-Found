<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the event table or safely complete an earlier interrupted creation.
     * A utf8mb4 VARCHAR(191) unique key is below MySQL's 1000-byte key limit.
     */
    public function up(): void
    {
        $alreadyExists = Schema::hasTable('event_fine_events');

        if (! $alreadyExists) {
            Schema::create('event_fine_events', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 191);
                $table->decimal('fine_amount', 12, 2);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->timestamps();
            });
        } else {
            // MySQL can leave the table behind when adding an index fails.
            // Ensure it is the expected table before changing anything.
            foreach ([
                'id', 'name', 'fine_amount', 'is_active',
                'created_by_admin_id', 'created_at', 'updated_at',
            ] as $column) {
                if (! Schema::hasColumn('event_fine_events', $column)) {
                    throw new RuntimeException(
                        "Existing event_fine_events table is missing {$column}. " .
                        'Stop and inspect the table before proceeding.'
                    );
                }
            }
        }

        // Do not silently truncate user-entered event names.
        if (DB::table('event_fine_events')
            ->whereRaw('CHAR_LENGTH(`name`) > ?', [191])
            ->exists()) {
            throw new RuntimeException(
                'Some existing event names exceed 191 characters. ' .
                'Shorten them after reviewing the data, then rerun the migration.'
            );
        }

        if ($alreadyExists) {
            // Repairs the VARCHAR(255) created by the previously failed run.
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->string('name', 191)->change();
            });
        }

        // An existing duplicate must be reviewed, not automatically deleted.
        if (DB::table('event_fine_events')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException(
                'Duplicate event names exist. Resolve duplicates, then rerun.'
            );
        }

        if (! $this->hasIndexOn('name', true)) {
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->unique('name', 'event_fine_events_name_unique');
            });
        }

        if (! $this->hasIndexOn('is_active')) {
            Schema::table('event_fine_events', function (Blueprint $table): void {
                $table->index('is_active', 'event_fine_events_is_active_index');
            });
        }

        // We intentionally avoid guessing which admin table the current
        // authentication guard uses. The nullable created_by_admin_id field
        // works without adding an unverified foreign-key relationship.
    }

    private function hasIndexOn(string $column, bool $unique = false): bool
    {
        $index = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'event_fine_events')
            ->where('COLUMN_NAME', $column);

        if ($unique) {
            $index->where('NON_UNIQUE', 0);
        }

        return $index->exists();
    }

    public function down(): void
    {
        // Deliberately non-destructive: the migration may have REPAIRED an
        // existing table, and rolling it back must not delete event records.
    }
};

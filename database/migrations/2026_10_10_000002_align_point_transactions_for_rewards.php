<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Preserve historical transactions while aligning with the current ledger. */
    public function up(): void
    {
        if (!Schema::hasTable('point_transactions')) {
            throw new \RuntimeException('Missing point_transactions. Run the initial migrations first.');
        }

        $exists = [];
        foreach (['reference_key', 'reference_type', 'reference_id', 'amount', 'description', 'created_by_admin_id'] as $field) {
            $exists[$field] = Schema::hasColumn('point_transactions', $field);
        }

        Schema::table('point_transactions', function (Blueprint $table) use ($exists) {
            if (!$exists['reference_key']) {
                $table->string('reference_key', 120)->nullable();
            }
            if (!$exists['reference_type']) {
                $table->string('reference_type', 120)->nullable();
            }
            if (!$exists['reference_id']) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }
            if (!$exists['amount']) {
                $table->decimal('amount', 14, 2)->nullable();
            }
            if (!$exists['description']) {
                $table->text('description')->nullable();
            }
            if (!$exists['created_by_admin_id']) {
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
            }
        });

        // Original FindIt schema uses UNSIGNED points. Payments need signed
        // debit rows. Keep all historical values unchanged during conversion.
        if (DB::getDriverName() === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM `point_transactions` LIKE 'points'");
            if ($column && str_contains(strtolower((string) $column->Type), 'unsigned')) {
                Schema::table('point_transactions', function (Blueprint $table) {
                    $table->bigInteger('points')->default(0)->change();
                });
            }
        }

        // Unique report-stage reference prevents duplicate point credits.
        $hasUnique = false;
        foreach (Schema::getIndexes('point_transactions') as $index) {
            if (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['reference_key']) {
                $hasUnique = true;
                break;
            }
        }
        if (!$hasUnique) {
            // A 255-character utf8mb4 unique index can exceed this MySQL
            // server's 1000-byte limit. New keys are short; only shorten an
            // existing column if no historical key would be truncated.
            if (DB::getDriverName() === 'mysql') {
                $tooLong = DB::table('point_transactions')
                    ->whereRaw('CHAR_LENGTH(`reference_key`) > 191')->exists();
                if ($tooLong) {
                    throw new \RuntimeException(
                        'Some historical reference_key values exceed 191 characters; cannot safely shorten for a unique index.'
                    );
                }
                Schema::table('point_transactions', function (Blueprint $table) {
                    $table->string('reference_key', 191)->nullable()->change();
                });
            }
            $duplicate = DB::table('point_transactions')
                ->select('reference_key')->whereNotNull('reference_key')
                ->groupBy('reference_key')->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicate) {
                throw new \RuntimeException('Duplicate reference_key values exist. Resolve them before indexing.');
            }
            Schema::table('point_transactions', function (Blueprint $table) {
                $table->unique('reference_key', 'pt_reward_reference_key_unique');
            });
        }
    }

    /** Deliberately nondestructive: never erase rewards or restore unsigned points. */
    public function down(): void
    {
        // Leave historic point data and compatibility columns in place.
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ResetLostFoundDatabaseCommand extends Command
{
    protected $signature = 'lostfound:reset-database {--force : Skip confirmation prompts and run the reset}';

    protected $description = 'Reset the Lost and Found schema while preserving the admin table and records.';

    public function handle(): int
    {
        $env = app()->environment();

        if (!in_array($env, ['local', 'testing'], true) && !$this->option('force')) {
            $this->error('This command is only allowed in local or testing environments.');
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->warn('This will drop application tables and rebuild the Lost and Found schema.');
            if (!$this->confirm('Continue with the reset?')) {
                $this->info('Reset cancelled.');
                return self::FAILURE;
            }
        }

        $adminTable = 'admin';
        if (!Schema::hasTable($adminTable)) {
            Schema::create($adminTable, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->string('profile_photo')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        $adminCount = DB::table($adminTable)->count();
        $backupPath = storage_path('app/backups/admin-backup-' . now()->format('Ymd_His') . '.json');
        $directory = dirname($backupPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $admins = DB::table($adminTable)->get()->all();
        file_put_contents($backupPath, json_encode($admins, JSON_PRETTY_PRINT));

        $this->info('Backed up administrator records to ' . $backupPath);
        $this->info('Preserving admin table and ' . $adminCount . ' administrator records.');

        $tablesToPreserve = [
            'admin',
            'migrations',
            'users',
            'password_reset_tokens',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'personal_access_tokens',
            'students',
            'reports',
        ];

        $driver = DB::getDriverName();
        $tableRows = match ($driver) {
            'sqlite' => DB::connection()->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"),
            'pgsql' => DB::connection()->select("SELECT tablename AS name FROM pg_catalog.pg_tables WHERE schemaname = 'public'"),
            default => DB::connection()->select('SHOW TABLES'),
        };

        $allTables = array_values(array_filter(array_map(function ($table) {
            if (is_object($table)) {
                return current((array) $table);
            }

            return (string) $table;
        }, $tableRows), function ($tableName) {
            return is_string($tableName) && $tableName !== '';
        }));

        $dropOrder = [
            'audit_logs',
            'announcements',
            'notifications',
            'push_tokens',
            'point_transactions',
            'event_fine_conversions',
            'event_fines',
            'completed_returns',
            'item_releases',
            'claim_status_histories',
            'claim_attachments',
            'claims',
            'potential_matches',
            'inventory_items',
            'item_verifications',
            'surrender_requests',
            'report_images',
            'item_categories',
            'students',
            'reports',
        ];

        $tablesToDrop = array_values(array_filter($allTables, function ($tableName) use ($tablesToPreserve) {
            return $tableName !== ''
                && !in_array($tableName, $tablesToPreserve, true)
                && str_starts_with($tableName, 'legacy_');
        }));

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        if (Schema::hasTable('reports')) {
            DB::table('reports')->delete();
        }
        if (Schema::hasTable('students')) {
            DB::table('students')->delete();
        }

        foreach ($tablesToDrop as $table) {
            $quotedTable = str_replace('"', '""', $table);
            DB::statement('DROP TABLE IF EXISTS "' . $quotedTable . '"');
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Administrator records verified: ' . DB::table($adminTable)->count());

        if (!Admin::query()->exists()) {
            Admin::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@ssct.edu.ph',
                'password' => bcrypt('password'),
                'status' => true,
                'role' => 'super_admin',
            ]);
        }

        return self::SUCCESS;
    }
}

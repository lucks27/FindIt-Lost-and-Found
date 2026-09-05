<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('event_fines')
            ->whereIn('status', ['partial', 'partially_paid'])
            ->update(['status' => 'partially paid']);
    }

    public function down(): void
    {
        DB::table('event_fines')
            ->where('status', 'partially paid')
            ->update(['status' => 'partial']);
    }
};

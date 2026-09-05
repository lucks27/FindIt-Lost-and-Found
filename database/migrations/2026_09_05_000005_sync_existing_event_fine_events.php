<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_fine_events') || !Schema::hasTable('event_fines')) {
            return;
        }

        $existingEvents = DB::table('event_fines')
            ->select('event_name')
            ->selectRaw('MAX(COALESCE(original_amount, amount)) as fine_amount')
            ->whereNotNull('event_name')
            ->where('event_name', '!=', '')
            ->groupBy('event_name')
            ->get();

        foreach ($existingEvents as $existingEvent) {
            $event = DB::table('event_fine_events')
                ->where('name', $existingEvent->event_name)
                ->first();

            if (!$event) {
                $eventId = DB::table('event_fine_events')->insertGetId([
                    'name' => $existingEvent->event_name,
                    'fine_amount' => $existingEvent->fine_amount ?? 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $eventId = $event->id;
            }

            DB::table('event_fines')
                ->where('event_name', $existingEvent->event_name)
                ->whereNull('event_fine_event_id')
                ->update(['event_fine_event_id' => $eventId]);
        }
    }

    public function down(): void
    {
        // Keep synchronized event definitions because they may be used by transactions.
    }
};

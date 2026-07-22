<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §2.5 + §2.9 of the punching fixes.
 *
 * - Adds a first-class `reason` column (previously the tracker's reason was
 *   dropped / stashed in metadata) and backfills it from metadata->reason.
 * - De-duplicates existing (user_id, action, timestamp) rows, then adds a UNIQUE
 *   index so replayed punches are idempotent at the database level.
 *
 * Timezone note (§2.1): existing `timestamp` values are ALREADY stored in UTC by
 * the write path, so this migration deliberately does NOT re-interpret them.
 * Only the day-boundary query logic changes (in code), not the stored data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the reason column.
        if (! Schema::hasColumn('time_tracker_activity_logs', 'reason')) {
            Schema::table('time_tracker_activity_logs', function (Blueprint $table) {
                $table->string('reason', 1000)->nullable()->after('action');
            });
        }

        // 2. Backfill reason from any existing metadata->reason.
        DB::statement("
            UPDATE time_tracker_activity_logs
            SET reason = JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.reason'))
            WHERE reason IS NULL
              AND metadata IS NOT NULL
              AND JSON_EXTRACT(metadata, '$.reason') IS NOT NULL
        ");

        // 3. Remove duplicate (user_id, action, timestamp) rows, keeping the lowest id,
        //    so the UNIQUE index below can be created.
        DB::statement('
            DELETE t1 FROM time_tracker_activity_logs t1
            INNER JOIN time_tracker_activity_logs t2
              ON  t1.user_id   = t2.user_id
              AND t1.action    = t2.action
              AND t1.timestamp = t2.timestamp
              AND t1.id        > t2.id
        ');

        // 4. Add the unique index (idempotent-write guard).
        Schema::table('time_tracker_activity_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'action', 'timestamp'], 'ttal_user_action_ts_unique');
        });
    }

    public function down(): void
    {
        Schema::table('time_tracker_activity_logs', function (Blueprint $table) {
            $table->dropUnique('ttal_user_action_ts_unique');
        });

        if (Schema::hasColumn('time_tracker_activity_logs', 'reason')) {
            Schema::table('time_tracker_activity_logs', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};

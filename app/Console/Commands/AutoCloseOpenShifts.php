<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Plugins\TimeTracker\Concerns\HandlesTimeTrackerLogs;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;

/**
 * §2.8 — Close time-tracker shifts that were never clocked out (machine crash /
 * power loss). Runs nightly and clocks out any shift left open on a PREVIOUS day,
 * at a configurable cutoff in the workspace timezone, so hours don't accumulate
 * forever. Idempotent: it only ever writes one clock-out per open shift.
 */
class AutoCloseOpenShifts extends Command
{
    use HandlesTimeTrackerLogs;

    protected $signature = 'timetracker:auto-close-shifts {--cutoff= : Time of day (HH:MM:SS) in the workspace timezone to stamp the clock-out; defaults to end of day}';

    protected $description = 'Auto-close time-tracker shifts left open on a previous day.';

    public function handle(): int
    {
        $tz = $this->workspaceTimezone();
        $cutoff = $this->option('cutoff')
            ?: config('timetracker.auto_close_time', '23:59:59');

        $todayStartLocal = Carbon::now($tz)->startOfDay();
        $closed = 0;

        // Users who have ever logged activity.
        $userIds = TimeTrackerActivityLog::distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $last = TimeTrackerActivityLog::where('user_id', $userId)
                ->orderBy('timestamp', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            // Skip: no logs, or the shift is already closed.
            if (! $last || $last->action === 'clock-out') {
                continue;
            }

            $lastLocal = Carbon::parse($last->timestamp)->setTimezone($tz);

            // Leave today's shift alone — the employee may still be working.
            if ($lastLocal->greaterThanOrEqualTo($todayStartLocal)) {
                continue;
            }

            // Close at the cutoff on the open shift's own day (workspace zone) -> UTC.
            $closeAtLocal = $lastLocal->copy()->setTimeFromTimeString($cutoff);
            $closeAtUtc = $closeAtLocal->copy()->utc();

            // Never stamp before the last real event.
            $lastUtc = Carbon::parse($last->timestamp);
            if ($closeAtUtc->lessThanOrEqualTo($lastUtc)) {
                $closeAtUtc = $lastUtc->copy()->addSecond();
            }

            $now = now();
            $inserted = TimeTrackerActivityLog::insertOrIgnore([
                'user_id'    => $userId,
                'action'     => 'clock-out',
                'reason'     => 'Auto-closed: no clock-out received',
                'timestamp'  => $closeAtUtc->format('Y-m-d H:i:s'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted > 0) {
                $closed++;
                $this->line("Auto-closed shift for user {$userId} at {$closeAtUtc->format('Y-m-d H:i:s')} UTC.");
            }
        }

        $this->info("Auto-close complete. {$closed} shift(s) closed.");

        return self::SUCCESS;
    }
}

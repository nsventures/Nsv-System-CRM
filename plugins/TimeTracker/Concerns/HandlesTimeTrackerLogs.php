<?php

namespace Plugins\TimeTracker\Concerns;

use Carbon\Carbon;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;

/**
 * Shared time-tracker punch logic: timezone normalization, the force-clockout
 * gate, and state-machine validation. Kept in one place so the /log-update and
 * /upload-screenshot endpoints (and forceClockout / the auto-close job) enforce
 * exactly the same rules.
 *
 * Timestamps are STORED in UTC. The desktop client sends a naive "Y-m-d H:i:s"
 * string in the employee's OS timezone (no offset), so we interpret it in the
 * workspace timezone and do ALL day-boundary / "now" math in that zone.
 */
trait HandlesTimeTrackerLogs
{
    /**
     * Activity actions that only make sense while an employee is clocked in.
     * These are the ones the force-clockout gate applies to. clock-in / clock-out
     * are intentionally excluded (clock-in starts a shift; clock-out is idempotent).
     */
    protected function activityActions(): array
    {
        return [
            'break-start', 'break-stop',
            'idle-start', 'idle-stop',
            'manual-processing-start', 'manual-processing-stop',
        ];
    }

    /**
     * The configured workspace/display timezone. Timestamps are stored in UTC;
     * this zone is used to interpret incoming naive strings and to compute day
     * boundaries so they match what the employee (and the timeline) actually see.
     */
    protected function workspaceTimezone(): string
    {
        $settings = function_exists('get_settings') ? get_settings('general_settings') : null;
        $tz = is_array($settings) ? ($settings['timezone'] ?? null) : null;

        return $tz ?: 'Asia/Kolkata';
    }

    /**
     * Parse a naive "Y-m-d H:i:s" client timestamp (expressed in $tz) into a UTC
     * Carbon instance. Falls back to lenient parsing so an ISO-8601 string with an
     * explicit offset (the durable client fix) is honoured verbatim if it ever
     * arrives.
     */
    protected function parseClientTimestamp(?string $raw, string $tz): Carbon
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return Carbon::now('UTC');
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $raw, $tz)->utc();
        } catch (\Throwable $e) {
            // ISO-8601 with offset, or any other shape Carbon understands.
            return Carbon::parse($raw, $tz)->utc();
        }
    }

    /**
     * The most recent activity log for a user AT OR BEFORE $at (UTC), within the
     * workspace-timezone calendar day that contains $at.
     *
     * Evaluating relative to the punch timestamp (not "now") is what stops offline
     * replay from being wrongly rejected: a backfilled punch is judged against the
     * state as it was when that punch happened.
     *
     * @param  bool  $lock  SELECT ... FOR UPDATE (only valid inside a transaction).
     */
    protected function lastLogAtOrBefore(int $userId, Carbon $at, string $tz, bool $lock = false)
    {
        $localDay = $at->copy()->setTimezone($tz);
        $dayStart = $localDay->copy()->startOfDay()->utc();
        $dayEnd = $localDay->copy()->endOfDay()->utc();

        $query = TimeTrackerActivityLog::where('user_id', $userId)
            ->whereBetween('timestamp', [$dayStart, $dayEnd])
            ->where('timestamp', '<=', $at)
            ->orderBy('timestamp', 'desc')
            ->orderBy('id', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * The force-clockout gate: true ONLY when the user's visible state is an explicit
     * clock-out (voluntary or admin force-clockout).
     *
     * A null $lastLog — no event visible at/before the punch — must NOT trigger a
     * force-clockout. That happens routinely for benign reasons (clock skew hiding a
     * clock-in whose stored timestamp is a second after this punch, a screenshot with
     * no client timestamp falling back to server-now, or offline replay ordering), and
     * treating it as "clocked out" fired a spurious 403 seconds after a real clock-in.
     * The genuine "activity with no clock-in at all" case is handled by the state
     * machine as a harmless no-op (isValidTransition returns false), not a force-clockout.
     */
    protected function isClockedOut($lastLog): bool
    {
        return $lastLog && $lastLog->action === 'clock-out';
    }

    /**
     * Validate a state transition against the last log. Returns true if the punch
     * should be stored, false if it is redundant / out-of-order and must be a
     * no-op. Only called after the force-clockout gate has passed.
     */
    protected function isValidTransition($lastLog, string $action): bool
    {
        $last = $lastLog->action ?? null;

        switch ($action) {
            case 'clock-in':
                // Only when starting fresh or resuming after a clock-out.
                return $last === null || $last === 'clock-out';

            case 'clock-out':
                // Only when currently on an open shift (idempotent otherwise).
                return $last !== null && $last !== 'clock-out';

            case 'break-start':
            case 'idle-start':
            case 'manual-processing-start':
                // Must be clocked in and not already in this exact state.
                return $last !== null && $last !== 'clock-out' && $last !== $action;

            case 'break-stop':
            case 'idle-stop':
            case 'manual-processing-stop':
                // The matching *-start must be the currently open state.
                return $last === str_replace('-stop', '-start', $action);
        }

        return false;
    }
}

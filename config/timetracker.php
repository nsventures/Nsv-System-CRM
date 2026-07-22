<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Force-clockout screenshot gate
    |--------------------------------------------------------------------------
    | When true, /upload-screenshot enforces the force-clockout gate (rejects
    | screenshots for a clocked-out user with 403 FORCE_CLOCKOUT). Set the env
    | var TIMETRACKER_SCREENSHOT_GATE=false for an instant unblock if it ever
    | misfires — no redeploy needed (run `php artisan config:cache` after).
    */
    'screenshot_gate' => env('TIMETRACKER_SCREENSHOT_GATE', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-close cutoff
    |--------------------------------------------------------------------------
    | Time of day (workspace timezone) at which the nightly job stamps the
    | clock-out for shifts left open on a previous day.
    */
    'auto_close_time' => env('TIMETRACKER_AUTO_CLOSE_TIME', '23:59:59'),
];

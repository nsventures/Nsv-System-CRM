<?php

namespace Plugins\TimeTracker\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Plugins\TimeTracker\Models\Screenshot;
use Plugins\TimeTracker\Models\TimeTrackerConfig;

class CleanupScreenshots extends Command
{
    protected $signature = 'timetracker:cleanup-screenshots';
    protected $description = 'Clean up old screenshots captured by the Time Tracker based on retention settings';

    public function handle()
    {
        $days = TimeTrackerConfig::where('name', 'time_tracker_config')->value('value')['auto_delete_screenshots_after_days'] ?? 30; // Default to 30 days if not set
        Log::info("Starting screenshot cleanup for screenshots older than {$days} days...");
        $this->info("Cleaning up screenshots older than {$days} days...");

        $cutoffDate = Carbon::now()->subDays($days);
        $screenshots = Screenshot::where('captured_at', '<', $cutoffDate)->get();
        $deletedCount = 0;

        foreach ($screenshots as $screenshot) {
            if ($screenshot->screenshot_path && Storage::exists($screenshot->screenshot_path)) {
                $this->info("Deleting screenshot: {$screenshot->screenshot_path}");
                Storage::delete($screenshot->screenshot_path);
            }
            $screenshot->delete();
            $deletedCount++;
        }

        Log::info("TimeTracker: Screenshot cleanup completed. {$deletedCount} screenshots deleted.");
        $this->info("Screenshot cleanup completed. {$deletedCount} screenshots deleted.");

        $this->info('Screenshot cleanup completed.');
    }
}

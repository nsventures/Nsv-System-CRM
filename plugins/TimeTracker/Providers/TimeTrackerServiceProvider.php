<?php

namespace Plugins\TimeTracker\Providers;

use Plugins\TimeTracker\Middleware\IsDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Plugins\TimeTracker\Console\CleanupScreenshots;
use Plugins\TimeTracker\Console\SeedActivityLogs;

class TimeTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'timetracker');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'timetracker');
        // Register your plugin middleware alias
        $this->app['router']->aliasMiddleware(
            'isDevice',
            IsDevice::class
        );


        $this->publishes([
            __DIR__ . '/../public/js' => public_path('assets/js/timetracker-plugin'),
        ], ['timetracker-assets', 'public']);

        $this->publishes([
            __DIR__ . '/../public/css' => public_path('assets/css/timetracker'),
        ], ['timetracker-assets', 'public']);

        $this->publishes([
            __DIR__ . '/../docs/scribe.php' => config_path('scribe_timetracker.php'),
        ], ['timetracker-config', 'config']);

        // Optional logging for plugin version on load
        if (file_exists(__DIR__ . '/../plugin.json')) {
            $pluginJson = json_decode(file_get_contents(__DIR__ . '/../plugin.json'), true);
            Log::info('✅ TimeTracker Plugin Loaded - Version: ' . ($pluginJson['version'] ?? 'unknown'));
        }

        // Attach plugin's scheduled task cleanly
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('timetracker:cleanup-screenshots')->dailyAt('00:00')->withoutOverlapping()->onFailure(function () {
                Log::error('TimeTracker Cleanup Screenshots command failed.');
            });
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../docs/scribe.php',
            'scribe_timetracker'
        );

        $this->commands([
            CleanupScreenshots::class,
            SeedActivityLogs::class,
        ]);
    }
}

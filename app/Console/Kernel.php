<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();

        $schedule->command('send:wishes')->daily();
        // $schedule->command('send:wishes')->everyMinute();

        // Remider Task

         $schedule->command('reminders:send')->everyMinute();
        // Recursion Task

        $schedule->command('recurring-tasks:generate')->daily()->at('00:00')->withoutOverlapping();

        // reset Database
        $schedule->command('demo:reset')->everyTwoHours();

        // Send Scheduled Emails
        $schedule->command('emails:send-scheduled')->everyMinute();

        // Initialize leave balances for new company year
        // Checks daily if today is the company year start date
        $schedule->call(function () {
            $settings = get_settings('general_settings');
            $startMonth = $settings['company_year_start_month'] ?? 1;
            $startDay = $settings['company_year_start_day'] ?? 1;

            $today = \Carbon\Carbon::today();

            // Check if today is the company year start date
            if ($today->month == $startMonth && $today->day == $startDay) {
                Artisan::call('leaves:initialize-balances');
                Log::info('Company year started - Leave balances initialized for new year');
            }
        })->daily()->at('00:05'); // Run at 00:05 AM to avoid conflicts with other midnight jobs
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

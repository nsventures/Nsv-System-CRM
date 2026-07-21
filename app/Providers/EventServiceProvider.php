<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\LeaveRequestApproved::class => [
            \App\Listeners\UpdateLeaveBalanceOnApproval::class,
        ],
        \App\Events\LeaveRequestRejected::class => [
            \App\Listeners\RestoreLeaveBalanceOnRejection::class,
        ],
        \App\Events\LeaveRequestCancelled::class => [
            \App\Listeners\RestoreLeaveBalanceOnCancellation::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

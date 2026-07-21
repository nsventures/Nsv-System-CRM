<?php

namespace App\Events;

use App\Models\UserLeaveBalance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeaveBalanceUpdated Event
 *
 * Fired when a leave balance is updated.
 * Used for audit/logging purposes.
 */
class LeaveBalanceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserLeaveBalance $balance;
    public array $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(UserLeaveBalance $balance, array $changes = [])
    {
        $this->balance = $balance;
        $this->changes = $changes;
    }
}


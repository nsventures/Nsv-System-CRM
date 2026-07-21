<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeaveRequestCancelled Event
 *
 * Fired when a leave request is cancelled/deleted.
 * If it was approved, this triggers balance restoration.
 */
class LeaveRequestCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public LeaveRequest $leaveRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }
}


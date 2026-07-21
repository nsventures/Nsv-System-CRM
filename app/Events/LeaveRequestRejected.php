<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeaveRequestRejected Event
 *
 * Fired when a leave request is rejected.
 * If it was previously approved, this triggers balance restoration.
 */
class LeaveRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public LeaveRequest $leaveRequest;
    public ?string $previousStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(LeaveRequest $leaveRequest, ?string $previousStatus = null)
    {
        $this->leaveRequest = $leaveRequest;
        $this->previousStatus = $previousStatus;
    }
}


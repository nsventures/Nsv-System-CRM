<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeaveRequestApproved Event
 *
 * Fired when a leave request is approved.
 * This triggers balance update processing.
 */
class LeaveRequestApproved
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


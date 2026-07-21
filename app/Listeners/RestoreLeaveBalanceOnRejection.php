<?php

namespace App\Listeners;

use App\Events\LeaveRequestRejected;
use App\Services\LeaveBalanceEngine;
use Illuminate\Support\Facades\Log;

/**
 * RestoreLeaveBalanceOnRejection Listener
 *
 * Handles balance restoration when a leave request is rejected.
 * Only restores if the leave was previously approved and paid.
 */
class RestoreLeaveBalanceOnRejection
{
    protected LeaveBalanceEngine $balanceEngine;

    /**
     * Create the event listener.
     */
    public function __construct(LeaveBalanceEngine $balanceEngine)
    {
        $this->balanceEngine = $balanceEngine;
    }

    /**
     * Handle the event.
     */
    public function handle(LeaveRequestRejected $event): void
    {
        try {
            // Only restore if it was previously approved
            if ($event->previousStatus === 'approved') {
                $this->balanceEngine->restoreBalance(
                    $event->leaveRequest->user_id,
                    $event->leaveRequest->workspace_id,
                    $event->leaveRequest
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to restore balance on leave rejection', [
                'leave_request_id' => $event->leaveRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


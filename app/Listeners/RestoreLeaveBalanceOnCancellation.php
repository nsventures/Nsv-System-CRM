<?php

namespace App\Listeners;

use App\Events\LeaveRequestCancelled;
use App\Services\LeaveBalanceEngine;
use Illuminate\Support\Facades\Log;

/**
 * RestoreLeaveBalanceOnCancellation Listener
 *
 * Handles balance restoration when a leave request is cancelled/deleted.
 * Only restores if the leave was approved and paid.
 */
class RestoreLeaveBalanceOnCancellation
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
    public function handle(LeaveRequestCancelled $event): void
    {
        try {
            // Only restore if it was approved and paid
            if ($event->leaveRequest->status === 'approved' &&
                $event->leaveRequest->is_paid &&
                $event->leaveRequest->paid_days > 0) {
                $this->balanceEngine->restoreBalance(
                    $event->leaveRequest->user_id,
                    $event->leaveRequest->workspace_id,
                    $event->leaveRequest
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to restore balance on leave cancellation', [
                'leave_request_id' => $event->leaveRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


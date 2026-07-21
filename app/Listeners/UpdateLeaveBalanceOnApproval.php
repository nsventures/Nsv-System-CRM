<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Services\LeaveBalanceEngine;
use Illuminate\Support\Facades\Log;

/**
 * UpdateLeaveBalanceOnApproval Listener
 *
 * Handles balance update when a leave request is approved.
 * Executes synchronously (no queue required).
 */
class UpdateLeaveBalanceOnApproval
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
    public function handle(LeaveRequestApproved $event): void
    {
        Log::info('[UpdateLeaveBalanceOnApproval] Event received', [
            'leave_request_id' => $event->leaveRequest->id,
            'user_id' => $event->leaveRequest->user_id,
            'workspace_id' => $event->leaveRequest->workspace_id,
            'status' => $event->leaveRequest->status,
            'is_paid' => $event->leaveRequest->is_paid,
            'paid_days' => $event->leaveRequest->paid_days,
            'unpaid_days' => $event->leaveRequest->unpaid_days,
            'previous_status' => $event->previousStatus,
        ]);

        try {
            // Execute balance update synchronously (no queue needed)
            Log::info('[UpdateLeaveBalanceOnApproval] Calling updateBalance', [
                'leave_request_id' => $event->leaveRequest->id,
                'user_id' => $event->leaveRequest->user_id,
                'workspace_id' => $event->leaveRequest->workspace_id,
            ]);

            $result = $this->balanceEngine->updateBalance(
                $event->leaveRequest->user_id,
                $event->leaveRequest->workspace_id,
                $event->leaveRequest
            );

            Log::info('[UpdateLeaveBalanceOnApproval] Balance update completed', [
                'leave_request_id' => $event->leaveRequest->id,
                'balance_id' => $result?->id,
                'used_paid_leaves' => $result?->used_paid_leaves,
                'remaining_paid_leaves' => $result?->remaining_paid_leaves,
            ]);
        } catch (\Exception $e) {
            Log::error('[UpdateLeaveBalanceOnApproval] Failed to update balance', [
                'leave_request_id' => $event->leaveRequest->id,
                'user_id' => $event->leaveRequest->user_id,
                'workspace_id' => $event->leaveRequest->workspace_id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'previous_exception' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine(),
                ] : null,
            ]);
            // Re-throw to ensure error is visible
            throw $e;
        }
    }
}


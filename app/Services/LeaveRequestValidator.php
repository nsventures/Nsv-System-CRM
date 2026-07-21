<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveRequestValidator
 *
 * Centralized validation logic for leave requests.
 * Handles overlap detection, balance checks, date validation, and status transitions.
 *
 * This service is used in Flow 1: Leave Request Approval
 */
class LeaveRequestValidator
{
    protected LeaveCalculationService $calculationService;

    public function __construct(LeaveCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Validate leave request dates and times
     *
     * @param string|Carbon $fromDate
     * @param string|Carbon $toDate
     * @param string|null $fromTime
     * @param string|null $toTime
     * @param bool $isApi Whether this is an API request (affects date format)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateDates(
        $fromDate,
        $toDate,
        ?string $fromTime = null,
        ?string $toTime = null,
        bool $isApi = false
    ): array {
        $errors = [];

        // Validate date format and order
        $dateErrors = validate_date_format_and_order(
            $fromDate,
            $toDate,
            $isApi ? 'Y-m-d' : null,
            'from date',
            'to date',
            'from_date',
            'to_date'
        );

        if (!empty($dateErrors)) {
            $errors = array_merge($errors, $dateErrors);
        }

        // Validate times if partial leave
        if (($fromTime || $toTime) && (!$fromTime || !$toTime)) {
            $errors['from_time'] = ['Both from_time and to_time are required for partial leave.'];
            $errors['to_time'] = ['Both from_time and to_time are required for partial leave.'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check for overlapping approved leaves
     *
     * This is a critical validation - prevents double-counting in balance calculations.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param string|Carbon $fromDate
     * @param string|Carbon $toDate
     * @param string|null $fromTime
     * @param string|null $toTime
     * @param int|null $excludeLeaveId Leave ID to exclude from check (for edit scenarios)
     * @return array ['has_overlap' => bool, 'overlapping_leaves' => array, 'overlap_days' => float]
     */
    public function validateLeaveOverlap(
        int $userId,
        int $workspaceId,
        $fromDate,
        $toDate,
        ?string $fromTime = null,
        ?string $toTime = null,
        ?int $excludeLeaveId = null
    ): array {
        $fromDate = Carbon::parse($fromDate);
        $toDate = Carbon::parse($toDate);

        // Query for overlapping approved leaves
        $query = LeaveRequest::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'approved')
            ->where(function ($q) use ($fromDate, $toDate) {
                // Overlap condition: leave starts before or on our end date AND ends after or on our start date
                $q->where('from_date', '<=', $toDate->toDateString())
                    ->where('to_date', '>=', $fromDate->toDateString());
            });

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $overlappingLeaves = $query->get();

        if ($overlappingLeaves->isEmpty()) {
            return [
                'has_overlap' => false,
                'overlapping_leaves' => [],
                'overlap_days' => 0,
            ];
        }

        // Calculate overlap days for each overlapping leave
        $overlapDetails = [];
        $totalOverlapDays = 0;

        foreach ($overlappingLeaves as $overlappingLeave) {
            $overlapStart = max($fromDate, Carbon::parse($overlappingLeave->from_date));
            $overlapEnd = min($toDate, Carbon::parse($overlappingLeave->to_date));

            if ($overlapStart->lessThanOrEqualTo($overlapEnd)) {
                // Calculate overlap days
                if ($fromTime && $toTime && $overlappingLeave->from_time && $overlappingLeave->to_time) {
                    // Both are partial leaves - calculate hour overlap
                    $overlapDays = $this->calculatePartialOverlap(
                        $overlapStart,
                        $overlapEnd,
                        $fromTime,
                        $toTime,
                        $overlappingLeave->from_time,
                        $overlappingLeave->to_time
                    );
                } else {
                    // At least one is full-day - calculate day overlap
                    $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
                }

                $overlapDetails[] = [
                    'leave_id' => $overlappingLeave->id,
                    'from_date' => $overlappingLeave->from_date,
                    'to_date' => $overlappingLeave->to_date,
                    'overlap_start' => $overlapStart->toDateString(),
                    'overlap_end' => $overlapEnd->toDateString(),
                    'overlap_days' => $overlapDays,
                ];

                $totalOverlapDays += $overlapDays;
            }
        }

        return [
            'has_overlap' => true,
            'overlapping_leaves' => $overlapDetails,
            'overlap_days' => $totalOverlapDays,
        ];
    }

    /**
     * Calculate overlap for partial leaves (with times)
     *
     * @param Carbon $overlapStart
     * @param Carbon $overlapEnd
     * @param string $fromTime1
     * @param string $toTime1
     * @param string $fromTime2
     * @param string $toTime2
     * @return float Overlap in days
     */
    protected function calculatePartialOverlap(
        Carbon $overlapStart,
        Carbon $overlapEnd,
        string $fromTime1,
        string $toTime1,
        string $fromTime2,
        string $toTime2
    ): float {
        $totalHours = 0;
        $currentDate = $overlapStart->copy();

        while ($currentDate->lessThanOrEqualTo($overlapEnd)) {
            $time1Start = Carbon::parse($currentDate->toDateString() . ' ' . $fromTime1);
            $time1End = Carbon::parse($currentDate->toDateString() . ' ' . $toTime1);
            $time2Start = Carbon::parse($currentDate->toDateString() . ' ' . $fromTime2);
            $time2End = Carbon::parse($currentDate->toDateString() . ' ' . $toTime2);

            // Calculate overlap hours for this day
            $overlapStartTime = max($time1Start, $time2Start);
            $overlapEndTime = min($time1End, $time2End);

            if ($overlapStartTime->lessThan($overlapEndTime)) {
                $totalHours += $overlapStartTime->diffInMinutes($overlapEndTime) / 60;
            }

            $currentDate->addDay();
        }

        // Convert hours to days
        return $totalHours < 8 ? 0.5 : round($totalHours / 8, 2);
    }

    /**
     * Check if user has sufficient balance for leave request
     *
     * @param int $userId
     * @param int $workspaceId
     * @param float $requestedDays
     * @param int|null $year Company year
     * @param int|null $excludeLeaveId Leave ID to exclude (for edit scenarios)
     * @return array ['sufficient' => bool, 'available' => float, 'requested' => float, 'shortfall' => float]
     */
    public function checkBalanceSufficiency(
        int $userId,
        int $workspaceId,
        float $requestedDays,
        ?int $year = null,
        ?int $excludeLeaveId = null
    ): array {
        if ($year === null) {
            $year = get_current_company_year();
        }

        $balanceService = app(LeaveBalanceEngine::class);
        $balance = $balanceService->getOrCreateBalance($userId, $workspaceId, $year);

        // Get available balance (excluding current leave if editing)
        $available = $this->calculationService->getAvailableBalance($userId, $workspaceId, $year, $balance);

        // If excluding a leave, add back its paid days
        if ($excludeLeaveId) {
            $excludedLeave = LeaveRequest::find($excludeLeaveId);
            if ($excludedLeave && $excludedLeave->is_paid) {
                $available += (float) ($excludedLeave->paid_days ?? 0);
            }
        }

        $sufficient = $available >= $requestedDays;
        $shortfall = $sufficient ? 0 : ($requestedDays - $available);

        return [
            'sufficient' => $sufficient,
            'available' => $available,
            'requested' => $requestedDays,
            'shortfall' => $shortfall,
        ];
    }

    /**
     * Validate status transition
     *
     * Ensures only valid status transitions are allowed.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @param bool $isAdmin Whether user is admin
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus, bool $isAdmin): array
    {
        // Cannot set to pending if already approved or rejected
        if (in_array($currentStatus, ['approved', 'rejected']) && $newStatus == 'pending') {
            return [
                'valid' => false,
                'error' => 'You cannot set the status to pending if it has already been approved or rejected.',
            ];
        }

        // Users cannot approve their own leave
        if ($currentStatus != 'approved' && $newStatus == 'approved' && !$isAdmin) {
            return [
                'valid' => false,
                'error' => 'You cannot approve your own leave request.',
            ];
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    /**
     * Log overlap detection for audit purposes
     *
     * @param int $leaveRequestId
     * @param array $overlapDetails Result from validateLeaveOverlap
     * @param string $actionTaken 'blocked', 'warned', or 'allowed'
     * @param int|null $detectedBy User ID who detected/processed the overlap
     * @return void
     */
    public function logOverlap(
        int $leaveRequestId,
        array $overlapDetails,
        string $actionTaken = 'warned',
        ?int $detectedBy = null
    ): void {
        foreach ($overlapDetails['overlapping_leaves'] as $overlap) {
            try {
                DB::table('leave_overlap_logs')->insert([
                    'leave_request_id' => $leaveRequestId,
                    'overlapping_with_id' => $overlap['leave_id'],
                    'overlap_start_date' => $overlap['overlap_start'],
                    'overlap_end_date' => $overlap['overlap_end'],
                    'overlap_days' => $overlap['overlap_days'],
                    'action_taken' => $actionTaken,
                    'detected_by' => $detectedBy,
                    'detected_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to log leave overlap', [
                    'leave_request_id' => $leaveRequestId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}


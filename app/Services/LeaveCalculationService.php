<?php

namespace App\Services;

use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveRequest;
use App\Models\UserLeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveCalculationService
 *
 * Single source of truth for all leave calculations across the system.
 * All leave-related math must go through this service to ensure consistency.
 *
 * This service implements:
 * - Flow 1: Leave Request calculations (paid/unpaid days)
 * - Flow 2: Balance calculations (accrual, remaining)
 * - Flow 3: Payslip baseline calculations (LOP)
 */
class LeaveCalculationService
{
    /**
     * Calculate total leave days from date range
     *
     * Handles both full-day and partial-day (half-day) leaves.
     * Partial leaves are calculated based on hours (8 hours = 1 day).
     *
     * @param string|Carbon $fromDate Start date
     * @param string|Carbon $toDate End date
     * @param string|null $fromTime Start time (HH:MM format) for partial leave
     * @param string|null $toTime End time (HH:MM format) for partial leave
     * @return float Total days (can be fractional, e.g., 0.5 for half-day)
     */
    public function calculateLeaveDays($fromDate, $toDate, $fromTime = null, $toTime = null): float
    {
        $fromDate = Carbon::parse($fromDate);
        $toDate = Carbon::parse($toDate);

        // If times are specified, it's a partial leave
        if ($fromTime && $toTime) {
            $duration = 0;
            $currentDate = $fromDate->copy();

            // Loop through each day in the range
            while ($currentDate->lessThanOrEqualTo($toDate)) {
                // Create Carbon instances for the start and end times of the leave request for the current day
                $fromDateTime = Carbon::parse($currentDate->toDateString() . ' ' . $fromTime);
                $toDateTime = Carbon::parse($currentDate->toDateString() . ' ' . $toTime);

                // Calculate the duration for the current day and add it to the total duration
                $duration += $fromDateTime->diffInMinutes($toDateTime) / 60; // Duration in hours

                // Move to the next day
                $currentDate->addDay();
            }

            // Convert hours to days (assuming 8 hours = 1 day)
            // If duration is less than 8 hours, count as 0.5 day
            return $duration < 8 ? 0.5 : round($duration / 8, 2);
        } else {
            // Calculate the inclusive duration in days (full-day leave)
            return $fromDate->diffInDays($toDate) + 1;
        }
    }

    /**
     * Calculate paid and unpaid days based on available balance
     *
     * This is used in Flow 1: Leave Request Approval
     * Determines how many days can be paid vs unpaid based on current balance.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param float $totalDays Total days requested
     * @param int|null $year Company year (defaults to current)
     * @param UserLeaveBalance|null $balance Pre-fetched balance (optional, for performance)
     * @return array ['paid_days' => float, 'unpaid_days' => float]
     */
    public function calculatePaidUnpaidDays(
        int $userId,
        int $workspaceId,
        float $totalDays,
        ?int $year = null,
        ?UserLeaveBalance $balance = null
    ): array {
        if ($year === null) {
            $year = get_current_company_year();
        }

        // Get available balance
        $availableBalance = $this->getAvailableBalance($userId, $workspaceId, $year, $balance);

        if ($availableBalance >= $totalDays) {
            // All days can be paid
            return [
                'paid_days' => $totalDays,
                'unpaid_days' => 0,
            ];
        } elseif ($availableBalance > 0) {
            // Partial paid, rest unpaid
            return [
                'paid_days' => $availableBalance,
                'unpaid_days' => $totalDays - $availableBalance,
            ];
        } else {
            // All unpaid
            return [
                'paid_days' => 0,
                'unpaid_days' => $totalDays,
            ];
        }
    }

    /**
     * Get available balance for a user
     *
     * This considers:
     * - Accrued leaves (if monthly accrual) or total annual leaves (if lumpsum)
     * - Used paid leaves
     * - Advance paid leaves (negative balance)
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int|null $year Company year
     * @param UserLeaveBalance|null $balance Pre-fetched balance
     * @return float Available balance (can be negative if advance leaves exist)
     */
    public function getAvailableBalance(
        int $userId,
        int $workspaceId,
        ?int $year = null,
        ?UserLeaveBalance $balance = null
    ): float {
        if ($year === null) {
            $year = get_current_company_year();
        }

        if ($balance === null) {
            $balanceService = app(LeaveBalanceEngine::class);
            $balance = $balanceService->getOrCreateBalance($userId, $workspaceId, $year);
        }

        // Get effective total (accrued if monthly accrual, otherwise annual total)
        $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;

        // Available = effective total - used - advance
        // This can be negative if advance leaves exist
        $available = (float) $effectiveTotal - (float) $balance->used_paid_leaves - (float) ($balance->advanced_paid_leaves ?? 0);

        return max(0, $available); // Return non-negative for balance checks
    }

    /**
     * Calculate accrued leaves based on accrual type
     *
     * This is used in Flow 2: Balance Calculation
     *
     * @param int $monthsWorked Number of complete months worked
     * @param float $totalAnnualLeaves Total annual allocation
     * @param bool $isMonthlyAccrual Whether monthly accrual is enabled
     * @param float|null $monthlyRate Custom monthly rate (optional)
     * @return float Accrued leaves
     */
    public function calculateAccruedLeaves(
        int $monthsWorked,
        float $totalAnnualLeaves,
        bool $isMonthlyAccrual,
        ?float $monthlyRate = null
    ): float {
        if (!$isMonthlyAccrual) {
            // Lumpsum: return full allocation
            return $totalAnnualLeaves;
        }

        // Monthly accrual: calculate based on months worked
        if ($monthlyRate === null) {
            $monthlyRate = round($totalAnnualLeaves / 12, 2);
        }

        $accrued = round($monthsWorked * $monthlyRate, 2);

        // Cap at total annual leaves
        return min($accrued, $totalAnnualLeaves);
    }

    /**
     * Calculate months worked in a company year
     *
     * Used for monthly accrual calculation.
     *
     * @param Carbon $accrualStartDate When accrual started (DOJ or year start)
     * @param int $year Company year
     * @return int Number of complete months worked (capped at 12)
     */
    public function calculateMonthsWorked(Carbon $accrualStartDate, int $year): int
    {
        $startDate = Carbon::parse($accrualStartDate);
        $currentDate = Carbon::now();

        // Get company year end date
        $yearDates = get_company_year_dates($year);
        $yearEnd = Carbon::parse($yearDates['end']);

        // Use the earlier of current date or year end
        $endDate = $currentDate->lessThan($yearEnd) ? $currentDate : $yearEnd;

        // Calculate complete months (rounded down)
        if ($endDate->lessThan($startDate)) {
            return 0;
        }

        // Count COMPLETE months only (use floor to avoid fractional months)
        $months = floor($startDate->floatDiffInMonths($endDate));

        // Add 1 to include the current month (if we're past the start date)
        if ($endDate->day >= $startDate->day || $endDate->isLastOfMonth()) {
            $months += 1;
        }

        return min((int) $months, 12); // Cap at 12 months, ensure integer
    }

    /**
     * Calculate baseline LOP from LeaveRequest records
     *
     * This is used in Flow 3: Payslip Adjustment
     * Calculates what LOP should be based on approved leave requests for the month.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param string $month Month in YYYY-MM format
     * @return array ['paid_leave_days' => float, 'unpaid_leave_days' => float, 'lop_days' => float, 'total_leave_days' => float]
     */
    public function calculateBaselineLOP(int $userId, int $workspaceId, string $month): array
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $workingDays = (float) $monthStart->daysInMonth;

        Log::info('[LeaveCalculationService] calculateBaselineLOP called', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'month' => $month,
            'month_start' => $monthStart->toDateString(),
            'month_end' => $monthEnd->toDateString(),
            'working_days' => $workingDays,
        ]);

        // Fetch approved leaves for this user in this month
        // Note: Database stores dates in Y-m-d format, so we compare with toDateString() which also returns Y-m-d
        $leaves = LeaveRequest::where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('from_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('to_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                        $q2->where('from_date', '<=', $monthStart->toDateString())
                            ->where('to_date', '>=', $monthEnd->toDateString());
                    });
            })
            ->get();

        Log::info('[LeaveCalculationService] Found leaves for baseline LOP', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'month' => $month,
            'leaves_count' => $leaves->count(),
            'leaves_data' => $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'from_date' => $leave->from_date,
                    'to_date' => $leave->to_date,
                    'status' => $leave->status,
                    'is_paid' => $leave->is_paid,
                    'paid_days' => $leave->paid_days,
                    'unpaid_days' => $leave->unpaid_days,
                    'total_days' => $leave->total_days,
                ];
            })->toArray(),
        ]);

        $paidLeaveDays = 0.0;
        $unpaidLeaveDays = 0.0;

        foreach ($leaves as $leave) {
            $paidDays = (float) ($leave->paid_days ?? 0);
            $unpaidDays = (float) ($leave->unpaid_days ?? 0);

            // If totals are empty, approximate using total_days as unpaid
            if (($paidDays + $unpaidDays) <= 0 && !empty($leave->total_days)) {
                $unpaidDays = (float) $leave->total_days;
                Log::info('[LeaveCalculationService] Using total_days as unpaid_days', [
                    'leave_id' => $leave->id,
                    'total_days' => $leave->total_days,
                ]);
            }

            $paidLeaveDays += $paidDays;
            $unpaidLeaveDays += $unpaidDays;

            Log::info('[LeaveCalculationService] Processing leave', [
                'leave_id' => $leave->id,
                'paid_days' => $paidDays,
                'unpaid_days' => $unpaidDays,
                'running_paid_total' => $paidLeaveDays,
                'running_unpaid_total' => $unpaidLeaveDays,
            ]);
        }

        // LOP days = unpaid leave days, capped at working days in month
        $lopDays = min($unpaidLeaveDays, $workingDays);

        $result = [
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'lop_days' => $lopDays,
            'total_leave_days' => $paidLeaveDays + $unpaidLeaveDays,
        ];

        Log::info('[LeaveCalculationService] Baseline LOP calculated', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'month' => $month,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Calculate complete balance summary
     *
     * This is used in Flow 2: Balance Calculation
     * Provides a complete snapshot of leave balance for a user.
     *
     * @param UserLeaveBalance $balance
     * @param int|null $excludeLeaveId Optional leave ID to exclude from calculations (for edit scenarios)
     * @return array Complete balance summary
     */
    public function calculateBalanceSummary(UserLeaveBalance $balance, ?int $excludeLeaveId = null): array
    {
        $year = $balance->company_year ?? $balance->year;

        // Calculate used paid leaves from LeaveRequest records (single source of truth)
        $usedQuery = LeaveRequest::where('user_id', $balance->user_id)
            ->where('workspace_id', $balance->workspace_id)
            ->where('status', 'approved')
            ->where('is_paid', true)
            ->where(function ($q) use ($year) {
                // Query by company year, not calendar year
                $yearDates = get_company_year_dates($year);
                $q->whereBetween('from_date', [
                    $yearDates['start']->toDateString(),
                    $yearDates['end']->toDateString()
                ]);
            });

        if ($excludeLeaveId) {
            $usedQuery->where('id', '!=', $excludeLeaveId);
        }

        $usedPaidLeaves = (float) $usedQuery->sum('paid_days');

        // Add adjustments (from payslip modifications) to used paid leaves
        // This ensures consistency with recalculateBalance() in LeaveBalanceEngine
        $adjustments = LeaveBalanceAdjustment::where('user_id', $balance->user_id)
            ->where('workspace_id', $balance->workspace_id)
            ->where('year', $year)
            ->get();
        $totalAdjustmentPaid = (float) $adjustments->sum('delta_paid');
        $usedPaidLeaves += $totalAdjustmentPaid;

        // Get unpaid leaves
        $unpaidQuery = LeaveRequest::where('user_id', $balance->user_id)
            ->where('workspace_id', $balance->workspace_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($year) {
                $yearDates = get_company_year_dates($year);
                $q->whereBetween('from_date', [
                    $yearDates['start']->toDateString(),
                    $yearDates['end']->toDateString()
                ]);
            });

        if ($excludeLeaveId) {
            $unpaidQuery->where('id', '!=', $excludeLeaveId);
        }

        $unpaidLeaves = (float) $unpaidQuery->sum('unpaid_days');

        // Account for payslip LOP adjustments (from adjustment records)
        $adjustmentService = app(LeaveBalanceSyncService::class);
        $lopAdjustment = $adjustmentService->calculateTotalLopAdjustment(
            $balance->user_id,
            $balance->workspace_id,
            $year
        );
        $unpaidLeaves = max(0, $unpaidLeaves - $lopAdjustment);

        // Get effective total
        $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;

        // Calculate remaining (non-negative)
        // Note: usedPaidLeaves now includes adjustments, so remaining is calculated correctly
        $remainingPaidLeaves = max(0, (float) $effectiveTotal - $usedPaidLeaves);

        // Advanced paid leaves
        $advancedPaidLeaves = (float) ($balance->advanced_paid_leaves ?? 0);

        // Display remaining (can be negative)
        $displayRemainingPaidLeaves = $remainingPaidLeaves - $advancedPaidLeaves;

        return [
            'total_annual_leaves' => (float) $balance->total_annual_leaves,
            'accrued_leaves' => $balance->accrued_leaves ? (float) $balance->accrued_leaves : null,
            'used_paid_leaves' => $usedPaidLeaves,
            'remaining_paid_leaves' => $remainingPaidLeaves,
            'advanced_paid_leaves' => $advancedPaidLeaves,
            'display_remaining_paid_leaves' => $displayRemainingPaidLeaves,
            'unpaid_leaves_taken' => $unpaidLeaves,
            'carry_forward_leaves' => (float) ($balance->carry_forward_leaves ?? 0),
            'expired_leaves' => (float) ($balance->expired_leaves ?? 0),
        ];
    }
}


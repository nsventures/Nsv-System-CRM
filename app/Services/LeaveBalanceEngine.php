<?php

namespace App\Services;

use App\Models\UserLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveBalanceAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveBalanceEngine
 *
 * Centralized balance management engine.
 * Implements Flow 2: Balance Calculation Flow
 *
 * Key features:
 * - Company year support (not calendar year)
 * - Single source of truth: LeaveRequests (base) + Adjustments (modifications)
 * - Monthly accrual with advance reduction
 * - Atomic balance updates
 */
class LeaveBalanceEngine
{
    protected LeaveCalculationService $calculationService;

    public function __construct(LeaveCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Get or create leave balance for a user
     *
     * Implements Flow 2: [Get/Create Balance]
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int|null $companyYear Company year (defaults to current)
     * @return UserLeaveBalance
     */
    public function getOrCreateBalance(int $userId, int $workspaceId, ?int $companyYear = null): UserLeaveBalance
    {
        if ($companyYear === null) {
            $companyYear = get_current_company_year();
        }

        // Try to find by company_year first, then fallback to year (for backward compatibility)
        $balance = UserLeaveBalance::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where(function ($q) use ($companyYear) {
                $q->where('company_year', $companyYear)
                    ->orWhere('year', $companyYear);
            })
            ->first();

        if ($balance) {
            // Update company_year if missing (migration support)
            if (!$balance->company_year) {
                $balance->company_year = $companyYear;
                $balance->save();
            }

            // NOTE: We do NOT automatically recalculate balance here
            // Balance should only be recalculated when explicitly requested via:
            // - updateBalance() (when leave is approved/rejected)
            // - recalculateBalance() (explicit recalculation)
            // - applyAccrual() (monthly accrual process)
            // This prevents overwriting manually set values in tests and ensures
            // balance is only updated when there's an actual change (leave approval, etc.)

            return $balance;
        }

        // Create new balance
        return $this->initializeBalance($userId, $workspaceId, $companyYear);
    }

    /**
     * Initialize balance for a user
     *
     * Implements Flow 2: [Get/Create Balance] → [Check Accrual Type] → [Calculate Accrued Leaves]
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int $companyYear
     * @return UserLeaveBalance
     */
    public function initializeBalance(int $userId, int $workspaceId, int $companyYear): UserLeaveBalance
    {
        // Get user to determine accrual start date
        $user = \App\Models\User::find($userId);

        // Get company year dates
        $yearDates = get_company_year_dates($companyYear);
        $yearStart = Carbon::parse($yearDates['start']);
        $yearEnd = Carbon::parse($yearDates['end']);

        // Determine accrual start date
        $accrualStartDate = $this->getAccrualStartDate($user, $yearStart, $yearEnd);

        // Check accrual type
        $isMonthlyAccrual = $this->isMonthlyAccrualEnabled();
        $totalAnnualLeaves = $this->getTotalAnnualLeaves();

        // Calculate months worked (for monthly accrual)
        $monthsWorked = 12; // Default for lumpsum
        if ($isMonthlyAccrual) {
            $monthsWorked = $this->calculationService->calculateMonthsWorked($accrualStartDate, $companyYear);
        }

        // Calculate accrued leaves
        $monthlyRate = $isMonthlyAccrual ? $this->getMonthlyAccrualRate() : null;
        $accruedLeaves = $this->calculationService->calculateAccruedLeaves(
            $monthsWorked,
            $totalAnnualLeaves,
            $isMonthlyAccrual,
            $monthlyRate
        );

        // Create balance record
        $balance = UserLeaveBalance::create([
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'year' => $yearStart->year, // Calendar year for backward compatibility
            'company_year' => $companyYear, // Company year (primary)
            'total_annual_leaves' => $totalAnnualLeaves,
            'accrued_leaves' => $accruedLeaves,
            'months_worked' => $monthsWorked,
            'accrual_start_date' => $accrualStartDate,
            'used_paid_leaves' => 0,
            'remaining_paid_leaves' => $accruedLeaves,
            'advanced_paid_leaves' => 0,
            'carry_forward_leaves' => 0,
            'expired_leaves' => 0,
        ]);

        return $balance;
    }

    /**
     * Recalculate balance from source of truth
     *
     * Implements Flow 2: [Sum Used Leaves from DB] → [Calculate Remaining] → [Update Balance Record]
     *
     * Single source of truth:
     * - Used leaves = Sum from LeaveRequest records (base)
     * - Adjustments = Sum from LeaveBalanceAdjustment records (modifications)
     *
     * @param UserLeaveBalance $balance
     * @return UserLeaveBalance
     */
    public function recalculateBalance(UserLeaveBalance $balance): UserLeaveBalance
    {
        Log::info('[LeaveBalanceEngine] recalculateBalance called', [
            'balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'workspace_id' => $balance->workspace_id,
            'company_year' => $balance->company_year ?? $balance->year,
        ]);

        DB::beginTransaction();
        try {
            $companyYear = $balance->company_year ?? $balance->year;
            $yearDates = get_company_year_dates($companyYear);

            Log::info('[LeaveBalanceEngine] Company year dates', [
                'balance_id' => $balance->id,
                'company_year' => $companyYear,
                'year_start' => $yearDates['start']->toDateString(),
                'year_end' => $yearDates['end']->toDateString(),
            ]);

            // [Sum Used Leaves from DB] - Single source of truth from LeaveRequests
            $usedPaidLeaves = $this->calculateUsedPaidLeaves(
                $balance->user_id,
                $balance->workspace_id,
                $companyYear
            );

            Log::info('[LeaveBalanceEngine] Used paid leaves calculated', [
                'balance_id' => $balance->id,
                'used_paid_leaves' => $usedPaidLeaves,
            ]);

            // Get adjustments (from payslip modifications)
            $adjustments = LeaveBalanceAdjustment::where('user_id', $balance->user_id)
                ->where('workspace_id', $balance->workspace_id)
                ->where('year', $companyYear)
                ->get();

            $totalAdjustmentPaid = (float) $adjustments->sum('delta_paid');
            $totalAdjustmentAdvance = (float) $adjustments->sum('delta_advance');

            Log::info('[LeaveBalanceEngine] Adjustments found', [
                'balance_id' => $balance->id,
                'adjustments_count' => $adjustments->count(),
                'total_adjustment_paid' => $totalAdjustmentPaid,
                'total_adjustment_advance' => $totalAdjustmentAdvance,
            ]);

            // Update accrued leaves if monthly accrual (with advance reduction)
            if ($this->isMonthlyAccrualEnabled()) {
                Log::info('[LeaveBalanceEngine] Monthly accrual enabled, applying accrual', [
                    'balance_id' => $balance->id,
                ]);
                $this->applyAccrual($balance);
            }

            // Get effective total
            $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;

            Log::info('[LeaveBalanceEngine] Effective total calculated', [
                'balance_id' => $balance->id,
                'effective_total' => $effectiveTotal,
                'accrued_leaves' => $balance->accrued_leaves,
                'total_annual_leaves' => $balance->total_annual_leaves,
            ]);

            // [Calculate Effective Used Leaves]
            // Effective used = LeaveRequest paid days + Adjustment paid days
            // Adjustments represent payslip modifications (admin granting paid leave beyond balance)
            $effectiveUsedPaidLeaves = $usedPaidLeaves + $totalAdjustmentPaid;

            Log::info('[LeaveBalanceEngine] Effective used paid leaves calculated', [
                'balance_id' => $balance->id,
                'used_paid_leaves_from_requests' => $usedPaidLeaves,
                'total_adjustment_paid' => $totalAdjustmentPaid,
                'effective_used_paid_leaves' => $effectiveUsedPaidLeaves,
            ]);

            // [Calculate Remaining]
            // Remaining = effective total - effective used (always non-negative)
            $remainingPaidLeaves = max(0, (float) $effectiveTotal - $effectiveUsedPaidLeaves);

            Log::info('[LeaveBalanceEngine] Remaining leaves calculated', [
                'balance_id' => $balance->id,
                'effective_total' => $effectiveTotal,
                'effective_used_paid_leaves' => $effectiveUsedPaidLeaves,
                'remaining_paid_leaves' => $remainingPaidLeaves,
            ]);

            // Update balance
            // Store effective used (LeaveRequests + Adjustments) in used_paid_leaves
            $balance->used_paid_leaves = $effectiveUsedPaidLeaves;
            $balance->remaining_paid_leaves = $remainingPaidLeaves;
            $balance->advanced_paid_leaves = max(0, $totalAdjustmentAdvance);
            $balance->save();

            Log::info('[LeaveBalanceEngine] Balance updated', [
                'balance_id' => $balance->id,
                'used_paid_leaves' => $balance->used_paid_leaves,
                'remaining_paid_leaves' => $balance->remaining_paid_leaves,
                'advanced_paid_leaves' => $balance->advanced_paid_leaves,
            ]);

            DB::commit();
            return $balance->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[LeaveBalanceEngine] Failed to recalculate balance', [
                'balance_id' => $balance->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update balance after leave approval/rejection
     *
     * Implements Flow 1: [Update Balance]
     * Called when a leave request is approved or rejected.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param LeaveRequest $leaveRequest
     * @return UserLeaveBalance
     */
    public function updateBalance(int $userId, int $workspaceId, LeaveRequest $leaveRequest): UserLeaveBalance
    {
        Log::info('[LeaveBalanceEngine] updateBalance called', [
            'leave_request_id' => $leaveRequest->id,
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'leave_from_date' => $leaveRequest->from_date,
            'leave_to_date' => $leaveRequest->to_date,
            'leave_status' => $leaveRequest->status,
            'leave_is_paid' => $leaveRequest->is_paid,
            'leave_paid_days' => $leaveRequest->paid_days,
            'leave_unpaid_days' => $leaveRequest->unpaid_days,
        ]);

        DB::beginTransaction();
        try {
            // Get company year from leave date
            $leaveDate = Carbon::parse($leaveRequest->from_date);
            $yearDates = get_company_year_dates();
            $companyYear = get_current_company_year();

            Log::info('[LeaveBalanceEngine] Company year calculation', [
                'leave_date' => $leaveRequest->from_date,
                'parsed_leave_date' => $leaveDate->toDateString(),
                'year_dates_start' => $yearDates['start']->toDateString(),
                'year_dates_end' => $yearDates['end']->toDateString(),
                'current_company_year' => $companyYear,
            ]);

            // Determine which company year this leave belongs to
            if ($leaveDate->lessThan($yearDates['start'])) {
                $companyYear = $companyYear - 1;
                Log::info('[LeaveBalanceEngine] Leave date is before company year start, using previous year', [
                    'adjusted_company_year' => $companyYear,
                ]);
            }

            Log::info('[LeaveBalanceEngine] Getting or creating balance', [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'company_year' => $companyYear,
            ]);

            $balance = $this->getOrCreateBalance($userId, $workspaceId, $companyYear);

            Log::info('[LeaveBalanceEngine] Balance retrieved', [
                'balance_id' => $balance->id,
                'current_used_paid_leaves' => $balance->used_paid_leaves,
                'current_remaining_paid_leaves' => $balance->remaining_paid_leaves,
                'current_total_annual_leaves' => $balance->total_annual_leaves,
            ]);

            // Recalculate from source of truth
            Log::info('[LeaveBalanceEngine] Recalculating balance from source of truth', [
                'balance_id' => $balance->id,
            ]);

            $balance = $this->recalculateBalance($balance);

            Log::info('[LeaveBalanceEngine] Balance recalculated', [
                'balance_id' => $balance->id,
                'new_used_paid_leaves' => $balance->used_paid_leaves,
                'new_remaining_paid_leaves' => $balance->remaining_paid_leaves,
                'new_total_annual_leaves' => $balance->total_annual_leaves,
            ]);

            DB::commit();
            return $balance;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[LeaveBalanceEngine] Failed to update balance', [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'leave_request_id' => $leaveRequest->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply monthly accrual with advance reduction
     *
     * Implements Flow 2: Monthly accrual logic
     * Critical: On monthly accrual, reduce advanced_paid_leaves first, then add to normal balance.
     *
     * @param UserLeaveBalance $balance
     * @return UserLeaveBalance
     */
    public function applyAccrual(UserLeaveBalance $balance): UserLeaveBalance
    {
        if (!$this->isMonthlyAccrualEnabled()) {
            return $balance;
        }

        $companyYear = $balance->company_year ?? $balance->year;
        $user = \App\Models\User::find($balance->user_id);

        $yearDates = get_company_year_dates($companyYear);
        $yearStart = Carbon::parse($yearDates['start']);
        $yearEnd = Carbon::parse($yearDates['end']);

        // Get accrual start date
        $accrualStartDate = $this->getAccrualStartDate($user, $yearStart, $yearEnd);

        // Calculate months worked
        $monthsWorked = $this->calculationService->calculateMonthsWorked($accrualStartDate, $companyYear);

        // Calculate new accrued leaves
        $totalAnnualLeaves = $this->getTotalAnnualLeaves();
        $monthlyRate = $this->getMonthlyAccrualRate();
        $newAccruedLeaves = $this->calculationService->calculateAccruedLeaves(
            $monthsWorked,
            $totalAnnualLeaves,
            true,
            $monthlyRate
        );

        // Get current advance leaves
        $currentAdvance = (float) ($balance->advanced_paid_leaves ?? 0);
        $oldAccrued = (float) ($balance->accrued_leaves ?? $balance->total_annual_leaves);
        $accrualIncrease = $newAccruedLeaves - $oldAccrued;

        // CRITICAL: Reduce advance leaves first
        if ($currentAdvance > 0 && $accrualIncrease > 0) {
            $advanceReduction = min($currentAdvance, $accrualIncrease);
            $balance->advanced_paid_leaves = max(0, $currentAdvance - $advanceReduction);
            $accrualIncrease -= $advanceReduction;
        }

        // Update accrued leaves
        $balance->accrued_leaves = $newAccruedLeaves;
        $balance->months_worked = $monthsWorked;
        $balance->accrual_start_date = $accrualStartDate;

        // Recalculate remaining
        $usedPaidLeaves = (float) $balance->used_paid_leaves;
        $balance->remaining_paid_leaves = max(0, $newAccruedLeaves - $usedPaidLeaves);

        $balance->save();
        return $balance;
    }

    /**
     * Check available balance
     *
     * Used in Flow 1 and Flow 3 for balance validation.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param float $requestedDays
     * @param int|null $companyYear
     * @return bool
     */
    public function checkAvailableBalance(
        int $userId,
        int $workspaceId,
        float $requestedDays,
        ?int $companyYear = null
    ): bool {
        $available = $this->calculationService->getAvailableBalance($userId, $workspaceId, $companyYear);
        return $available >= $requestedDays;
    }

    /**
     * Calculate used paid leaves from LeaveRequest records
     *
     * Single source of truth: Sum from LeaveRequest records only.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int $companyYear
     * @return float
     */
    protected function calculateUsedPaidLeaves(int $userId, int $workspaceId, int $companyYear): float
    {
        $yearDates = get_company_year_dates($companyYear);

        Log::info('[LeaveBalanceEngine] calculateUsedPaidLeaves called', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'company_year' => $companyYear,
            'year_start' => $yearDates['start']->toDateString(),
            'year_end' => $yearDates['end']->toDateString(),
        ]);

        $sum = LeaveRequest::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'approved')
            ->where('is_paid', true)
            ->whereBetween('from_date', [
                $yearDates['start']->toDateString(),
                $yearDates['end']->toDateString()
            ])
            ->sum('paid_days');

        Log::info('[LeaveBalanceEngine] Found approved paid leaves', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'company_year' => $companyYear,
            'calculated_sum' => $sum,
        ]);

        Log::info('[LeaveBalanceEngine] Used paid leaves sum calculated', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'company_year' => $companyYear,
            'sum' => $sum,
        ]);

        return $sum;
    }

    /**
     * Get accrual start date for a user
     *
     * @param \App\Models\User|null $user
     * @param Carbon $yearStart
     * @param Carbon $yearEnd
     * @return Carbon
     */
    protected function getAccrualStartDate(?\App\Models\User $user, Carbon $yearStart, Carbon $yearEnd): Carbon
    {
        if (!$user) {
            return $yearStart;
        }

        // Use actual date of joining (doj) if available
        $joiningDate = $user->doj ? Carbon::parse($user->doj) : Carbon::parse($user->created_at);

        // If user joined within this company year, use joining date
        if ($joiningDate->greaterThanOrEqualTo($yearStart) && $joiningDate->lessThanOrEqualTo($yearEnd)) {
            return $joiningDate->startOfMonth();
        }

        // Otherwise, use year start
        return $yearStart;
    }

    /**
     * Get total annual leaves from settings
     *
     * @return float
     */
    public function getTotalAnnualLeaves(): float
    {
        $settings = get_settings('general_settings');
        return (float) ($settings['total_paid_leaves_per_year'] ?? 12);
    }

    /**
     * Check if monthly accrual is enabled
     *
     * @return bool
     */
    public function isMonthlyAccrualEnabled(): bool
    {
        $settings = get_settings('general_settings');
        return ($settings['leave_accrual_type'] ?? 'monthly') === 'monthly';
    }

    /**
     * Get monthly accrual rate
     *
     * @return float
     */
    public function getMonthlyAccrualRate(): float
    {
        $settings = get_settings('general_settings');
        if (isset($settings['monthly_accrual_rate'])) {
            return (float) $settings['monthly_accrual_rate'];
        }
        return round($this->getTotalAnnualLeaves() / 12, 2);
    }

    /**
     * Get balance summary
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int|null $companyYear
     * @param int|null $excludeLeaveId
     * @return array
     */
    public function getBalanceSummary(
        int $userId,
        int $workspaceId,
        ?int $companyYear = null,
        ?int $excludeLeaveId = null
    ): array {
        if ($companyYear === null) {
            $companyYear = get_current_company_year();
        }

        $balance = $this->getOrCreateBalance($userId, $workspaceId, $companyYear);
        return $this->calculationService->calculateBalanceSummary($balance, $excludeLeaveId);
    }

    /**
     * Restore balance after leave deletion or rejection
     *
     * This is called when a previously approved leave is rejected or deleted.
     * It recalculates the balance, which will automatically exclude this leave
     * since it's no longer approved.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param LeaveRequest $leaveRequest The leave request being rejected/deleted
     * @return UserLeaveBalance|null
     */
    public function restoreBalance(int $userId, int $workspaceId, LeaveRequest $leaveRequest): ?UserLeaveBalance
    {
        // Check if this leave was previously approved and paid
        // Note: The leave request status might already be changed to 'rejected',
        // so we check if it has paid_days > 0 which indicates it was approved and paid
        if ($leaveRequest->is_paid && $leaveRequest->paid_days > 0) {
            // Recalculate balance - this will automatically exclude this leave
            // since calculateUsedPaidLeaves only counts approved leaves
            return $this->updateBalance($userId, $workspaceId, $leaveRequest);
        }
        return null;
    }
}


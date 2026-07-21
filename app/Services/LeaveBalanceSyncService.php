<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\UserLeaveBalance;
use App\Models\LeaveBalanceAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveBalanceSyncService
 *
 * Handles synchronization between Payslip adjustments and Leave Balance.
 * Implements Flow 3: Payslip Adjustment Flow
 *
 * Key features:
 * - Creates adjustment records for exact reversal
 * - Handles override scenarios with advance leaves
 * - Uses LeaveCalculationService for baseline calculations
 * - Single source of truth: LeaveRequests + Adjustments
 */
class LeaveBalanceSyncService
{
    protected LeaveBalanceEngine $balanceEngine;
    protected LeaveCalculationService $calculationService;

    public function __construct(
        LeaveBalanceEngine $balanceEngine,
        LeaveCalculationService $calculationService
    ) {
        $this->balanceEngine = $balanceEngine;
        $this->calculationService = $calculationService;
    }

    /**
     * Sync leave balance from payslip adjustments
     *
     * Implements Flow 3: [Payslip Created/Updated] → [Calculate Baseline LOP] → [Calculate Delta] → [Sync Balance]
     *
     * @param Payslip $payslip
     * @param array $options ['is_update' => bool, 'override_confirmed' => bool]
     * @return array
     */
    public function syncFromPayslip(Payslip $payslip, array $options = []): array
    {
        DB::beginTransaction();
        try {
            $isUpdate = $options['is_update'] ?? false;
            $overrideConfirmed = $options['override_confirmed'] ?? false;

            Log::info('Starting leave balance sync from payslip', [
                'payslip_id' => $payslip->id,
                'user_id' => $payslip->user_id,
                'month' => $payslip->month,
                'is_update' => $isUpdate,
                'override_confirmed' => $overrideConfirmed
            ]);

            // [Calculate Baseline LOP] - From LeaveCalculationService
            $baseline = $this->calculationService->calculateBaselineLOP(
                $payslip->user_id,
                $payslip->workspace_id,
                Carbon::parse($payslip->month)->format('Y-m')
            );

            // [Admin Adjusts LOP?] - Calculate delta
            $currentLopDays = (float) $payslip->lop_days;
            $baselineLopDays = (float) ($baseline['lop_days'] ?? 0);
            $deltaLop = $currentLopDays - $baselineLopDays;
            $deltaPaidLeave = -$deltaLop; // Inverse relationship

            // If no adjustment, nothing to sync
            if (abs($deltaLop) < 0.01) {
                DB::commit();
                return [
                    'success' => true,
                    'balance_updated' => false,
                    'message' => 'No adjustment needed',
                ];
            }

            // Get company year from payslip month
            $monthDate = Carbon::parse($payslip->month);
            $companyYear = $this->getCompanyYearForDate($monthDate);

            // Get or create balance
            $balance = $this->balanceEngine->getOrCreateBalance(
                $payslip->user_id,
                $payslip->workspace_id,
                $companyYear
            );

            // [Check Available Balance]
            $availableBalance = $this->calculationService->getAvailableBalance(
                $payslip->user_id,
                $payslip->workspace_id,
                $companyYear,
                $balance
            );

            // [Check if Sufficient?]
            $overrideRequired = $deltaPaidLeave > 0 && $deltaPaidLeave > $availableBalance;
            $excessPaidLeave = $overrideRequired ? ($deltaPaidLeave - $availableBalance) : 0;

            if ($overrideRequired && !$overrideConfirmed) {
                DB::rollBack();
                return [
                    'success' => false,
                    'balance_updated' => false,
                    'override_required' => true,
                    'delta_paid_leave' => $deltaPaidLeave,
                    'available_balance' => $availableBalance,
                    'excess_paid_leave' => $excessPaidLeave,
                ];
            }

            // [Sync Balance] - Create adjustment record
            $adjustment = $this->createAdjustment(
                $payslip,
                $deltaPaidLeave,
                $excessPaidLeave,
                $overrideRequired && $overrideConfirmed,
                $companyYear
            );

            // Recalculate balance (will pick up adjustment from records)
            $balance = $this->balanceEngine->recalculateBalance($balance);

            DB::commit();

            return [
                'success' => true,
                'balance_updated' => true,
                'adjustment_id' => $adjustment->id,
                'delta_paid_leave' => $deltaPaidLeave,
                'delta_advance' => $excessPaidLeave,
                'override_applied' => $overrideRequired && $overrideConfirmed,
                'new_used_paid_leaves' => (float) $balance->used_paid_leaves,
                'new_remaining_paid_leaves' => (float) $balance->remaining_paid_leaves,
                'new_advanced_paid_leaves' => (float) $balance->advanced_paid_leaves,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Leave balance sync failed for payslip', [
                'payslip_id' => $payslip->id ?? null,
                'user_id' => $payslip->user_id ?? null,
                'month' => $payslip->month ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance_updated' => false,
            ];
        }
    }

    /**
     * Check if override is required for a payslip adjustment
     *
     * Called BEFORE creating/updating payslip to determine if confirmation is needed.
     *
     * @param Payslip $payslip
     * @return array
     */
    public function checkOverrideRequired(Payslip $payslip): array
    {
        try {
            // Get baseline from LeaveCalculationService
            $baseline = $this->calculationService->calculateBaselineLOP(
                $payslip->user_id,
                $payslip->workspace_id,
                Carbon::parse($payslip->month)->format('Y-m')
            );

            // Calculate deltas
            $currentLopDays = (float) $payslip->lop_days;
            $baselineLopDays = (float) ($baseline['lop_days'] ?? 0);
            $deltaLop = $currentLopDays - $baselineLopDays;
            $deltaPaidLeave = -$deltaLop;

            // If no manual adjustment, no override needed
            if (abs($deltaLop) < 0.01) {
                return [
                    'override_required' => false,
                    'delta_paid_leave' => 0,
                    'delta_lop' => 0,
                    'available_balance' => 0,
                    'excess_paid_leave' => 0,
                    'baseline_lop' => $baselineLopDays,
                    'submitted_lop' => $currentLopDays,
                ];
            }

            // Get company year
            $monthDate = Carbon::parse($payslip->month);
            $companyYear = $this->getCompanyYearForDate($monthDate);

            // Get balance
            $balance = $this->balanceEngine->getOrCreateBalance(
                $payslip->user_id,
                $payslip->workspace_id,
                $companyYear
            );

            // Get available balance
            $availableBalance = $this->calculationService->getAvailableBalance(
                $payslip->user_id,
                $payslip->workspace_id,
                $companyYear,
                $balance
            );

            // Check if override is needed
            $overrideRequired = $deltaPaidLeave > 0 && $deltaPaidLeave > $availableBalance;
            $excessPaidLeave = $overrideRequired ? ($deltaPaidLeave - $availableBalance) : 0;

            return [
                'override_required' => $overrideRequired,
                'delta_paid_leave' => $deltaPaidLeave,
                'delta_lop' => $deltaLop,
                'available_balance' => $availableBalance,
                'excess_paid_leave' => $excessPaidLeave,
                'baseline_lop' => $baselineLopDays,
                'submitted_lop' => $currentLopDays,
                'current_used_paid_leaves' => (float) $balance->used_paid_leaves,
                'effective_total' => $balance->accrued_leaves ?? $balance->total_annual_leaves,
            ];
        } catch (\Exception $e) {
            Log::error('Override check failed for payslip', [
                'payslip_id' => $payslip->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'override_required' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create adjustment record
     *
     * This enables exact reversal and single source of truth.
     *
     * @param Payslip $payslip
     * @param float $deltaPaidLeave
     * @param float $excessPaidLeave
     * @param bool $isOverride
     * @param int $companyYear
     * @return LeaveBalanceAdjustment
     */
    protected function createAdjustment(
        Payslip $payslip,
        float $deltaPaidLeave,
        float $excessPaidLeave,
        bool $isOverride,
        int $companyYear
    ): LeaveBalanceAdjustment {
        // If updating, remove old adjustment first
        if ($payslip->id) {
            LeaveBalanceAdjustment::where('payslip_id', $payslip->id)->delete();
        }

        // Calculate what can be granted from available balance
        $grantablePaidLeave = $deltaPaidLeave - $excessPaidLeave;
        $deltaAdvance = $isOverride ? $excessPaidLeave : 0;

        // Create adjustment record
        // CRITICAL: delta_paid should be TOTAL paid days granted, not just grantable
        // When override is confirmed, we're granting deltaPaidLeave total paid days
        // The delta_advance tracks the excess portion beyond available balance
        $adjustment = LeaveBalanceAdjustment::create([
            'payslip_id' => $payslip->id,
            'user_id' => $payslip->user_id,
            'workspace_id' => $payslip->workspace_id,
            'year' => $companyYear,
            'delta_paid' => $deltaPaidLeave, // Total paid days granted (was: $grantablePaidLeave)
            'delta_advance' => $deltaAdvance,
            'notes' => $isOverride
                ? "Override confirmed: {$excessPaidLeave} days granted as advance leaves"
                : "Normal adjustment from payslip LOP change",
        ]);

        return $adjustment;
    }

    /**
     * Reverse adjustment (for payslip updates/deletions)
     *
     * Uses adjustment records for exact reversal (not proportional).
     *
     * @param Payslip $payslip
     * @return array
     */
    public function reverseAdjustment(Payslip $payslip): array
    {
        DB::beginTransaction();
        try {
            // Find adjustment record for this payslip
            $adjustment = LeaveBalanceAdjustment::where('payslip_id', $payslip->id)->first();

            if (!$adjustment) {
                DB::commit();
                return [
                    'success' => true,
                    'balance_updated' => false,
                    'message' => 'No adjustment found to reverse',
                ];
            }

            // Delete adjustment record (reversal)
            $adjustment->delete();

            // Recalculate balance (will exclude this adjustment now)
            $companyYear = $adjustment->year;
            $balance = $this->balanceEngine->getOrCreateBalance(
                $adjustment->user_id,
                $adjustment->workspace_id,
                $companyYear
            );

            $balance = $this->balanceEngine->recalculateBalance($balance);

            DB::commit();

            return [
                'success' => true,
                'balance_updated' => true,
                'reversed_delta_paid_leave' => -$adjustment->delta_paid,
                'reversed_delta_advance' => -$adjustment->delta_advance,
                'new_used_paid_leaves' => (float) $balance->used_paid_leaves,
                'new_remaining_paid_leaves' => (float) $balance->remaining_paid_leaves,
                'new_advanced_paid_leaves' => (float) $balance->advanced_paid_leaves,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Leave balance reversal failed for payslip ' . $payslip->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance_updated' => false,
            ];
        }
    }

    /**
     * Calculate total LOP adjustment from payslip adjustments
     *
     * Used by LeaveCalculationService to account for payslip adjustments in balance summary.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int $companyYear
     * @return float Total LOP adjustment (positive = unpaid leaves reduced)
     */
    public function calculateTotalLopAdjustment(int $userId, int $workspaceId, int $companyYear): float
    {
        // Get all payslips for this user/year
        $payslips = Payslip::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->whereYear('month', $companyYear)
            ->get();

        $totalLopAdjustment = 0.0;

        foreach ($payslips as $payslip) {
            // Get baseline
            $baseline = $this->calculationService->calculateBaselineLOP(
                $userId,
                $workspaceId,
                Carbon::parse($payslip->month)->format('Y-m')
            );

            // Calculate adjustment: baseline_lop - current_lop
            // If LOP decreased (e.g., 5.5 → 0), adjustment is positive (unpaid leaves reduced)
            $baselineLopDays = (float) ($baseline['lop_days'] ?? 0);
            $currentLopDays = (float) $payslip->lop_days;
            $adjustment = $baselineLopDays - $currentLopDays;
            $totalLopAdjustment += $adjustment;
        }

        return $totalLopAdjustment;
    }

    /**
     * Get company year for a given date
     *
     * @param Carbon $date
     * @return int Company year identifier
     */
    protected function getCompanyYearForDate(Carbon $date): int
    {
        return get_current_company_year();
        // Note: This could be enhanced to determine company year from date
        // For now, using current company year as default
    }
}

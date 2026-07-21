<?php

namespace App\Services;

use App\Models\UserLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;

class LeaveBalanceService
{
    /**
     * Get or create leave balance for a user in a workspace for a specific year
     */
    public function getOrCreateBalance($userId, $workspaceId, $year = null)
    {
        if ($year === null) {
            $year = get_current_company_year();
        }

        // Get user to determine accrual start date
        $user = \App\Models\User::find($userId);
        $accrualStartDate = $this->getAccrualStartDate($user, $year);
        $monthsWorked = $this->calculateMonthsWorked($accrualStartDate, $year);
        $accruedLeaves = $this->calculateAccruedLeaves($monthsWorked);

        $balance = UserLeaveBalance::firstOrCreate(
            [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'year' => $year,
            ],
            [
                'total_annual_leaves' => $this->getTotalAnnualLeaves(),
                'accrued_leaves' => $accruedLeaves,
                'months_worked' => $monthsWorked,
                'accrual_start_date' => $accrualStartDate,
                'used_paid_leaves' => 0,
                'remaining_paid_leaves' => $accruedLeaves,
            ]
        );

        // Update total_annual_leaves if settings changed (for existing balances)
        if (!$balance->wasRecentlyCreated) {
            $currentTotal = (float) $this->getTotalAnnualLeaves();
            $storedTotal = (float) $balance->total_annual_leaves;

            // Compare with small tolerance for floating point comparison
            if (abs($storedTotal - $currentTotal) > 0.01) {
                $balance->total_annual_leaves = $currentTotal;
                $balance->save();
            }
        }

        // Update accrued leaves if already exists (for current year)
        if (!$balance->wasRecentlyCreated && $year == date('Y')) {
            $this->updateAccruedLeaves($balance);
        }

        return $balance;
    }

    /**
     * Get total annual leaves from settings
     */
    public function getTotalAnnualLeaves()
    {
        $settings = get_settings('general_settings');
        return $settings['total_paid_leaves_per_year'] ?? 12; // Default to 12 if not set
    }

    /**
     * Calculate used paid leaves for a user in a workspace for a specific year
     * Counts all approved paid leaves within the company year period
     */
    public function calculateUsedPaidLeaves($userId, $workspaceId, $year = null)
    {
        if ($year === null) {
            $year = get_current_company_year();
        }

        $query = LeaveRequest::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'approved')
            ->where('is_paid', true)
            ->whereYear('from_date', $year);

        $usedLeaves = $query->sum('paid_days');

        return $usedLeaves ?? 0;
    }

    /**
     * Check if monthly accrual is enabled
     */
    public function isMonthlyAccrualEnabled()
    {
        $settings = get_settings('general_settings');
        return ($settings['leave_accrual_type'] ?? 'monthly') === 'monthly';
    }

    /**
     * Get monthly accrual rate (e.g., 1.25 days per month)
     */
    public function getMonthlyAccrualRate()
    {
        $settings = get_settings('general_settings');
        if (isset($settings['monthly_accrual_rate'])) {
            return (float) $settings['monthly_accrual_rate'];
        }
        // Calculate from total annual leaves
        return round($this->getTotalAnnualLeaves() / 12, 2);
    }

    /**
     * Get accrual start date for a user in a specific year
     */
    protected function getAccrualStartDate($user, $year)
    {
        if (!$user) {
            return Carbon::create($year, 1, 1);
        }

        // Use actual date of joining (doj) if available, otherwise fall back to created_at
        $joiningDate = $user->doj ? Carbon::parse($user->doj) : Carbon::parse($user->created_at);
        $yearStart = Carbon::create($year, 1, 1);

        // If user joined in this year, use joining date
        // Otherwise, use year start
        if ($joiningDate->year == $year) {
            return $joiningDate->startOfMonth(); // Start accrual from month of joining
        }

        return $yearStart;
    }

    /**
     * Calculate months worked from accrual start date to current date
     */
    protected function calculateMonthsWorked($accrualStartDate, $year)
    {
        $startDate = Carbon::parse($accrualStartDate);
        $currentDate = Carbon::now();
        $yearEnd = Carbon::create($year, 12, 31);

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

        return min((int)$months, 12); // Cap at 12 months, ensure integer
    }

    /**
     * Calculate accrued leaves based on months worked
     */
    public function calculateAccruedLeaves($monthsWorked)
    {
        if (!$this->isMonthlyAccrualEnabled()) {
            // If monthly accrual is disabled, return full annual allocation
            return $this->getTotalAnnualLeaves();
        }

        $monthlyRate = $this->getMonthlyAccrualRate();
        return round($monthsWorked * $monthlyRate, 2);
    }

    /**
     * Update accrued leaves for an existing balance record
     */
    public function updateAccruedLeaves($balance)
    {
        // Update total_annual_leaves from current settings first
        $currentTotal = (float) $this->getTotalAnnualLeaves();
        $storedTotal = (float) $balance->total_annual_leaves;

        // Compare with small tolerance for floating point comparison
        if (abs($storedTotal - $currentTotal) > 0.01) {
            $balance->total_annual_leaves = $currentTotal;
        }

        if (!$this->isMonthlyAccrualEnabled()) {
            $balance->accrued_leaves = $balance->total_annual_leaves;
            $balance->months_worked = 12;
            $balance->save();
            return $balance;
        }

        // Always get fresh user data and recalculate from current DOJ (don't use stored accrual_start_date)
        $user = \App\Models\User::find($balance->user_id);
        // Always recalculate accrual_start_date from user's current DOJ to reflect any DOJ changes
        $accrualStartDate = $this->getAccrualStartDate($user, $balance->year);
        $monthsWorked = $this->calculateMonthsWorked($accrualStartDate, $balance->year);
        $accruedLeaves = $this->calculateAccruedLeaves($monthsWorked);

        $balance->accrued_leaves = $accruedLeaves;
        $balance->months_worked = $monthsWorked;
        $balance->accrual_start_date = $accrualStartDate;
        $balance->remaining_paid_leaves = max(0, $accruedLeaves - $balance->used_paid_leaves);
        $balance->save();

        return $balance;
    }

    /**
     * Check if user has sufficient accrued balance for leave request
     */
    public function hasRequiredAccruedBalance($userId, $workspaceId, $requestedDays, $year = null)
    {
        if (!$this->isMonthlyAccrualEnabled()) {
            // If monthly accrual disabled, just check remaining balance
            return $this->canApproveAsPaid($userId, $workspaceId, $requestedDays, $year);
        }

        $balance = $this->getOrCreateBalance($userId, $workspaceId, $year);

        // Check if enough leaves have been accrued
        $availableAccrued = $balance->accrued_leaves - $balance->used_paid_leaves;

        return $availableAccrued >= $requestedDays;
    }

    /**
     * Get remaining balance for a user
     * Takes into account monthly accrual if enabled
     */
    public function getRemainingBalance($userId, $workspaceId, $year = null)
    {
        $balance = $this->getOrCreateBalance($userId, $workspaceId, $year);

        // If monthly accrual is enabled, use accrued leaves as the limit
        if ($this->isMonthlyAccrualEnabled() && isset($balance->accrued_leaves)) {
            return max(0, $balance->accrued_leaves - $balance->used_paid_leaves);
        }

        // Otherwise use the standard remaining balance
        return $balance->remaining_paid_leaves;
    }

    /**
     * Check if user can approve leave as paid
     */
    public function canApproveAsPaid($userId, $workspaceId, $requestedDays, $year = null)
    {
        $remainingBalance = $this->getRemainingBalance($userId, $workspaceId, $year);
        return $remainingBalance >= $requestedDays;
    }

    /**
     * Update balance after leave approval/rejection
     */
    public function updateBalance($userId, $workspaceId, LeaveRequest $leaveRequest)
    {
        // Use calendar year from leave date for balance record
        // This ensures consistency with whereYear() queries
        $year = Carbon::parse($leaveRequest->from_date)->year;
        $balance = $this->getOrCreateBalance($userId, $workspaceId, $year);

        // Recalculate from database to ensure accuracy
        // This sums all approved paid leaves for this user/workspace/year
        $usedLeaves = $this->calculateUsedPaidLeaves($userId, $workspaceId, $year);

        // Get effective total (accrued if monthly accrual, otherwise annual total)
        $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;

        // Update balance fields
        $balance->used_paid_leaves = $usedLeaves;
        $balance->remaining_paid_leaves = max(0, $effectiveTotal - $usedLeaves);
        $balance->save();

        return $balance;
    }

    /**
     * Restore balance after leave deletion or rejection
     */
    public function restoreBalance($userId, $workspaceId, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status === 'approved' && $leaveRequest->is_paid && $leaveRequest->paid_days > 0) {
            $year = Carbon::parse($leaveRequest->from_date)->year;
            $balance = $this->getOrCreateBalance($userId, $workspaceId, $year);

            // Recalculate from database
            $usedLeaves = $this->calculateUsedPaidLeaves($userId, $workspaceId, $year);

            // Get effective total (accrued if monthly accrual, otherwise annual total)
            $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;

            $balance->used_paid_leaves = $usedLeaves;
            $balance->remaining_paid_leaves = max(0, $effectiveTotal - $usedLeaves);
            $balance->save();

            return $balance;
        }

        return null;
    }

    /**
     * Calculate paid and unpaid days based on available balance
     */
    public function calculatePaidUnpaidDays($userId, $workspaceId, $totalDays, $year = null)
    {
        $remainingBalance = $this->getRemainingBalance($userId, $workspaceId, $year);

        if ($remainingBalance >= $totalDays) {
            // All days can be paid
            return [
                'paid_days' => $totalDays,
                'unpaid_days' => 0,
            ];
        } elseif ($remainingBalance > 0) {
            // Partial paid, rest unpaid
            return [
                'paid_days' => $remainingBalance,
                'unpaid_days' => $totalDays - $remainingBalance,
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
     * Initialize balances for all users in a workspace
     */
    public function initializeBalancesForWorkspace($workspaceId, $year = null)
    {
        if ($year === null) {
            $year = get_current_company_year();
        }

        $workspace = \App\Models\Workspace::find($workspaceId);
        $totalAnnualLeaves = $this->getTotalAnnualLeaves();

        foreach ($workspace->users as $user) {
            $this->getOrCreateBalance($user->id, $workspaceId, $year);
        }
    }

    /**
     * Get leave balance summary for a user
     * @param int $excludeLeaveId Optional - exclude this leave from calculations (for edit scenarios)
     */
    public function getBalanceSummary($userId, $workspaceId, $year = null, $excludeLeaveId = null)
    {
        if ($year === null) {
            $year = get_current_company_year();
        }

        $balance = $this->getOrCreateBalance($userId, $workspaceId, $year);

        // Get unpaid leaves count - sum ALL unpaid_days from approved leaves
        // A single leave can have both paid AND unpaid days
        // Exclude the current leave if editing
        $unpaidQuery = LeaveRequest::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'approved')
            ->whereYear('from_date', $year);

        if ($excludeLeaveId) {
            $unpaidQuery->where('id', '!=', $excludeLeaveId);
        }

        $unpaidLeaves = $unpaidQuery->sum('unpaid_days');

        // Account for payslip LOP adjustments (delta leaves concept)
        // When LOP days are manually adjusted in payslips, it affects unpaid_leaves_taken
        // If LOP was reduced (e.g., 5.5 → 0), unpaid leaves are reduced by that amount
        $payslipLopAdjustment = $this->calculatePayslipLopAdjustment($userId, $workspaceId, $year);
        $unpaidLeaves = max(0, $unpaidLeaves - $payslipLopAdjustment);

        // Calculate used paid leaves, excluding current leave if editing
        $usedPaidLeaves = $balance->used_paid_leaves;
        if ($excludeLeaveId) {
            // Recalculate used paid leaves excluding the current leave
            $usedQuery = LeaveRequest::where('user_id', $userId)
                ->where('workspace_id', $workspaceId)
                ->where('status', 'approved')
                ->where('is_paid', true)
                ->whereYear('from_date', $year)
                ->where('id', '!=', $excludeLeaveId);

            $usedPaidLeaves = $usedQuery->sum('paid_days');
        }

        // Calculate remaining based on accrual if enabled
        $remainingBalance = $balance->remaining_paid_leaves;
        if ($this->isMonthlyAccrualEnabled() && isset($balance->accrued_leaves)) {
            $remainingBalance = max(0, $balance->accrued_leaves - $usedPaidLeaves);
        } else {
            // For lump sum, recalculate if excluding a leave
            if ($excludeLeaveId) {
                $remainingBalance = max(0, $balance->total_annual_leaves - $usedPaidLeaves);
            }
        }

        // Ensure total_annual_leaves is up to date (double-check before returning)
        $currentTotalFromSettings = (float) $this->getTotalAnnualLeaves();
        $storedTotal = (float) $balance->total_annual_leaves;

        if (abs($storedTotal - $currentTotalFromSettings) > 0.01) {
            $balance->total_annual_leaves = $currentTotalFromSettings;
            $balance->save();
            $balance->refresh(); // Refresh to get the updated value
        }

        // Get advanced paid leaves from balance
        $advancedPaidLeaves = (float) ($balance->advanced_paid_leaves ?? 0);

        // Calculate display remaining (remaining - advanced, can be negative)
        $displayRemainingPaidLeaves = $remainingBalance - $advancedPaidLeaves;

        $summary = [
            'total_annual_leaves' => (float) $balance->total_annual_leaves,
            'used_paid_leaves' => $usedPaidLeaves, // Use recalculated value if excluding
            'remaining_paid_leaves' => $remainingBalance, // Always non-negative
            'advanced_paid_leaves' => $advancedPaidLeaves,
            'display_remaining_paid_leaves' => $displayRemainingPaidLeaves, // Can be negative for UI
            'unpaid_leaves_taken' => $unpaidLeaves ?? 0,
            'utilization_percentage' => $balance->total_annual_leaves > 0
                ? round(($usedPaidLeaves / $balance->total_annual_leaves) * 100, 2)
                : 0,
        ];

        // Add monthly accrual information if enabled
        if ($this->isMonthlyAccrualEnabled()) {
            $summary['accrued_leaves'] = $balance->accrued_leaves;
            $summary['months_worked'] = $balance->months_worked;
            $summary['monthly_accrual_rate'] = $this->getMonthlyAccrualRate();
            $summary['accrual_start_date'] = $balance->accrual_start_date;
            $summary['accrual_utilization_percentage'] = $balance->accrued_leaves > 0
                ? round(($balance->used_paid_leaves / $balance->accrued_leaves) * 100, 2)
                : 0;
        }

        return $summary;
    }

    public function getWorkspaceSummary(int $workspaceId, ?int $year = null, array $filters = []): array
    {
        $year = $year ?? get_current_company_year();
        $lowThreshold = isset($filters['low_threshold']) ? (float) $filters['low_threshold'] : 3;

        $balances = UserLeaveBalance::where('workspace_id', $workspaceId)
            ->where('year', $year);

        if (!empty($filters['user_ids'])) {
            $balances->whereIn('user_id', (array) $filters['user_ids']);
        }

        if (!empty($filters['balance_status'])) {
            if ($filters['balance_status'] === 'exhausted') {
                $balances->where('remaining_paid_leaves', '<=', 0);
            } elseif ($filters['balance_status'] === 'low') {
                $balances->where('remaining_paid_leaves', '>', 0)
                    ->where('remaining_paid_leaves', '<', $lowThreshold);
            } elseif ($filters['balance_status'] === 'healthy') {
                $balances->where('remaining_paid_leaves', '>=', $lowThreshold);
            }
        }

        $summaryRow = (clone $balances)
            ->selectRaw('COUNT(*) as member_count,
                SUM(total_annual_leaves) as total_annual_allocation,
                SUM(COALESCE(accrued_leaves, total_annual_leaves)) as total_accrued_allocation,
                SUM(used_paid_leaves) as total_used,
                SUM(GREATEST(remaining_paid_leaves, 0)) as total_accrued_remaining,
                SUM(GREATEST(total_annual_leaves - used_paid_leaves, 0)) as total_annual_remaining,
                SUM(COALESCE(advanced_paid_leaves, 0)) as total_advanced_paid_leaves')
            ->first();

        $exhaustedCount = (clone $balances)
            ->where('remaining_paid_leaves', '<=', 0)
            ->count();

        $lowCount = (clone $balances)
            ->where('remaining_paid_leaves', '>', 0)
            ->where('remaining_paid_leaves', '<', $lowThreshold)
            ->count();

        $unpaidQuery = LeaveRequest::where('workspace_id', $workspaceId)
            ->where('status', 'approved');

        // Filter by date range if provided
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $unpaidQuery->where(function($query) use ($filters) {
                $query->whereBetween('from_date', [$filters['date_from'], $filters['date_to']])
                      ->orWhereBetween('to_date', [$filters['date_from'], $filters['date_to']])
                      ->orWhere(function($q) use ($filters) {
                          $q->where('from_date', '<=', $filters['date_from'])
                            ->where('to_date', '>=', $filters['date_to']);
                      });
            });
        } else {
            // Fallback to year filter
            $unpaidQuery->whereYear('from_date', $year);
        }

        if (!empty($filters['user_ids'])) {
            $unpaidQuery->whereIn('user_id', (array) $filters['user_ids']);
        }

        $totalUnpaid = (float) $unpaidQuery->sum('unpaid_days');

        $totalAnnualAllocation = (float) ($summaryRow->total_annual_allocation ?? 0);
        $totalAccruedAllocation = (float) ($summaryRow->total_accrued_allocation ?? 0);
        $totalUsed = (float) ($summaryRow->total_used ?? 0);
        $totalAccruedRemaining = (float) ($summaryRow->total_accrued_remaining ?? 0);
        $totalAnnualRemaining = (float) ($summaryRow->total_annual_remaining ?? 0);

        $accrualEnabled = $this->isMonthlyAccrualEnabled();
        $totalAccruedUsed = $accrualEnabled
            ? min($totalUsed, $totalAccruedAllocation)
            : $totalUsed;

        return [
            'year' => $year,
            'member_count' => (int) ($summaryRow->member_count ?? 0),
            'total_allocation' => round($totalAccruedAllocation, 2),
            'total_accrued_allocation' => round($totalAccruedAllocation, 2),
            'total_annual_allocation' => round($totalAnnualAllocation, 2),
            'total_yearly_allocation' => round($totalAnnualAllocation, 2),
            'total_used' => round($totalUsed, 2),
            'total_accrued_used' => round($totalAccruedUsed, 2),
            'total_yearly_used' => round($totalUsed, 2),
            'total_remaining' => round($totalAccruedRemaining, 2),
            'total_accrued_remaining' => round($totalAccruedRemaining, 2),
            'total_annual_remaining' => round($totalAnnualRemaining, 2),
            'total_yearly_remaining' => round($totalAnnualRemaining, 2),
            'overall_utilization' => $totalAnnualAllocation > 0 ? round(($totalUsed / $totalAnnualAllocation) * 100, 1) : 0,
            'overall_utilization_accrued' => $totalAccruedAllocation > 0 ? round(($totalUsed / $totalAccruedAllocation) * 100, 1) : 0,
            'low_balance_count' => $lowCount,
            'exhausted_count' => $exhaustedCount,
            'accrual_enabled' => $accrualEnabled,
            'total_unpaid_taken' => round($totalUnpaid, 2),
            'total_advanced_paid_leaves' => round((float) ($summaryRow->total_advanced_paid_leaves ?? 0), 2),
        ];
    }

    public function getWorkspaceBalanceTableData(int $workspaceId, array $params = []): array
    {
        $year = isset($params['year']) && $params['year'] !== '' ? (int) $params['year'] : get_current_company_year();
        $search = $params['search'] ?? null;
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
        $sort = $params['sort'] ?? 'users.first_name';
        $order = strtoupper($params['order'] ?? 'ASC');
        $userIds = !empty($params['user_ids']) ? (array) $params['user_ids'] : [];
        $balanceStatus = $params['balance_status'] ?? null;
        $lowThreshold = isset($params['low_threshold']) ? (float) $params['low_threshold'] : 3;

        // Ensure all workspace users have leave balances (safety net for missing balances)
        try {
            $workspace = \App\Models\Workspace::find($workspaceId);
            if ($workspace) {
                foreach ($workspace->users as $user) {
                    // Only create if balance doesn't exist to avoid unnecessary queries
                    $existingBalance = UserLeaveBalance::where('user_id', $user->id)
                        ->where('workspace_id', $workspaceId)
                        ->where('year', $year)
                        ->exists();

                    if (!$existingBalance) {
                        $this->getOrCreateBalance($user->id, $workspaceId, $year);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail - this is just a safety net
            Log::warning('Failed to ensure leave balances for workspace ' . $workspaceId . ': ' . $e->getMessage());
        }

        $query = UserLeaveBalance::query()
            ->select('user_leave_balances.*')
            ->with(['user.roles'])
            ->join('users', 'user_leave_balances.user_id', '=', 'users.id')
            ->where('user_leave_balances.workspace_id', $workspaceId)
            ->where('user_leave_balances.year', $year);

        if (!empty($userIds)) {
            $query->whereIn('user_leave_balances.user_id', $userIds);
        }

        if ($search) {
            $query->where(function ($sub) use ($search) {
                $sub->where(DB::raw('CONCAT(users.first_name, " ", users.last_name)'), 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%');
            });
        }

        if ($balanceStatus) {
            if ($balanceStatus === 'exhausted') {
                $query->where('user_leave_balances.remaining_paid_leaves', '<=', 0);
            } elseif ($balanceStatus === 'low') {
                $query->where('user_leave_balances.remaining_paid_leaves', '>', 0)
                    ->where('user_leave_balances.remaining_paid_leaves', '<', $lowThreshold);
            } elseif ($balanceStatus === 'healthy') {
                $query->where('user_leave_balances.remaining_paid_leaves', '>=', $lowThreshold);
            }
        }

        $sortable = [
            'member' => 'users.first_name',
            'total_allocation' => 'user_leave_balances.total_annual_leaves',
            'used_paid_leaves' => 'user_leave_balances.used_paid_leaves',
            'remaining_paid_leaves' => 'user_leave_balances.remaining_paid_leaves',
            'utilization' => 'user_leave_balances.used_paid_leaves',
        ];

        $sortColumn = $sortable[$sort] ?? $sortable['member'];

        $orderedBalances = (clone $query)
            ->orderBy($sortColumn, $order === 'DESC' ? 'DESC' : 'ASC')
            ->get();

        $total = $orderedBalances->count();

        $filteredUserIds = $orderedBalances
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        $healthyThreshold = max($lowThreshold, 10);

        $unpaidQuery = LeaveRequest::select('user_id', DB::raw('SUM(unpaid_days) as total_unpaid'))
            ->where('workspace_id', $workspaceId)
            ->whereYear('from_date', $year)
            ->where('status', 'approved');

        if (!empty($filteredUserIds)) {
            $unpaidQuery->whereIn('user_id', $filteredUserIds);
        } elseif (!empty($userIds)) {
            $unpaidQuery->whereIn('user_id', $userIds);
        }

        $unpaidByUser = $unpaidQuery
            ->groupBy('user_id')
            ->pluck('total_unpaid', 'user_id');

        $healthyCount = $orderedBalances
            ->filter(function (UserLeaveBalance $balance) use ($healthyThreshold) {
                return max((float) $balance->remaining_paid_leaves, 0) >= $healthyThreshold;
            })
            ->count();

        $topUnpaidMember = null;
        if ($unpaidByUser->isNotEmpty()) {
            $topUserId = $unpaidByUser->sortDesc()->keys()->first();
            $topBalance = $orderedBalances->firstWhere('user_id', $topUserId);
            $topUserName = get_label('not_assigned', 'Not assigned');
            if ($topBalance && $topBalance->user) {
                $topUserName = trim($topBalance->user->first_name . ' ' . $topBalance->user->last_name);
            }
            $topUnpaidMember = [
                'name' => $topUserName,
                'unpaid_leaves' => round((float) $unpaidByUser[$topUserId], 2),
            ];
        }

        $summaryFilters = [
            'user_ids' => $filteredUserIds,
            'balance_status' => $balanceStatus,
            'low_threshold' => $lowThreshold,
        ];
        $summaryData = $this->getWorkspaceSummary($workspaceId, $year, $summaryFilters);

        $chartDataCollection = $orderedBalances->map(function (UserLeaveBalance $balance) use ($unpaidByUser) {
            $user = $balance->user;
            $used = (float) $balance->used_paid_leaves;
            // Ensure remaining is non-negative (should be, but handle edge cases)
            $remaining = max(0, (float) $balance->remaining_paid_leaves);
            $advanced = (float) ($balance->advanced_paid_leaves ?? 0);
            $displayRemaining = $remaining - $advanced; // Can be negative
            $effectiveTotal = max((float) ($balance->accrued_leaves ?? $balance->total_annual_leaves), 0);
            $unpaid = (float) ($unpaidByUser[$balance->user_id] ?? 0);

            return [
                'member' => $user ? trim($user->first_name . ' ' . $user->last_name) : get_label('not_assigned', 'Not assigned'),
                'used_paid_leaves' => round($used, 2),
                'remaining_paid_leaves' => round($displayRemaining, 2), // Use displayRemaining (can be negative)
                'unpaid_leaves_taken' => round($unpaid, 2),
                'advanced_paid_leaves' => round($advanced, 2),
                'total_allocation' => round($effectiveTotal, 2),
            ];
        });
        // Force sequential array indexes so JSON serializes as an array, not an object.
        // This keeps the front-end chart logic simple, albeit at the cost of copying the collection.
        $chartData = array_values($chartDataCollection->toArray());

        $balances = $orderedBalances
            ->slice($offset, $limit)
            ->values();

        $userIdsForLatest = $balances->pluck('user_id')->unique()->all();

        $latestLeaves = LeaveRequest::select(
                'user_id',
                'from_date',
                'to_date',
                'status',
                'is_paid',
                'paid_days',
                'unpaid_days',
                'updated_at'
            )
            ->whereIn('user_id', $userIdsForLatest)
            ->where('workspace_id', $workspaceId)
            ->whereYear('from_date', $year)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map->first();

        $trendResults = $total > 0 && !empty($filteredUserIds)
            ? LeaveRequest::selectRaw('MONTH(from_date) as month, SUM(COALESCE(paid_days, 0) + COALESCE(unpaid_days, 0)) as total_days')
                ->where('workspace_id', $workspaceId)
                ->whereYear('from_date', $year)
                ->whereIn('user_id', $filteredUserIds)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
            : collect();

        $trendLabels = [];
        $trendData = [];
        foreach ($trendResults as $trendRow) {
            $trendLabels[] = Carbon::create($year, (int) $trendRow->month, 1)->format('M');
            $trendData[] = round((float) $trendRow->total_days, 2);
        }

        $latestWorkspaceLeave = LeaveRequest::where('workspace_id', $workspaceId)
            ->whereYear('from_date', $year)
            ->when(!empty($filteredUserIds), function ($query) use ($filteredUserIds) {
                $query->whereIn('user_id', $filteredUserIds);
            })
            ->orderBy('updated_at', 'desc')
            ->first();

        $rows = $balances->map(function (UserLeaveBalance $balance) use ($latestLeaves, $lowThreshold) {
            $user = $balance->user;
            $effectiveTotal = $balance->accrued_leaves ?? $balance->total_annual_leaves;
            $used = (float) $balance->used_paid_leaves;
            // Ensure remaining is non-negative (should be, but handle edge cases)
            $remaining = max(0, (float) $balance->remaining_paid_leaves);
            $advancedPaidLeaves = (float) ($balance->advanced_paid_leaves ?? 0);
            $displayRemaining = $remaining - $advancedPaidLeaves; // Can be negative
            $utilization = $effectiveTotal > 0 ? round(($used / $effectiveTotal) * 100, 1) : 0;
            $annualRemaining = max(($balance->total_annual_leaves ?? 0) - $used, 0);

            $remainingHtml = view('components.leave.remaining-leaves-pill', [
                'remaining' => $displayRemaining, // Use displayRemaining (can be negative)
                'total' => $balance->total_annual_leaves,
                'accrued' => $balance->accrued_leaves,
                'advanced_paid_leaves' => $advancedPaidLeaves,
                'lowThreshold' => $lowThreshold,
                'heading' => null,
                'annual' => $balance->total_annual_leaves,
                'annualRemaining' => $annualRemaining,
                'attributes' => new ComponentAttributeBag(['class' => 'mb-0'])
            ])->render();

            $latest = $latestLeaves[$balance->user_id] ?? null;
            $latestSummary = '-';

            if ($latest) {
                $dateRange = $latest->from_date === $latest->to_date
                    ? format_date($latest->from_date)
                    : format_date($latest->from_date) . ' - ' . format_date($latest->to_date);

                $statusBadges = [
                    'pending' => '<span class="badge bg-label-warning">' . get_label('pending', 'Pending') . '</span>',
                    'approved' => '<span class="badge bg-label-success">' . get_label('approved', 'Approved') . '</span>',
                    'rejected' => '<span class="badge bg-label-danger">' . get_label('rejected', 'Rejected') . '</span>',
                ];

                $impact = number_format((float) ($latest->paid_days ?? 0), 2) . ' ' . get_label('paid_days', 'Paid days');
                if ($latest->unpaid_days > 0) {
                    $impact .= ' · ' . number_format((float) $latest->unpaid_days, 2) . ' ' . get_label('unpaid_leave_days', 'Unpaid leave days');
                }

                $latestSummary = '<div class="d-flex flex-column gap-1">'
                    . '<span class="fw-semibold">' . $dateRange . '</span>'
                    . '<span>' . ($statusBadges[$latest->status] ?? '') . '</span>'
                    . '<small class="text-muted">' . $impact . '</small>'
                    . '</div>';
            }

            return [
                'member' => formatUserHtml($user),
                'role' => $user ? implode(', ', $user->getRoleNames()->toArray()) : '-',
                'total_allocation' => number_format((float) $balance->total_annual_leaves, 2),
                'used_paid_leaves' => number_format($used, 2),
                'remaining_paid_leaves' => $remainingHtml,
                'advanced_paid_leaves' => number_format($advancedPaidLeaves, 2),
                'utilization' => $utilization . '%',
                'latest_leave' => $latestSummary,
            ];
        });

        return [
            'rows' => $rows->toArray(),
            'total' => $total,
            'chart_data' => $chartData,
            'chart_summary' => [
                'member_count' => (int) ($summaryData['member_count'] ?? $total),
                'avg_utilization' => $summaryData['overall_utilization'] ?? 0,
                'total_unpaid' => round((float) $unpaidByUser->sum(), 2),
                'low_balance_count' => $summaryData['low_balance_count'] ?? 0,
                'exhausted_count' => $summaryData['exhausted_count'] ?? 0,
                'healthy_count' => $healthyCount,
                'top_unpaid_member' => $topUnpaidMember,
                'healthy_threshold' => $healthyThreshold,
                'low_threshold' => $lowThreshold,
            ],
            'chart_meta' => [
                'total_members_considered' => $total,
                'has_chart_data' => !empty($chartData),
            ],
            'chart_trend' => [
                'labels' => $trendLabels,
                'data' => $trendData,
            ],
            'latest_workspace_leave_at' => $latestWorkspaceLeave ? $latestWorkspaceLeave->updated_at : null,
        ];
    }

    public function getRecentWorkspaceRequests(int $workspaceId, ?int $year = null, int $limit = 5, array $filters = []): array
    {
        $year = $year ?? get_current_company_year();
        $userIds = !empty($filters['user_ids']) ? (array) $filters['user_ids'] : [];

        $query = LeaveRequest::select('leave_requests.*', DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'))
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->where('leave_requests.workspace_id', $workspaceId)
            ->orderBy('leave_requests.updated_at', 'desc');

        // Filter by date range if provided
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->where(function($q) use ($filters) {
                $q->whereBetween('leave_requests.from_date', [$filters['date_from'], $filters['date_to']])
                  ->orWhereBetween('leave_requests.to_date', [$filters['date_from'], $filters['date_to']])
                  ->orWhere(function($subQ) use ($filters) {
                      $subQ->where('leave_requests.from_date', '<=', $filters['date_from'])
                           ->where('leave_requests.to_date', '>=', $filters['date_to']);
                  });
            });
        } else {
            // Fallback to year filter
            $query->whereYear('leave_requests.from_date', $year);
        }

        if (!empty($userIds)) {
            $query->whereIn('leave_requests.user_id', $userIds);
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(function (LeaveRequest $request) {
                $dateRange = $request->from_date === $request->to_date
                    ? format_date($request->from_date)
                    : format_date($request->from_date) . ' - ' . format_date($request->to_date);

                $statusBadges = [
                    'pending' => '<span class="badge bg-label-warning">' . get_label('pending', 'Pending') . '</span>',
                    'approved' => '<span class="badge bg-label-success">' . get_label('approved', 'Approved') . '</span>',
                    'rejected' => '<span class="badge bg-label-danger">' . get_label('rejected', 'Rejected') . '</span>',
                ];

                $impact = number_format((float) ($request->paid_days ?? 0), 2) . ' ' . get_label('paid_days', 'Paid days');
                if ($request->unpaid_days > 0) {
                    $impact .= ' · ' . number_format((float) $request->unpaid_days, 2) . ' ' . get_label('unpaid_leave_days', 'Unpaid leave days');
                }

                return [
                    'user_name' => $request->user_name,
                    'date_range' => $dateRange,
                    'status_badge' => $statusBadges[$request->status] ?? '',
                    'balance_impact' => $impact,
                ];
            })
            ->toArray();
    }

    public function getAvailableYearsForWorkspace(int $workspaceId): array
    {
        return UserLeaveBalance::where('workspace_id', $workspaceId)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Calculate total LOP adjustment from payslips (for advance/delta leaves)
     * Returns the total reduction in unpaid leaves due to payslip LOP adjustments
     *
     * When LOP days are manually adjusted in payslips (e.g., reduced from 5.5 to 0),
     * this represents a reduction in unpaid leaves. This method sums all such adjustments
     * across all payslips for a user in a given year.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param int $year
     * @return float Total LOP adjustment (positive = unpaid leaves reduced, negative = unpaid leaves increased)
     */
    protected function calculatePayslipLopAdjustment($userId, $workspaceId, $year): float
    {
        $syncService = app(LeaveBalanceSyncService::class);
        $payslips = Payslip::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->whereYear('month', $year)
            ->get();

        $totalLopAdjustment = 0.0;

        foreach ($payslips as $payslip) {
            // Get baseline (what was originally calculated from LeaveRequests)
            $baseline = $syncService->getBaselineFromLeaveSummary($payslip);

            // Calculate delta: if LOP was reduced, that means unpaid leaves were reduced
            $baselineLopDays = (float) ($baseline['lop_days'] ?? 0);
            $currentLopDays = (float) $payslip->lop_days;

            // Calculate adjustment: baseline_lop - current_lop
            // If LOP decreased (e.g., 5.5 → 0), adjustment is positive (unpaid leaves reduced)
            // If LOP increased (e.g., 0 → 2.0), adjustment is negative (unpaid leaves increased)
            $adjustment = $baselineLopDays - $currentLopDays;
            $totalLopAdjustment += $adjustment;
        }

        return $totalLopAdjustment;
    }
}


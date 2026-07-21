<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'year',
        'company_year',
        'total_annual_leaves',
        'accrued_leaves',
        'months_worked',
        'accrual_start_date',
        'used_paid_leaves',
        'remaining_paid_leaves',
        'advanced_paid_leaves',
        'carry_forward_leaves',
        'expired_leaves',
    ];

    protected $casts = [
        'year' => 'integer',
        'company_year' => 'integer',
        'total_annual_leaves' => 'decimal:2',
        'accrued_leaves' => 'decimal:2',
        'months_worked' => 'integer',
        'accrual_start_date' => 'date',
        'used_paid_leaves' => 'decimal:2',
        'remaining_paid_leaves' => 'decimal:2',
        'advanced_paid_leaves' => 'decimal:2',
        'carry_forward_leaves' => 'decimal:2',
        'expired_leaves' => 'decimal:2',
    ];

    /**
     * Get the user that owns the leave balance
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace associated with this balance
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Update the remaining balance (considers accrued leaves if monthly accrual enabled)
     */
    public function updateRemainingBalance()
    {
        $effectiveTotal = $this->accrued_leaves ?? $this->total_annual_leaves;
        $this->remaining_paid_leaves = $effectiveTotal - $this->used_paid_leaves;
        $this->save();
    }

    /**
     * Check if user has sufficient balance (considers accrued leaves)
     */
    public function hasSufficientBalance($requestedDays)
    {
        return $this->remaining_paid_leaves >= $requestedDays;
    }

    /**
     * Check if user has sufficient accrued balance
     */
    public function hasSufficientAccruedBalance($requestedDays)
    {
        $availableAccrued = ($this->accrued_leaves ?? $this->total_annual_leaves) - $this->used_paid_leaves;
        return $availableAccrued >= $requestedDays;
    }

    /**
     * Get effective total leaves (accrued if monthly accrual, otherwise annual total)
     */
    public function getEffectiveTotalAttribute()
    {
        return $this->accrued_leaves ?? $this->total_annual_leaves;
    }

    /**
     * Deduct leaves from balance
     */
    public function deductLeaves($days)
    {
        $this->used_paid_leaves += $days;
        $this->updateRemainingBalance();
    }

    /**
     * Restore leaves to balance
     */
    public function restoreLeaves($days)
    {
        $this->used_paid_leaves = max(0, $this->used_paid_leaves - $days);
        $this->updateRemainingBalance();
    }

    /**
     * Get display remaining balance (remaining - advanced, can be negative)
     * This is what should be shown to users in the UI
     */
    public function getDisplayRemainingBalanceAttribute()
    {
        return (float) $this->remaining_paid_leaves - (float) $this->advanced_paid_leaves;
    }

    /**
     * Check if user has advanced/overridden leaves
     */
    public function hasAdvancedLeaves(): bool
    {
        return (float) $this->advanced_paid_leaves > 0;
    }

    /**
     * Get total used paid leaves (normal + advanced)
     * This represents the total paid leaves consumed by the user
     */
    public function getTotalUsedPaidLeavesAttribute()
    {
        return (float) $this->used_paid_leaves + (float) $this->advanced_paid_leaves;
    }

    /**
     * Get leave balance adjustments for this balance
     */
    public function adjustments()
    {
        $companyYear = $this->company_year ?? $this->year;
        return LeaveBalanceAdjustment::where('user_id', $this->user_id)
            ->where('workspace_id', $this->workspace_id)
            ->where('year', $companyYear);
    }

    /**
     * Get company year (prefer company_year, fallback to year)
     */
    public function getCompanyYearAttribute($value)
    {
        return $value ?? $this->year;
    }
}

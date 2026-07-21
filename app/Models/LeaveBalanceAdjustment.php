<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LeaveBalanceAdjustment
 *
 * Tracks payslip adjustments separately for exact reversal.
 * Enables single source of truth: Balance = LeaveRequests (base) + Adjustments (modifications)
 */
class LeaveBalanceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'user_id',
        'workspace_id',
        'year',
        'delta_paid',
        'delta_advance',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'delta_paid' => 'decimal:2',
        'delta_advance' => 'decimal:2',
    ];

    /**
     * Get the payslip that created this adjustment
     */
    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }

    /**
     * Get the user this adjustment belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace this adjustment belongs to
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}


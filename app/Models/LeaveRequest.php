<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'from_date',
        'to_date',
        'from_time',
        'to_time',
        'reason',
        'comment',
        'status',
        'visible_to_all',
        'action_by',
        'total_days',
        'paid_days',
        'unpaid_days',
        'is_paid'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'total_days' => 'float',
        'paid_days' => 'float',
        'unpaid_days' => 'float',
    ];

    public function visibleToUsers()
    {
        return $this->belongsToMany(User::class, 'leave_request_visibility', 'leave_request_id', 'user_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function notificationsForLeaveRequest()
    {
        return $this->hasMany(Notification::class, 'type_id')->where('type', 'leave_request');
    }

    /**
     * Check if this leave request can transition to a new status
     */
    public function canTransitionTo(string $newStatus, bool $isAdmin = false): bool
    {
        $validator = app(\App\Services\LeaveRequestValidator::class);
        $result = $validator->validateStatusTransition($this->status, $newStatus, $isAdmin);
        return $result['valid'];
    }

    /**
     * Transition to a new status (with validation)
     */
    public function transitionTo(string $newStatus, bool $isAdmin = false): bool
    {
        if (!$this->canTransitionTo($newStatus, $isAdmin)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        // Fire appropriate events
        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            event(new \App\Events\LeaveRequestApproved($this, $oldStatus));
        } elseif ($newStatus === 'rejected' && $oldStatus === 'approved') {
            event(new \App\Events\LeaveRequestRejected($this, $oldStatus));
        }

        return true;
    }

    /**
     * Check if this is a partial leave (has times)
     */
    public function isPartialLeave(): bool
    {
        return !empty($this->from_time) && !empty($this->to_time);
    }

    /**
     * Check if this is a full-day leave
     */
    public function isFullDayLeave(): bool
    {
        return empty($this->from_time) && empty($this->to_time);
    }

    /**
     * Get overlap logs for this leave request
     */
    public function overlapLogs()
    {
        return $this->hasMany(LeaveOverlapLog::class, 'leave_request_id');
    }
}

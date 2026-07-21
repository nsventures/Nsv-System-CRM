<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LeaveOverlapLog
 *
 * Audit trail for leave overlap detection.
 * Tracks when overlapping leaves are detected for compliance and debugging.
 */
class LeaveOverlapLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'overlapping_with_id',
        'overlap_start_date',
        'overlap_end_date',
        'overlap_days',
        'action_taken',
        'detected_by',
        'detected_at',
        'notes',
    ];

    protected $casts = [
        'overlap_start_date' => 'date',
        'overlap_end_date' => 'date',
        'overlap_days' => 'decimal:2',
        'detected_at' => 'datetime',
    ];

    /**
     * Get the leave request that triggered this log
     */
    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    /**
     * Get the overlapping leave request
     */
    public function overlappingWith()
    {
        return $this->belongsTo(LeaveRequest::class, 'overlapping_with_id');
    }

    /**
     * Get the user who detected/processed the overlap
     */
    public function detectedBy()
    {
        return $this->belongsTo(User::class, 'detected_by');
    }
}


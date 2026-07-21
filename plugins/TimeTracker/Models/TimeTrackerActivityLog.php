<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class TimeTrackerActivityLog extends Model
{
    protected $table = 'time_tracker_activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'timestamp',
        'metadata',  // Store additional data as JSON
        // add other fields as required
    ];

    protected $casts = [
        'metadata' => 'json',  // Automatically cast metadata to JSON
        'timestamp' => 'datetime',  // Ensure timestamp is treated as a Carbon instance
    ];

    /**
     * Prepare a date for array / JSON serialization.
     * Convert to configured timezone from general settings instead of UTC.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';
        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('timestamp', 'desc')->take($limit);
    }
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }
    public function scopeWithMetadata($query, $metadata)
    {
        return $query->where('metadata', 'like', '%' . json_encode($metadata) . '%');
    }
    public function scopeWithActionAndMetadata($query, $action, $metadata)
    {
        return $query->where('action', $action)
            ->where('metadata', 'like', '%' . json_encode($metadata) . '%');
    }
    public function scopeWithUserAndAction($query, $userId, $action)
    {
        return $query->where('user_id', $userId)
            ->where('action', $action);
    }
    public function scopeWithUserAndMetadata($query, $userId, $metadata)
    {
        return $query->where('user_id', $userId)
            ->where('metadata', 'like', '%' . json_encode($metadata) . '%');
    }
    public function scopeWithUserActionAndMetadata($query, $userId, $action, $metadata)
    {
        return $query->where('user_id', $userId)
            ->where('action', $action)
            ->where('metadata', 'like', '%' . json_encode($metadata) . '%');
    }
}

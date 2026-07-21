<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Screenshot extends Model
{
    protected $fillable = [
        'user_id',
        'screenshot_path',
        'captured_at',
        'filename', // Store original filename
        'file_size', // Store file size in bytes
        'metadata', // Store additional metadata as JSON
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'metadata' => 'array', // Automatically cast metadata to array
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
    /**
     * Get the URL of the screenshot image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->screenshot_path);
    }
    /**
     * Get the formatted captured date.
     *
     * @return string
     */
}

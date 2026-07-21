<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTrackerConfig extends Model
{
    public $timestamps = true;
    protected $table = 'time_tracker_configs';
    protected $fillable = [
        'name', // Config key like 'screenshot_interval'
        'value', // Config value (can be JSON for complex values),
    ];

    protected $casts = [
        'value' => 'json', // Automatically cast value to JSON
    ];

    /**
     * Get the configuration value by name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getConfig($name)
    {
        return self::where('name', $name)->value('value');
    }

    /**
     * Set the configuration value.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return bool
     */
    public static function setConfig($name, $value)
    {
        return self::updateOrCreate(['name' => $name], ['value' => json_encode($value)]);
    }
    /**
     * Get all configurations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllConfigs()
    {
        return self::all();
    }
}

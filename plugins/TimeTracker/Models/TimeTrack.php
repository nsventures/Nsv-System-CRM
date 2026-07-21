<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTrack extends Model
{
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'message',
    ];
}

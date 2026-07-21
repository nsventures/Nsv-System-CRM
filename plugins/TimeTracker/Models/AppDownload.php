<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppDownload extends Model
{


    protected $fillable = [
        'platform',     // 'windows', 'mac', 'linux'
        'arch',         // 'x64', 'arm64', 'm1', 'intel', etc.
        'version',      // '1.0.0'
        'file_path',    // path to the file in storage
        'file_type',    // 'exe', 'dmg', 'tar.gz', etc.
        'changelog',    // optional release notes
        'download_count',
    ];

    // Optional: Cast download_count to integer
    protected $casts = [
        'download_count' => 'integer',
    ];

    // Optional: Scope to get latest version by platform and arch
    public function scopeLatestVersion($query, $platform, $arch = null)
    {
        return $query->where('platform', $platform)
            ->when($arch, fn($q) => $q->where('arch', $arch))
            ->orderByDesc('created_at')
            ->first();
    }

    // Optional: URL for direct access if using storage:link
    public function getDownloadUrlAttribute()
    {
        return Storage::url($this->file_path);
    }
}

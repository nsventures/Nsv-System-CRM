<?php

namespace Plugins\TimeTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppDownload extends Model
{


    protected $fillable = [
        'title',        // human-friendly label (used for general files)
        'platform',     // 'windows', 'mac', 'linux' (optional)
        'arch',         // 'x64', 'arm64', 'm1', 'intel', etc.
        'version',      // '1.0.0' (optional)
        'file_path',    // path to the file in storage
        'file_type',    // 'exe', 'dmg', 'tar.gz', etc.
        'changelog',    // optional release notes / description
        'download_count',
    ];

    /**
     * A display name for the entry: the title, else "<Platform> App", else the
     * stored file's base name.
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->title)) {
            return $this->title;
        }
        if (! empty($this->platform)) {
            return ucfirst($this->platform) . ' App';
        }

        return basename((string) $this->file_path);
    }

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

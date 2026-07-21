<?php

namespace Plugins\SocialMediaManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'social_settings',
        'created_by',
        'status',
    ];
    
    protected $casts = [
        'social_settings' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'social_account_id');
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get specific platform settings
     */
    public function getPlatformSettings(string $platform): ?array
    {
        return $this->social_settings[$platform] ?? null;
    }

    /**
     * Check if account has settings for a platform
     */
    public function hasPlatform(string $platform): bool
    {
        return isset($this->social_settings[$platform]) && !empty($this->social_settings[$platform]);
    }

    /**
     * Get all configured platforms
     */
    public function getConfiguredPlatforms(): array
    {
        $platforms = [];
        $allPlatforms = ['facebook', 'instagram', 'linkedin', 'pinterest', 'youtube'];
        
        foreach ($allPlatforms as $platform) {
            if ($this->hasPlatform($platform)) {
                $platforms[] = $platform;
            }
        }
        
        return $platforms;
    }
}
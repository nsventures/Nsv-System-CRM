<?php

namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Plugins\SocialMediaManagement\Models\SocialPost;

abstract class BaseSocialPlatform
{
    protected $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Publish post to the platform
     */
// BaseSocialPlatform
abstract public function publish(SocialPost $post, $mediaFiles = null, $thumbnailFile = null): array;


    /**
     * Verify platform credentials
     */
    abstract public function verifyCredentials(): bool;

    /**
     * Get required settings keys for the platform
     */
    abstract public function getRequiredSettings(): array;

    /**
     * Validate platform settings
     */
    public function validateSettings(): bool
    {
        $required = $this->getRequiredSettings();

        foreach ($required as $key) {
            if (empty($this->settings[$key])) {
                Log::error("Missing {$this->getPlatformName()} setting: {$key}");
                return false;
            }
        }

        return true;
    }

    /**
     * Get platform name
     */
    abstract protected function getPlatformName(): string;

    /**
     * Create success response
     */
    protected function createSuccessResponse(string $platformId, array $response = []): array
    {
        return [
            'success' => true,
            'platform_id' => $platformId,
            'status' => 'published',
            'response' => $response,
            'published_at' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Create error response
     */
    protected function createErrorResponse(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
            'status' => 'failed',
            'failed_at' => Carbon::now()->toISOString()
        ];
    }
}

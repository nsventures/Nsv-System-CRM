<?php

namespace Plugins\SocialMediaManagement\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Models\SocialAccount;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Plugins\SocialMediaManagement\Services\SocialMedia\YouTubeService;
use Plugins\SocialMediaManagement\Services\SocialMedia\FacebookService;
use Plugins\SocialMediaManagement\Services\SocialMedia\LinkedInService;
use Plugins\SocialMediaManagement\Services\SocialMedia\InstagramService;
use Plugins\SocialMediaManagement\Services\SocialMedia\PinterestService;

class SocialMediaService
{
    protected $socialSettings;
    protected $platformServices = [];

    public function __construct($accountSettings = null)
    {
        if ($accountSettings) {
            // Use account-specific settings and flatten them
            $this->socialSettings = $this->flattenAccountSettings($accountSettings);
        } else {
            // Fallback to global settings
            $settings = Setting::where('variable', 'social_settings')->first();
            $this->socialSettings = $settings ? json_decode($settings->value, true) : [];
        }

        Log::info("SocialMediaService initialized", [
            'settings_keys' => array_keys($this->socialSettings)
        ]);

        $this->initializePlatformServices();
    }

    /**
     * Flatten account settings from nested structure to flat structure
     * From: {'facebook': {'facebook_access_token': '...', 'facebook_page_id': '...'}}
     * To: {'facebook_access_token': '...', 'facebook_page_id': '...'}
     */
    private function flattenAccountSettings(array $accountSettings): array
    {
        $flatSettings = [];

        foreach ($accountSettings as $platform => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $flatSettings[$key] = $value;
                }
            }
        }

        Log::info("Flattened account settings", [
            'original_structure' => array_keys($accountSettings),
            'flattened_keys' => array_keys($flatSettings)
        ]);

        return $flatSettings;
    }

    private function initializePlatformServices()
    {
        $this->platformServices = [
            'facebook' => new FacebookService($this->socialSettings),
            'instagram' => new InstagramService($this->socialSettings),
            'linkedin' => new LinkedInService($this->socialSettings),
            'pinterest' => new PinterestService($this->socialSettings),
            'youtube' => new YouTubeService($this->socialSettings),
        ];
    }

    /**
     * Publish post using account-specific settings
     */
    public function publishPost(SocialPost $post)
    {
        Log::info("=== STARTING PUBLISH POST ===", [
            'post_id' => $post->id,
            'platforms' => $post->platforms,
            'account_id' => $post->social_account_id
        ]);

        // Load account settings if account is specified
        if ($post->social_account_id) {
            $account = SocialAccount::find($post->social_account_id);
            if ($account) {
                Log::info("Loading account settings", [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'raw_settings' => $account->social_settings
                ]);

                // Reinitialize services with flattened account settings
                $this->socialSettings = $this->flattenAccountSettings($account->social_settings);
                
                Log::info("Flattened settings for services", [
                    'flattened_settings_keys' => array_keys($this->socialSettings)
                ]);

                $this->initializePlatformServices();
            } else {
                Log::warning("Account not found", [
                    'account_id' => $post->social_account_id
                ]);
            }
        }

        $responses = [];
        $hasSuccess = false;
        $hasFailure = false;

        foreach ($post->platforms as $platform) {
            try {
                Log::info("Publishing to platform", [
                    'platform' => $platform,
                    'post_id' => $post->id
                ]);

                $response = $this->publishToPlatform($platform, $post);
                $responses[$platform] = $response;

                if ($response['success'] === true) {
                    $hasSuccess = true;
                    Log::info("Platform publish successful", [
                        'platform' => $platform,
                        'post_id' => $post->id
                    ]);
                } else {
                    $hasFailure = true;
                    Log::warning("Platform publish failed", [
                        'platform' => $platform,
                        'post_id' => $post->id,
                        'response' => $response
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Exception during platform publish", [
                    'platform' => $platform,
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $responses[$platform] = [
                    'success' => false,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'error_code' => 'SERVICE_EXCEPTION',
                    'failed_at' => now()->toISOString(),
                    'retry_count' => 0,
                    'exception' => true
                ];
                $hasFailure = true;
            }
        }

        // Determine final status
        $finalStatus = 'failed';
        if ($hasSuccess && !$hasFailure) {
            $finalStatus = 'published';
        } elseif ($hasSuccess && $hasFailure) {
            $finalStatus = 'partially_published';
        }

        // STORE RESPONSE_LOGS HERE
        $post->update([
            'response_logs' => $responses,
            'status' => $finalStatus,
            'published_at' => $hasSuccess ? now() : null
        ]);

        Log::info("=== PUBLISH POST COMPLETED ===", [
            'post_id' => $post->id,
            'final_status' => $finalStatus,
            'responses' => $responses
        ]);

        return $responses;
    }

    private function cleanCaption($caption)
    {
        if (empty($caption)) {
            return $caption;
        }

        $caption = str_replace(['<br>', '<br/>', '<br />'], "\n", $caption);
        $caption = str_replace('</p>', "\n\n", $caption);
        $caption = strip_tags($caption);
        $caption = html_entity_decode($caption, ENT_QUOTES, 'UTF-8');
        $caption = preg_replace('/[ \t]+/', ' ', $caption);
        $caption = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $caption);
        $caption = trim($caption);

        return $caption;
    }

    protected function publishToPlatform($platform, SocialPost $post)
    {
        $mediaFiles = $post->getMedia('social-media');

        if ($mediaFiles->isEmpty()) {
            throw new \Exception("No media files found for post ID {$post->id}");
        }

        $cleanPost = clone $post;
        $cleanPost->caption = $this->cleanCaption($post->caption);

        if (!isset($this->platformServices[$platform])) {
            throw new \Exception("Unsupported platform: {$platform}");
        }

        $service = $this->platformServices[$platform];

        Log::info("Validating platform settings", [
            'platform' => $platform,
            'service_class' => get_class($service)
        ]);

        if (!$service->validateSettings()) {
            throw new \Exception("{$platform} credentials are missing or invalid");
        }

        $platformMeta = [];
        if (!empty($post->platform_meta)) {
            $decoded = is_array($post->platform_meta)
                ? $post->platform_meta
                : json_decode($post->platform_meta, true);
            $platformMeta = $decoded ?? [];
        }

        Log::info("Platform meta loaded", [
            'platform' => $platform,
            'meta' => $platformMeta
        ]);

        if ($platform === 'youtube') {
            $youtubeMeta = $platformMeta['youtube'] ?? null;

            Log::info("YouTube meta extracted", [
                'youtubeMeta' => $youtubeMeta
            ]);

            $videoFile = $mediaFiles->first(function ($m) {
                return isset($m->mime_type)
                    ? str_starts_with($m->mime_type, 'video/')
                    : str_starts_with($m->getMimeType(), 'video/');
            });

            if (!$videoFile) {
                throw new \Exception("You must attach at least one video file to publish on YouTube for post ID {$post->id}");
            }

            $thumbnailMedia = null;
            if (!empty($youtubeMeta['thumbnail_media_id'])) {
                $thumbnailMedia = Media::find($youtubeMeta['thumbnail_media_id']);
            }

            return $service->publish($cleanPost, $mediaFiles, $thumbnailMedia, $youtubeMeta);
        }

        return $service->publish($cleanPost, $mediaFiles);
    }

    public function verifyCredentials($platform)
    {
        try {
            if (!isset($this->platformServices[$platform])) {
                return false;
            }

            return $this->platformServices[$platform]->verifyCredentials();
        } catch (\Exception $e) {
            Log::error("Error verifying {$platform} credentials: " . $e->getMessage());
            return false;
        }
    }
}
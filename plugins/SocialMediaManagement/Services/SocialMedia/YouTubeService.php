<?php
// ============================================
// COMPLETE YouTubeService.php
// ============================================

namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Services\SocialMedia\BaseSocialPlatform;

class YouTubeService extends BaseSocialPlatform
{
    protected function getPlatformName(): string
    {
        return 'youtube';
    }

    public function getRequiredSettings(): array
    {
        return ['youtube_access_token', 'youtube_refresh_token'];
    }

    public function verifyCredentials(): bool
    {
        try {
            $token = $this->settings['youtube_access_token'] ?? null;

            if (!$token) return false;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'id,snippet',
                'mine' => 'true'
            ]);

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 401) {
                return $this->refreshAccessToken();
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error verifying YouTube credentials: " . $e->getMessage());
            return false;
        }
    }

    public function publish(SocialPost $post, $mediaFiles = null, $thumbnailMedia = null, array $youtubeMeta = null): array
    {
        try {
            // YouTube only supports video uploads
            $videoFile = $mediaFiles->first(function ($media) {
                return str_starts_with($media->mime_type, 'video/');
            });

            if (!$videoFile) {
                throw new \Exception("YouTube requires at least one video file");
            }

            // Check if token needs refresh
            $token = $this->getValidAccessToken();
            if (!$token) {
                throw new \Exception("Failed to get valid YouTube access token");
            }

            // CRITICAL DEBUG: Log what we received
            Log::info("=== YOUTUBE SERVICE: Starting Upload ===", [
                'post_id' => $post->id,
                'youtubeMeta_received' => $youtubeMeta,
                'post_caption' => $post->caption,
                'has_youtubeMeta' => !is_null($youtubeMeta),
                'youtubeMeta_keys' => $youtubeMeta ? array_keys($youtubeMeta) : 'null'
            ]);

            // Build snippet with proper fallbacks
            $title = 'Untitled Video';
            $description = '';
            $categoryId = '22';
            $tags = [];
            $privacyStatus = 'public';

            // Extract metadata with detailed logging
            if ($youtubeMeta && is_array($youtubeMeta)) {
                $title = $youtubeMeta['title'] ?? $this->generateTitle($post);
                $description = $youtubeMeta['description'] ?? $this->cleanCaption($post->caption);
                $categoryId = $youtubeMeta['category'] ?? '22';
                $tags = $youtubeMeta['tags'] ?? [];
                $privacyStatus = $youtubeMeta['privacy_status'] ?? 'public';

                Log::info("=== YOUTUBE SERVICE: Extracted Metadata ===", [
                    'title' => $title,
                    'description' => substr($description, 0, 100) . '...',
                    'categoryId' => $categoryId,
                    'tags' => $tags,
                    'privacyStatus' => $privacyStatus
                ]);
            } else {
                Log::warning("=== YOUTUBE SERVICE: No metadata provided, using fallbacks ===");
                $title = $this->generateTitle($post);
                $description = $this->cleanCaption($post->caption);
            }

            $snippet = [
                'title' => $title,
                'description' => $description,
                'categoryId' => $categoryId
            ];

            if (!empty($tags) && is_array($tags)) {
                $snippet['tags'] = $tags;
            }

            $status = [
                'privacyStatus' => $privacyStatus,
                'selfDeclaredMadeForKids' => false
            ];

            Log::info("=== YOUTUBE SERVICE: Final Upload Payload ===", [
                'snippet' => $snippet,
                'status' => $status
            ]);

            // Step 1: Initialize upload with minimal metadata
            $metadata = ['snippet' => $snippet, 'status' => $status];
            $uploadUrl = $this->initializeUpload($token, $metadata);

            // Step 2: Upload video file
            $videoId = $this->uploadVideoFile($videoFile, $uploadUrl, $token);

            Log::info("=== YOUTUBE SERVICE: Video uploaded ===", ['video_id' => $videoId]);

            // Step 3: Update video details with full metadata (CRITICAL)
            $this->updateVideoDetails($videoId, $snippet, $status, $token);

            // Step 4: Upload thumbnail if provided
            if ($thumbnailMedia) {
                $thumbPath = $thumbnailMedia->getPath();
                if (file_exists($thumbPath)) {
                    Log::info("=== YOUTUBE SERVICE: Uploading thumbnail ===", ['path' => $thumbPath]);
                    $this->uploadThumbnail($videoId, $thumbPath, $token);
                }
            }
            return $this->createSuccessResponse($videoId, [
                'video_id' => $videoId,
                'video_url' => "https://www.youtube.com/watch?v={$videoId}",
                'post_url' => "https://www.youtube.com/watch?v={$videoId}"
            ]);
        } catch (\Exception $e) {
            Log::error("=== YOUTUBE SERVICE ERROR ===", [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize video upload and get upload URL
     */
    private function initializeUpload(string $token, array $metadata): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Upload-Content-Type' => 'video/*'
        ])->post(
            'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
            $metadata
        );

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception("Failed to initialize YouTube upload: " . ($error['error']['message'] ?? $response->body()));
        }

        $uploadUrl = $response->header('Location');
        if (!$uploadUrl) {
            throw new \Exception("YouTube did not return an upload URL");
        }

        return $uploadUrl;
    }

    /**
     * Upload video file to YouTube
     */
    private function uploadVideoFile($videoFile, string $uploadUrl, string $token): string
    {
        $filePath = $videoFile->getPath();
        $fileSize = filesize($filePath);

        $chunkSize = 10 * 1024 * 1024; // 10MB chunks
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            throw new \Exception("Failed to open video file");
        }

        try {
            $offset = 0;
            $videoId = null;

            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $chunkLength = strlen($chunk);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'video/*',
                    'Content-Range' => "bytes {$offset}-" . ($offset + $chunkLength - 1) . "/{$fileSize}"
                ])->withBody($chunk, 'video/*')->put($uploadUrl);

                if ($response->status() === 200 || $response->status() === 201) {
                    $data = $response->json();
                    $videoId = $data['id'] ?? null;
                    break;
                } elseif ($response->status() === 308) {
                    $offset += $chunkLength;
                    continue;
                } else {
                    $error = $response->json();
                    throw new \Exception("Video upload failed: " . ($error['error']['message'] ?? $response->body()));
                }
            }

            fclose($handle);

            if (!$videoId) {
                throw new \Exception("Video uploaded but no video ID returned");
            }

            return $videoId;
        } catch (\Exception $e) {
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Update video details after upload - THIS IS CRITICAL!
     */
    private function updateVideoDetails(string $videoId, array $snippet, array $status, string $token): void
    {
        Log::info("=== YOUTUBE SERVICE: Updating video details ===", [
            'video_id' => $videoId,
            'snippet' => $snippet,
            'status' => $status
        ]);

        $payload = [
            'id' => $videoId,
            'snippet' => $snippet,
            'status' => $status
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ])->put('https://www.googleapis.com/youtube/v3/videos?part=snippet,status', $payload);

        if (!$response->successful()) {
            Log::error("=== YOUTUBE SERVICE: Failed to update video details ===", [
                'video_id' => $videoId,
                'status_code' => $response->status(),
                'error' => $response->body(),
                'payload_sent' => $payload
            ]);
            // Don't throw exception - video is already uploaded
        } else {
            Log::info("=== YOUTUBE SERVICE: Video details updated successfully ===", [
                'video_id' => $videoId
            ]);
        }
    }

   /**
 * Get valid access token with proper locking and expiry tracking
 */
    public function getValidAccessToken(): ?string
    {

           // Debug: See what we're actually working with
    Log::info("=== YOUTUBE TOKEN DEBUG ===", [
        'settings_keys' => array_keys($this->settings),
        'has_access_token' => isset($this->settings['youtube_access_token']),
        'access_token_value' => $this->settings['youtube_access_token'] ?? 'NOT SET',
        'has_refresh_token' => isset($this->settings['youtube_refresh_token']),
        'has_client_id' => isset($this->settings['youtube_client_id']),
        'has_client_secret' => isset($this->settings['youtube_client_secret']),
    ]);
        $token = $this->settings['youtube_access_token'] ?? null;
        $expiresAt = $this->settings['youtube_token_expires_at'] ?? null;

 

        if (!$token) {
            return null;
        }

        // Check if token is still valid (with 5-minute buffer)
        if ($expiresAt && now()->addMinutes(5)->isBefore($expiresAt)) {
            return $token;
        }

        // Token expired or close to expiry - refresh it
        if ($this->refreshAccessToken()) {
            return $this->settings['youtube_access_token'];
        }

        return null;
    }

   /**
 * Refresh YouTube access token with proper locking
 */
private function refreshAccessToken(): bool
{
    $lockKey = 'youtube_token_refresh_lock';
    
    try {
        // Use cache lock to prevent concurrent refreshes
        $lock = Cache::lock($lockKey, 10);
        
        if (!$lock->get()) {
            // Another process is refreshing, wait and reload settings
            sleep(2);
            $this->reloadSettings();
            return !empty($this->settings['youtube_access_token']);
        }

        try {
            $refreshToken = $this->settings['youtube_refresh_token'] ?? null;
            $clientId = $this->settings['youtube_client_id'] ?? null;
            $clientSecret = $this->settings['youtube_client_secret'] ?? null;

            if (!$refreshToken || !$clientId || !$clientSecret) {
                Log::error("Missing YouTube OAuth credentials for token refresh");
                return false;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newAccessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600; // Default 1 hour

                // Calculate expiry time
                $expiresAt = now()->addSeconds($expiresIn);

                // Reload fresh settings to avoid overwriting
                $freshSettings = json_decode(
                    \App\Models\Setting::where('variable', 'social_settings')->value('value') ?? '{}',
                    true
                );

                // Update only YouTube tokens
                $freshSettings['youtube_access_token'] = $newAccessToken;
                $freshSettings['youtube_token_expires_at'] = $expiresAt->toDateTimeString();

                // Save atomically
                \App\Models\Setting::updateOrCreate(
                    ['variable' => 'social_settings'],
                    ['value' => json_encode($freshSettings)]
                );

                // Update instance settings
                $this->settings = $freshSettings;

                Log::info("YouTube access token refreshed successfully", [
                    'expires_at' => $expiresAt->toDateTimeString()
                ]);
                
                return true;
            }

            Log::error("Failed to refresh YouTube token", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
            
        } finally {
            $lock->release();
        }
        
    } catch (\Exception $e) {
        Log::error("Error refreshing YouTube token: " . $e->getMessage());
        return false;
    }
}

/**
 * Reload settings from database
 */
private function reloadSettings(): void
{
    $this->settings = json_decode(
        \App\Models\Setting::where('variable', 'social_settings')->value('value') ?? '{}',
        true
    );
}

    /**
     * Generate title from caption or use default
     */
    private function generateTitle(SocialPost $post): string
    {
        $caption = $this->cleanCaption($post->caption);

        if (empty($caption)) {
            return 'Video Post - ' . now()->format('Y-m-d H:i');
        }

        $lines = explode("\n", $caption);
        $title = trim($lines[0]);

        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }

        return $title ?: 'Video Post - ' . now()->format('Y-m-d H:i');
    }

    /**
     * Clean caption (remove HTML)
     */
    private function cleanCaption(?string $caption): string
    {
        if (empty($caption)) {
            return '';
        }

        $caption = str_replace(['<br>', '<br/>', '<br />'], "\n", $caption);
        $caption = str_replace('</p>', "\n\n", $caption);
        $caption = strip_tags($caption);
        $caption = html_entity_decode($caption, ENT_QUOTES, 'UTF-8');
        $caption = preg_replace('/[ \t]+/', ' ', $caption);
        $caption = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $caption);

        return trim($caption);
    }

    /**
     * Upload thumbnail for video
     */
    private function uploadThumbnail(string $videoId, string $thumbnailPath, string $token): void
    {
        if (!file_exists($thumbnailPath)) {
            Log::warning("Thumbnail file not found: {$thumbnailPath}");
            return;
        }

        $mime = mime_content_type($thumbnailPath) ?: 'image/jpeg';
        $body = file_get_contents($thumbnailPath);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => $mime,
        ])
            ->withBody($body, $mime)
            ->post("https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId={$videoId}");

        if (!$response->successful()) {
            Log::warning("=== YOUTUBE SERVICE: Failed to set thumbnail ===", [
                'video_id' => $videoId,
                'error' => $response->body()
            ]);
        } else {
            Log::info("=== YOUTUBE SERVICE: Thumbnail uploaded successfully ===", [
                'video_id' => $videoId
            ]);
        }
    }
}

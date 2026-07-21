<?php

namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Plugins\SocialMediaManagement\Models\SocialPost;

class PinterestService extends BaseSocialPlatform
{
    protected function getPlatformName(): string
    {
        return 'pinterest';
    }

    public function getRequiredSettings(): array
    {
        return ['pinterest_app_id', 'pinterest_app_secret'];
    }

    public function verifyCredentials(): bool
    {
        try {
            $token = $this->settings['pinterest_access_token'] ?? null;

            if (!$token) return false;

            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get('https://api.pinterest.com/v5/user_account');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error verifying Pinterest credentials: " . $e->getMessage());
            return false;
        }
    }

    public function publish(SocialPost $post, $mediaFiles = null, $thumbnailFile = null): array
    {
        $appId = $this->settings['pinterest_app_id'] ?? null;
        $appSecret = $this->settings['pinterest_app_secret'] ?? null;
        $appType = $this->settings['pinterest_app_type'] ?? 'trial';

        if (!$appId || !$appSecret) {
            Log::error("Pinterest publishing failed: Missing app credentials for post ID {$post->id}");
            throw new \Exception('Pinterest app ID and secret are required');
        }

        if ($mediaFiles->isEmpty()) {
            Log::error("Pinterest publishing failed: No media files provided for post ID {$post->id}");
            throw new \Exception('Pinterest requires at least one image');
        }

        Log::info("Publishing to Pinterest for post ID {$post->id}", [
            'app_id_present' => !empty($appId),
            'app_type' => $appType,
            'media_count' => $mediaFiles->count(),
        ]);

        $apiBase = ($appType === 'production')
            ? 'https://api.pinterest.com/v5'
            : 'https://api-sandbox.pinterest.com/v5';

        try {
            $publicUrls = $this->getPublicMediaUrls($mediaFiles);
            $accessToken = $this->getPinterestAccessToken($appId, $appSecret, $apiBase);
            $boardId = $this->getOrCreatePinterestBoard($accessToken, $apiBase);

            if (!$boardId) {
                throw new \Exception('Failed to get or create Pinterest board');
            }

            $createdPins = [];
            $errors = [];

            foreach ($mediaFiles as $index => $media) {
                if (!Str::startsWith($media->mime_type, 'image')) {
                    Log::warning("Skipping unsupported media type for Pinterest (only images allowed)", [
                        'media_id' => $media->id,
                        'mime_type' => $media->mime_type,
                    ]);
                    continue;
                }

                try {
                    $pinData = [
                        'title' => Str::limit($post->caption ?: 'Untitled', 100),
                        'board_id' => $boardId,
                        'media_source' => [
                            'source_type' => 'image_url',
                            'url' => $media->getFullUrl(),
                        ]
                    ];

                    if ($post->caption) {
                        $pinData['description'] = $post->caption;
                    }

                    $response = Http::timeout(30)->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ])->post($apiBase . '/pins', $pinData);

                    if ($response->successful()) {
                        $pinId = $response->json('id');
                        $createdPins[] = $pinId;

                        Log::info('Pinterest pin created successfully', [
                            'pin_id' => $pinId,
                            'media_index' => $index + 1,
                            'response' => $response->json()
                        ]);
                    } else {
                        $errorMessage = "Failed to create pin for media " . ($index + 1) . ": " . $response->body();
                        $errors[] = $errorMessage;
                    }
                } catch (\Exception $e) {
                    $errorMessage = "Failed to create pin for media " . ($index + 1) . ": " . $e->getMessage();
                    $errors[] = $errorMessage;
                }
            }

            if (empty($createdPins)) {
                throw new \Exception("Failed to create any pins. Errors: " . implode("; ", $errors));
            }

            return [
                'success' => true,
                'platform_id' => $createdPins,
                'status' => 'published',
                'response' => [
                    'pins_created' => count($createdPins),
                    'pin_ids' => $createdPins,
                    'errors' => $errors,
                    'message' => count($createdPins) . ' pin(s) created successfully' .
                        (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
                    'environment' => $appType
                ],
                'published_at' => \Carbon\Carbon::now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Pinterest publishing error for post {$post->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getPinterestAccessToken($appId, $appSecret, $apiBase): string
    {
        Log::info('Getting Pinterest access token');

        try {
            $auth = base64_encode($appId . ':' . $appSecret);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($apiBase . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'scope' => implode(',', [
                    'boards:read',
                    'boards:write',
                    'pins:read',
                    'pins:write',
                    'user_accounts:read'
                ])
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get access token: ' . $response->body());
            }

            $tokenData = $response->json();

            if (empty($tokenData['access_token'])) {
                throw new \Exception('Access token not found in response: ' . json_encode($tokenData));
            }

            return $tokenData['access_token'];
        } catch (\Exception $e) {
            Log::error('Failed to get Pinterest access token', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to get Pinterest access token: ' . $e->getMessage());
        }
    }

    private function getOrCreatePinterestBoard(string $accessToken, string $apiBase): ?string
    {
        try {
            Log::info('Fetching Pinterest boards');

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get($apiBase . '/boards');

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['items'][0]['id'])) {
                    Log::info('Using existing board', ['board_id' => $result['items'][0]['id']]);
                    return $result['items'][0]['id'];
                }
            }

            // Create new board if none exists
            $boardData = [
                'name' => 'Default Board ' . date('Y-m-d'),
                'description' => 'Automatically created board for social media posts',
                'privacy' => 'PUBLIC'
            ];

            $newBoardResponse = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($apiBase . '/boards', $boardData);

            if (!$newBoardResponse->successful()) {
                throw new \Exception('Board creation failed: ' . $newBoardResponse->body());
            }

            $result = $newBoardResponse->json();

            if (!isset($result['id'])) {
                throw new \Exception('Board creation failed: ' . json_encode($result));
            }

            return $result['id'];
        } catch (\Exception $e) {
            Log::error('Board creation/fetch failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getPublicMediaUrls($mediaFiles): array
    {
        if ($mediaFiles->isEmpty()) {
            throw new \Exception('No media files provided');
        }

        $publicUrls = [];

        foreach ($mediaFiles as $index => $mediaFile) {
            try {
                $mediaUrl = null;

                if (method_exists($mediaFile, 'getFullUrl')) {
                    $mediaUrl = $mediaFile->getFullUrl();
                } elseif (method_exists($mediaFile, 'getUrl')) {
                    $mediaUrl = $mediaFile->getUrl();
                } elseif (property_exists($mediaFile, 'original_url')) {
                    $mediaUrl = $mediaFile->original_url;
                }

                if (empty($mediaUrl)) {
                    Log::warning('Could not get URL from media file', [
                        'media_id' => $mediaFile->id ?? 'unknown',
                        'file_name' => $mediaFile->file_name ?? 'unknown'
                    ]);
                    continue;
                }

                $publicUrls[$index] = $mediaUrl;
            } catch (\Exception $e) {
                Log::error('Error getting URL for media file', [
                    'media_id' => $mediaFile->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'index' => $index
                ]);
                continue;
            }
        }

        if (empty($publicUrls)) {
            throw new \Exception('No valid media URLs could be generated');
        }

        return $publicUrls;
    }
}

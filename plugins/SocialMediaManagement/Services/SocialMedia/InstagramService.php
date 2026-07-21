<?php
namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Plugins\SocialMediaManagement\Models\SocialPost;


class InstagramService extends BaseSocialPlatform
{
    protected function getPlatformName(): string
    {
        return 'instagram';
    }

    public function getRequiredSettings(): array
    {
        return ['instagram_business_account_id', 'instagram_access_token'];
    }

    public function verifyCredentials(): bool
    {
        try {
            $accountId = $this->settings['instagram_business_account_id'] ?? null;
            $token = $this->settings['instagram_access_token'] ?? null;

            if (!$accountId || !$token) {
                Log::warning("Missing Instagram credentials: accountId={$accountId}, token=" . ($token ? '***' : 'null'));
                return false;
            }

            $url = "https://graph.facebook.com/v21.0/{$accountId}";
            $params = [
                'fields' => 'id,username',
                'access_token' => $token
            ];

            $response = Http::timeout(10)->get($url, $params);

            if (!$response->successful()) {
                Log::error("Instagram verify failed. Status: {$response->status()}. Body: " . $response->body());
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error verifying Instagram credentials: " . $e->getMessage());
            return false;
        }
    }

    public function publish(SocialPost $post,  $mediaFiles = null, $thumbnailFile = null): array
    {
        $accountId = $this->settings['instagram_business_account_id'];
        $token = $this->settings['instagram_access_token'];

        Log::info("=== STARTING INSTAGRAM PUBLISH DEBUG ===", [
            'post_id' => $post->id,
            'account_id' => $accountId,
            'token_exists' => !empty($token),
            'token_length' => strlen($token ?? ''),
            'media_count' => $mediaFiles->count()
        ]);

        if ($mediaFiles->isEmpty()) {
            Log::error("Instagram publish failed: No media files", ['post_id' => $post->id]);
            throw new \Exception('Instagram requires at least one media file');
        }

        try {
            $mediaIds = [];

            if ($mediaFiles->count() === 1) {
                $mediaIds = $this->createSingleMediaContainer($post, $mediaFiles->first(), $accountId, $token);
            } else {
                $mediaIds = $this->createCarouselContainer($post, $mediaFiles, $accountId, $token);
            }

            $containerId = $mediaIds[0];
            $this->waitForMediaProcessing($containerId, $token);

            $publishResponse = $this->publishMedia($containerId, $accountId, $token);

            if ($publishResponse->successful()) {
                $publishData = $publishResponse->json();

                if (isset($publishData['id'])) {
                    Log::info('Instagram publish SUCCESS', [
                        'post_id' => $post->id,
                        'instagram_id' => $publishData['id'],
                        'full_response' => $publishData
                    ]);

                    return $this->createSuccessResponse($publishData['id'], $publishData);
                } else {
                    Log::error('Instagram publish response missing ID', [
                        'response' => $publishData
                    ]);
                    throw new \Exception("Instagram publish response missing ID: " . $publishResponse->body());
                }
            }

            Log::error('Instagram publish failed', [
                'status' => $publishResponse->status(),
                'error' => $publishResponse->json(),
                'body' => $publishResponse->body()
            ]);
            throw new \Exception("Instagram publish failed: " . $publishResponse->body());
        } catch (\Exception $e) {
            Log::error("=== INSTAGRAM PUBLISH EXCEPTION ===", [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            Log::info("=== INSTAGRAM PUBLISH DEBUG END ===", [
                'post_id' => $post->id
            ]);
        }
    }

    private function createSingleMediaContainer(SocialPost $post, $media, string $accountId, string $token): array
    {

        $mediaUrl = $media->getFullUrl();
        $mediaType = Str::startsWith($media->mime_type, 'video') ? 'REELS' : 'IMAGE';
        $urlField = $mediaType === 'REELS' ? 'video_url' : 'image_url';

        Log::info('Creating single Instagram media container', [
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'url_field' => $urlField,
            'mime_type' => $media->mime_type,
            'file_size' => $media->size ?? 'unknown',
            'post_id' => $post->id,
            'caption_length' => strlen($post->caption ?? '')
        ]);

        $requestData = [
            $urlField => $mediaUrl,
            'caption' => $post->caption ?: '',
            'media_type' => $mediaType,
            'access_token' => $token
        ];

        $response = Http::timeout(30)->post("https://graph.facebook.com/v21.0/{$accountId}/media", $requestData);

        Log::info('Instagram media container response', [
            'status' => $response->status(),
            'body' => $response->json(),
            'successful' => $response->successful()
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            Log::error('Failed to create Instagram media container', [
                'status' => $response->status(),
                'error' => $errorData,
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to create media container: ' . $response->body());
        }

        $responseData = $response->json();
        if (!isset($responseData['id'])) {
            throw new \Exception('No media container ID in response');
        }

        return [$responseData['id']];
    }

    private function createCarouselContainer(SocialPost $post, $mediaFiles, string $accountId, string $token): array
    {
        Log::info('Creating Instagram carousel', [
            'media_count' => $mediaFiles->count()
        ]);

        $childIds = [];

        foreach ($mediaFiles as $index => $media) {
            $mediaUrl = $media->getFullUrl();
            $mediaType = Str::startsWith($media->mime_type, 'video') ? 'REELS' : 'IMAGE';
            $urlField = $mediaType === 'REELS' ? 'video_url' : 'image_url';

            $requestData = [
                $urlField => $mediaUrl,
                'media_type' => $mediaType,
                'is_carousel_item' => true,
                'access_token' => $token
            ];

            $childResponse = Http::timeout(30)->post("https://graph.facebook.com/v21.0/{$accountId}/media", $requestData);

            if ($childResponse->successful()) {
                $childData = $childResponse->json();
                if (isset($childData['id'])) {
                    $childIds[] = $childData['id'];
                    sleep(2); // recommended delay
                } else {
                    throw new \Exception("Failed to get carousel item ID: " . $childResponse->body());
                }
            } else {
                throw new \Exception("Failed to create carousel item: " . $childResponse->body());
            }
        }

        if (empty($childIds)) {
            throw new \Exception('No carousel items were created successfully.');
        }

        $carouselData = [
            'media_type' => 'CAROUSEL',
            'caption' => $post->caption ?: '',
            'children' => implode(',', $childIds),
            'access_token' => $token
        ];

        $carouselResponse = Http::timeout(30)->post("https://graph.facebook.com/v21.0/{$accountId}/media", $carouselData);

        if (!$carouselResponse->successful()) {
            throw new \Exception('Failed to create carousel container: ' . $carouselResponse->body());
        }

        $carouselData = $carouselResponse->json();
        if (!isset($carouselData['id'])) {
            throw new \Exception('No carousel container ID in response');
        }

        return [$carouselData['id']];
    }

    private function waitForMediaProcessing(string $containerId, string $token): void
    {
        $maxTries = 10;
        $waitTime = 3; // seconds between tries

        for ($i = 0; $i < $maxTries; $i++) {
            $statusResponse = Http::get("https://graph.facebook.com/v21.0/{$containerId}?fields=status_code&access_token={$token}");
            $statusData = $statusResponse->json();

            Log::info("Media container status check", [
                'try' => $i + 1,
                'container_id' => $containerId,
                'status_code' => $statusData['status_code'] ?? null,
                'raw' => $statusData
            ]);

            if (isset($statusData['status_code']) && $statusData['status_code'] === 'FINISHED') {
                return;
            }

            sleep($waitTime);
        }

        throw new \Exception("Media container not ready after {$maxTries} attempts.");
    }

    private function publishMedia(string $containerId, string $accountId, string $token)
    {
        Log::info('Publishing Instagram media', [
            'creation_id' => $containerId,
            'account_id' => $accountId
        ]);

        $publishData = [
            'creation_id' => $containerId,
            'access_token' => $token
        ];

        return Http::timeout(30)->post("https://graph.facebook.com/v21.0/{$accountId}/media_publish", $publishData);
    }
}

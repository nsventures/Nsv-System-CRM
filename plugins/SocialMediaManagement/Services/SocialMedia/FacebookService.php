<?php

namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Services\SocialMedia\BaseSocialPlatform;

class FacebookService extends BaseSocialPlatform
{
    protected function getPlatformName(): string
    {
        return 'facebook';
    }

    public function getRequiredSettings(): array
    {
        return ['facebook_page_id', 'facebook_access_token'];
    }

    public function verifyCredentials(): bool
    {
        try {
            $pageId = $this->settings['facebook_page_id'] ?? null;
            $token = $this->settings['facebook_access_token'] ?? null;

            if (!$pageId || !$token) return false;

            $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'fields' => 'id,name',
                'access_token' => $token
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error verifying Facebook credentials: " . $e->getMessage());
            return false;
        }
    }

    public function publish(SocialPost $post, $mediaFiles = null, $thumbnailFile = null): array
    {
        $pageId = $this->settings['facebook_page_id'];
        $token = $this->settings['facebook_access_token'];
        $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/";

        try {
            if ($mediaFiles->isEmpty()) {
                // Text-only post
                $response = Http::timeout(30)->post($endpoint . 'feed', [
                    'message' => $post->caption ?: '',
                    'access_token' => $token
                ]);
            } else {
                // Handle media posts
                if ($mediaFiles->count() == 1) {
                    $response = $this->publishSingleMedia($post, $mediaFiles->first(), $endpoint, $token);
                } else {
                    $response = $this->publishMultipleMedia($post, $mediaFiles, $endpoint, $token);
                }
            }

            if ($response->successful()) {
                return $this->createSuccessResponse($response->json('id'), $response->json());
            }

            $errorData = $response->json();
            throw new \Exception("Facebook API error: " . ($errorData['error']['message'] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error("Facebook publishing error for post {$post->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function publishSingleMedia(SocialPost $post, $media, string $endpoint, string $token)
    {
        $type = Str::startsWith($media->mime_type, 'video') ? 'videos' : 'photos';

        $params = [
            'access_token' => $token,
            'published' => 'true',
        ];
        if ($type === 'photos') {
            $params['message'] = $post->caption ?: '';
        } else {
            $params['description'] = $post->caption ?: '';
        }

        return Http::timeout(60)
            ->attach('source', file_get_contents($media->getPath()), $media->file_name)
            ->post($endpoint . $type, $params);
    }

   private function publishMultipleMedia(SocialPost $post, $mediaFiles, string $endpoint, string $token)
{
    $mediaIds = [];

    foreach ($mediaFiles as $media) {
        $type = Str::startsWith($media->mime_type, 'video') ? 'videos' : 'photos';

        $params = [
            'access_token' => $token,
            'published' => 'false', //  IMPORTANT: don't publish immediately
        ];

        if ($type === 'photos') {
            $params['message'] = $post->caption ?: ''; // optional, will be overridden in feed
        } else {
            $params['description'] = $post->caption ?: '';
        }

        $uploadResponse = Http::timeout(60)
            ->attach('source', file_get_contents($media->getPath()), $media->file_name)
            ->post($endpoint . $type, $params);

        if ($uploadResponse->successful()) {
            $mediaIds[] = ['media_fbid' => $uploadResponse->json('id')];
        } else {
            Log::error("Facebook media upload failed", [
                'status' => $uploadResponse->status(),
                'body' => $uploadResponse->body(),
            ]);
        }
    }

    if (empty($mediaIds)) {
        throw new \Exception('Failed to upload any media files to Facebook');
    }

    return Http::timeout(30)->post($endpoint . 'feed', [
        'message' => $post->caption ?: '',
        'attached_media' => json_encode($mediaIds),
        'access_token' => $token
    ]);
}

}

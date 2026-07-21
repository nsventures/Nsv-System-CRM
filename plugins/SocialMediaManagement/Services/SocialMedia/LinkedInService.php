<?php
namespace Plugins\SocialMediaManagement\Services\SocialMedia;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Plugins\SocialMediaManagement\Models\SocialPost;


class LinkedInService extends BaseSocialPlatform
{
    protected function getPlatformName(): string
    {
        return 'linkedin';
    }

    public function getRequiredSettings(): array
    {
        return ['linkedin_person_id', 'linkedin_access_token'];
    }

    public function verifyCredentials(): bool
    {
        try {
            $token = $this->settings['linkedin_access_token'] ?? null;

            if (!$token) return false;

            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get('https://api.linkedin.com/v2/userinfo');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error verifying LinkedIn credentials: " . $e->getMessage());
            return false;
        }
    }

    public function publish(SocialPost $post,  $mediaFiles = null, $thumbnailFile = null): array
    {
        $personId = $this->settings['linkedin_person_id'];
        $token = $this->settings['linkedin_access_token'];
        $authorUrn = 'urn:li:person:' . $personId;

        try {
            // Clean and validate UTF-8 encoding for caption
            $cleanCaption = $this->cleanUtf8Text($post->caption ?: '');

            $firstMediaMime = $mediaFiles->first()->mime_type ?? null;
            $isImage = Str::startsWith($firstMediaMime, 'image/');
            $isVideo = Str::startsWith($firstMediaMime, 'video/');

            $shareMediaCategory = 'NONE';
            if ($isImage) {
                $shareMediaCategory = 'IMAGE';
            } elseif ($isVideo) {
                $shareMediaCategory = 'VIDEO';
            }

            $content = [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => ['text' => $cleanCaption],
                        'shareMediaCategory' => $shareMediaCategory,
                    ]
                ],
                'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
            ];

            if ($mediaFiles->isNotEmpty()) {
                $mediaAssets = $this->uploadMediaAssets($mediaFiles, $authorUrn, $token);

                if (!empty($mediaAssets)) {
                    $content['specificContent']['com.linkedin.ugc.ShareContent']['media'] = array_map(function ($assetData) {
                        $mediaItem = [
                            'status' => 'READY',
                            'media' => $assetData['asset']
                        ];

                        if ($assetData['isVideo']) {
                            $mediaItem['description'] = ['text' => 'Video Post'];
                        } else {
                            $mediaItem['title'] = ['text' => 'Post Image'];
                        }

                        return $mediaItem;
                    }, $mediaAssets);
                }
            }

            // Final JSON validation before sending main request
            $jsonTest = json_encode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON encoding error in main content: " . json_last_error_msg());
            }

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->post('https://api.linkedin.com/v2/ugcPosts', $content);

            if ($response->successful()) {
                return $this->createSuccessResponse($response->json('id'), $response->json());
            }

            throw new \Exception("LinkedIn API error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("LinkedIn publishing error for post {$post->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function uploadMediaAssets($mediaFiles, string $authorUrn, string $token): array
    {
        $mediaAssets = [];

        foreach ($mediaFiles as $media) {
            // Validate file path encoding
            $filePath = $this->validateFilePath($media->getPath());
            if (!$filePath) {
                Log::warning("Skipping media file due to encoding issues: " . $media->getPath());
                continue;
            }

            $mimeType = $media->mime_type;
            $isCurrentVideo = Str::startsWith($mimeType, 'video/');

            $recipe = $isCurrentVideo
                ? 'urn:li:digitalmediaRecipe:feedshare-video'
                : 'urn:li:digitalmediaRecipe:feedshare-image';

            $uploadRequest = [
                'registerUploadRequest' => [
                    'recipes' => [$recipe],
                    'owner' => $authorUrn,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent'
                        ]
                    ]
                ]
            ];

            // Validate JSON encoding before sending
            $jsonTest = json_encode($uploadRequest);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON encoding error in upload request: " . json_last_error_msg());
                continue;
            }

            $initResponse = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post('https://api.linkedin.com/v2/assets?action=registerUpload', $uploadRequest);

            if (!$initResponse->successful()) {
                Log::warning("Failed to initialize upload: " . $initResponse->body());
                continue;
            }

            $uploadData = $initResponse->json();
            $uploadUrl = $uploadData['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
            $asset = $uploadData['value']['asset'];

            // Upload media
            $fileContent = file_get_contents($filePath);
            $detectedMimeType = mime_content_type($filePath) ?: 'application/octet-stream';

            $uploadResponse = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => $detectedMimeType,
                ])
                ->withBody($fileContent, $detectedMimeType)
                ->put($uploadUrl);

            if ($uploadResponse->successful()) {
                $mediaAssets[] = [
                    'asset' => $asset,
                    'isVideo' => $isCurrentVideo
                ];
            } else {
                Log::warning("Failed to upload media: " . $uploadResponse->body());
            }
        }

        return $mediaAssets;
    }

    private function cleanUtf8Text($text): string
    {
        if (empty($text)) {
            return '';
        }

        $cleanText = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanText);
        $cleanText = trim($cleanText);

        return $cleanText;
    }

    private function validateFilePath($path)
    {
        if (!file_exists($path)) {
            Log::error("File does not exist: " . $path);
            return false;
        }

        if (!is_readable($path)) {
            Log::error("File is not readable: " . $path);
            return false;
        }

        $fileSize = filesize($path);
        $mimeType = mime_content_type($path);

        if (Str::startsWith($mimeType, 'video/')) {
            if ($fileSize > 5 * 1024 * 1024 * 1024) {
                Log::error("Video file too large: " . $path . " (" . $fileSize . " bytes)");
                return false;
            }
        } else {
            if ($fileSize > 100 * 1024 * 1024) {
                Log::error("Image file too large: " . $path . " (" . $fileSize . " bytes)");
                return false;
            }

            $imageInfo = getimagesize($path);
            if ($imageInfo === false) {
                Log::error("Invalid image file: " . $path);
                return false;
            }
        }

        return $path;
    }
}

<?php
namespace Plugins\SocialMediaManagement\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Services\SocialMediaService;

class PublishScheduledPosts extends Command
{
    protected $signature = 'social:publish-scheduled';
    protected $description = 'Publish scheduled social media posts';

    public function handle()
    {
        Log::info('Starting social:publish-scheduled command');
        $publisher = app('social.scheduler');

        if (!$publisher) {
            Log::error('SocialMediaService not resolved.');
            $this->error('SocialMediaService not resolved.');
            return self::FAILURE;
        }

        $nowUtc = Carbon::now('UTC');
        $this->info("Now (UTC): " . $nowUtc->toDateTimeString());

        try {
            $scheduledPosts = SocialPost::where('status', 'scheduled')
                ->where('scheduled_at', '<=', $nowUtc)
                ->get();

            if ($scheduledPosts->isEmpty()) {
                Log::info('No scheduled posts found to publish.', ['now_utc' => $nowUtc->toDateTimeString()]);
                $this->info('No scheduled posts found to publish.');
                return self::SUCCESS;
            }

            $this->info("Found {$scheduledPosts->count()} posts to publish");

            foreach ($scheduledPosts as $post) {
                try {
                    $this->info("Publishing post ID: {$post->id}");
                    $responses = $publisher->publishPost($post);
                    $post->update([
                        'status' => 'published',
                        'response_logs' => $responses,
                        'published_at' => $nowUtc->toISOString(),
                    ]);
                    $this->info("Published post ID: {$post->id}");
                    if (config('app.debug')) {
                        $this->info("Responses: " . json_encode($responses));
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to publish post ID: {$post->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $post->update([
                        'status' => 'failed',
                        'response_logs' => [
                            'error' => $e->getMessage(),
                            'failed_at' => $nowUtc->toISOString(),
                        ],
                    ]);
                    $this->error("Failed to publish post ID: {$post->id} - " . $e->getMessage());
                }
            }

            $this->info("Processed {$scheduledPosts->count()} scheduled posts");
            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error in social:publish-scheduled command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Command failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
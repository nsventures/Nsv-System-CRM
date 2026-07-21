<?php

namespace Plugins\SocialMediaManagement\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Commands\PublishScheduledPosts;

class SocialMediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'social-media-scheduler');
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'social-media-scheduler');

        // Define publishable assets
        $this->publishes([
            __DIR__ . '/../public/js' => public_path('assets/js/social'),
            __DIR__ . '/../public/css' => public_path('assets/css/social'),
            __DIR__ . '/../public/img'  => public_path('assets/img/social'),
        ], ['social-assets', 'public']);

        // Auto-publish assets
        $this->autoPublishAssets();

        // Log plugin version
        if (file_exists(__DIR__ . '/../plugin.json')) {
            $pluginJson = json_decode(file_get_contents(__DIR__ . '/../plugin.json'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info("Social Media Scheduler Plugin Loaded - Version: " . ($pluginJson['version'] ?? 'unknown'));
            } else {
                Log::warning("Failed to parse plugin.json: " . json_last_error_msg());
            }
        }

        // Schedule tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(PublishScheduledPosts::class)
                ->everyMinute()
                ->withoutOverlapping()
                ->onSuccess(function () {
                    Log::info('social:publish-scheduled command completed successfully.');
                })
                ->onFailure(function () {
                    Log::error('social:publish-scheduled command failed.');
                });
        });

        // Add scheduledPosts relationship to User
        User::resolveRelationUsing('scheduledPosts', function ($userModel) {
            return $userModel->hasMany(SocialPost::class, 'user_id');
        });

        // Register services
        $this->registerServices();
    }

    public function register(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishScheduledPosts::class,
            ]);
        }

        // Social settings helper
        $getSocialSettings = function () {
            $settings = \App\Models\Setting::where('variable', 'social_settings')->first();
            return $settings ? json_decode($settings->value, true) : [];
        };

        // Register service bindings
        $this->app->singleton('social.facebook', function ($app) use ($getSocialSettings) {
            return new \Plugins\SocialMediaManagement\Services\SocialMedia\FacebookService($getSocialSettings());
        });
        $this->app->singleton('social.pinterest', function ($app) use ($getSocialSettings) {
            return new \Plugins\SocialMediaManagement\Services\SocialMedia\PinterestService($getSocialSettings());
        });
        $this->app->singleton('social.instagram', function ($app) use ($getSocialSettings) {
            return new \Plugins\SocialMediaManagement\Services\SocialMedia\InstagramService($getSocialSettings());
        });
        $this->app->singleton('social.linkedin', function ($app) use ($getSocialSettings) {
            return new \Plugins\SocialMediaManagement\Services\SocialMedia\LinkedInService($getSocialSettings());
        });
        $this->app->singleton('social.youtube', function ($app) use ($getSocialSettings) {
            return new \Plugins\SocialMediaManagement\Services\SocialMedia\YouTubeService($getSocialSettings());
        });
    }

    private function registerServices(): void
    {
        $this->app->singleton('social.scheduler', function ($app) {
           return new \Plugins\SocialMediaManagement\Services\SocialMediaService();
        });
    }

    private function autoPublishAssets(): void
    {
        $sourcePathJs = __DIR__ . '/../public/js';
        $destinationPathJs = public_path('assets/js/social');
        if (File::exists($sourcePathJs) && (!File::exists($destinationPathJs) || $this->assetsNeedUpdate($sourcePathJs, $destinationPathJs))) {
            File::ensureDirectoryExists($destinationPathJs);
            File::copyDirectory($sourcePathJs, $destinationPathJs);
            Log::info("Social Media Scheduler Plugin: JS assets auto-published to {$destinationPathJs}");
        }

        $sourcePathCss = __DIR__ . '/../public/css';
        $destinationPathCss = public_path('assets/css/social');
        if (File::exists($sourcePathCss) && (!File::exists($destinationPathCss) || $this->assetsNeedUpdate($sourcePathCss, $destinationPathCss))) {
            File::ensureDirectoryExists($destinationPathCss);
            File::copyDirectory($sourcePathCss, $destinationPathCss);
            Log::info("Social Media Scheduler Plugin: CSS assets auto-published to {$destinationPathCss}");
        }

        $sourcePathImg = __DIR__ . '/../public/img';
        $destinationPathImg = public_path('assets/img/social');
        if (File::exists($sourcePathImg) && (!File::exists($destinationPathImg) || $this->assetsNeedUpdate($sourcePathImg, $destinationPathImg))) {
            File::ensureDirectoryExists($destinationPathImg);
            File::copyDirectory($sourcePathImg, $destinationPathImg);
            Log::info("Social Media Scheduler Plugin: IMG assets auto-published to {$destinationPathImg}");
        }

        
        // CONFIG
        $sourceConfig = __DIR__ . '/../config/social.php';
        $destinationConfig = config_path('social.php');
        if (File::exists($sourceConfig) && !File::exists($destinationConfig)) {
            File::copy($sourceConfig, $destinationConfig);
            Log::info("Social Media Scheduler Plugin: Config file auto-published to {$destinationConfig}");
        }
    }

    private function assetsNeedUpdate(string $sourcePath, string $destinationPath): bool
    {
        if (!File::exists($destinationPath)) {
            return true;
        }
        $sourceTime = File::lastModified($sourcePath);
        $destTime = File::lastModified($destinationPath);
        return $sourceTime > $destTime;
    }
}

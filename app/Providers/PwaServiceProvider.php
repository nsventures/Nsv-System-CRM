<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PwaServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        // Share PWA config with all views
        // View::composer('*', function ($view) {
        //     if (app()->bound(PwaManifestService::class)) {
        //         $pwaService = app(PwaManifestService::class);
        //         $config = $pwaService->getConfigForMeta();
        //         $view->with('config', $config);
        //     }
        // });
    }
}

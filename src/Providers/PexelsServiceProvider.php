<?php

namespace hexa_package_pexels\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_pexels\Services\PexelsService;

class PexelsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        ->mergeConfigFrom(__DIR__ . '/../../config/pexels.php', 'pexels');
        $this->app->singleton(PexelsService::class);
    }

    public function boot(): void {}
}

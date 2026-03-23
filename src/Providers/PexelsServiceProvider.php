<?php

namespace hexa_package_pexels\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_pexels\Services\PexelsService;
use hexa_core\Services\PackageRegistryService;

/**
 * PexelsServiceProvider — registers Pexels package routes, views, config,
 * and sidebar menu for the Hexa Core framework.
 */
class PexelsServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/pexels.php', 'pexels');
        $this->app->singleton(PexelsService::class);
    }

    /**
     * Bootstrap package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/pexels.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'pexels');

        // Sidebar links — registered via PackageRegistryService with auto permission checks
        if (!config('hexa.app_controls_sidebar', false)) {
            $registry = app(PackageRegistryService::class);
            $registry->registerSidebarLink('pexels.index', 'Pexels', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'Sandbox', 'pexels', 83);
        }
    }
}

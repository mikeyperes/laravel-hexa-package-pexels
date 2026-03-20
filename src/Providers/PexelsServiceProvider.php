<?php

namespace hexa_package_pexels\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_pexels\Services\PexelsService;

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

        // Sidebar menu injection (skipped when app controls sidebar)
        $this->registerSidebarMenu();
    }

    /**
     * Register sidebar menu items via view composer.
     *
     * @return void
     */
    private function registerSidebarMenu(): void
    {
        view()->composer('layouts.app', function ($view) {
            if (config('hexa.app_controls_sidebar', false)) return;
            $factory = app('view');
            $factory->startPush('sidebar-sandbox');
            echo $factory->make('pexels::partials.sidebar-menu')->render();
            $factory->stopPush();
        });
    }
}

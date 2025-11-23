<?php

namespace ToKLearningSpace\Providers;

use Illuminate\Support\ServiceProvider;

class ToKLearningSpaceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/learning_space.php');

        // Load package views with "tok_ls::" namespace
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tok_ls');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // ✅ Publish public assets (CSS, etc.) to /public/tok-ls
        $this->publishes([
            __DIR__ . '/../../public' => public_path('tok-ls'),
        ], 'tok-learning-space-assets');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
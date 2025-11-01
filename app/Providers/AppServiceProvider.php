<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

use App\Models\Thread;
use App\Policies\ThreadPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Force HTTPS when running in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ✅ Register policies here (no separate AuthServiceProvider needed)
        Gate::policy(Thread::class, ThreadPolicy::class);
    }
}
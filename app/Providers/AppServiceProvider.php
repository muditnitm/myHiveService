<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Classes\Module;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('module', function ($app) {
            return new Module();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Service::observe(\App\Observers\BookingCacheObserver::class);
        \App\Models\Staff::observe(\App\Observers\BookingCacheObserver::class);
        \App\Models\Location::observe(\App\Observers\BookingCacheObserver::class);
    }
}

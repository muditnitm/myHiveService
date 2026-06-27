<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NewRelicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (extension_loaded('newrelic')) {
            newrelic_set_appname('myHiveService');
        }
    }
}

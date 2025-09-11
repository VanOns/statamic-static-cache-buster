<?php

namespace VanOns\StatamicStaticCacheBuster;

use VanOns\StatamicStaticCacheBuster\StaticCaching\Buster;
use Illuminate\Support\ServiceProvider;
use Statamic\StaticCaching\Cacher;

class StatamicStaticCacheBusterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            paths: [
                __DIR__ . '/../config/statamic/static-cache-buster.php' => config_path('statamic/static-cache-buster.php'),
            ],
            groups: 'statamic-static-cache-buster-config'
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/statamic/static-cache-buster.php',
            'statamic/static-cache-buster'
        );

        $this->app->bind(Buster::class, function ($app) {
            return new Buster(
                $app[Cacher::class],
                $app['config']['statamic.static_caching.invalidation.rules']
            );
        });
    }
}

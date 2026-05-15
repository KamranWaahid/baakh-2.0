<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestFactoryInterface;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\StreamFactoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientInterface::class, Client::class);
        $this->app->bind(RequestFactoryInterface::class, HttpFactory::class);
        $this->app->bind(StreamFactoryInterface::class, HttpFactory::class);

        // Search is handled by Scout 'database' driver on shared hosting
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $usesConfiguredHttpsOrigin = str_starts_with($appUrl, 'https://');

        if ($appUrl && (getenv('VERCEL') || $usesConfiguredHttpsOrigin)) {
            URL::forceRootUrl($appUrl);
        }

        if (getenv('VERCEL') || $usesConfiguredHttpsOrigin) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrap();
        \Illuminate\Database\Schema\Builder::defaultStringLength(191);

        // Register Observers for Static Caching and Notifications
        \App\Models\Poetry::observe([\App\Observers\PoetryObserver::class, \App\Observers\ContentNotificationObserver::class]);
        \App\Models\Poets::observe([\App\Observers\PoetObserver::class, \App\Observers\ContentNotificationObserver::class]);
        \App\Models\Tags::observe([\App\Observers\TagObserver::class, \App\Observers\ContentNotificationObserver::class]);
        \App\Models\Categories::observe([\App\Observers\CategoryObserver::class, \App\Observers\ContentNotificationObserver::class]);
        \App\Models\TopicCategory::observe([\App\Observers\TopicCategoryObserver::class, \App\Observers\ContentNotificationObserver::class]);

        // Register the 'localized' macro when the URL service is resolved.
        // This prevents early resolution errors (TypeError regarding $request).
        $this->app->resolving('url', function ($url) {
            if (!method_exists($url, 'localized')) {
                $url->macro('localized', function ($path) {
                    $l = app()->getLocale();
                    if ($l == 'sd') {
                        return $path;
                    } else {
                        return $path . '?lang=' . $l;
                    }
                });
            }
        });
    }
}

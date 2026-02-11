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
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\NodePool\SimpleNodePool;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client as ElasticsearchClient;

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
        $this->app->bind(NodePoolInterface::class, SimpleNodePool::class);

        // Search is handled by Scout 'database' driver on shared hosting
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

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

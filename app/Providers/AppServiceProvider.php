<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);
         
        URL::macro('localized', function ($url) {
            $l = app()->getLocale();
            if($l == 'sd')
            {
                return $url;
            }else{
                return $url . '?lang=' . app()->getLocale();
            }
            
        });
        
                
        
    }
}

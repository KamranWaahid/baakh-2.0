<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Long-running admin batch jobs (dictionary Hesudhar, etc.)
        RateLimiter::for('admin-bulk', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(600)->by('admin-bulk:'.$request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin SPA is served from routes/web.php without server-side auth.
            // React ProtectedRoute checks /api/auth/me. Do NOT wrap /admin in auth here
            // or guests (and post-deploy expired sessions) get redirected to public "/".

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

        });
    }
}

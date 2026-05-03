<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/

// Include Auth Routes BEFORE SPA routes
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/
// Allow SPA to load (Frontend handles auth via API)
Route::get('admin/{any?}', function () {
    return view('admin.app');
})->where('any', '.*')->name('admin.spa');

// Admin SPA with locale prefix (e.g. /sd/admin, /en/admin)
Route::get('{lang}/admin/{any?}', function ($lang, $any = null) {
    if (in_array($lang, ['en', 'sd'])) {
        app()->setLocale($lang);
        return view('admin.app');
    }
    abort(404);
})->where('lang', 'en|sd')->where('any', '.*')->name('admin.spa.locale');

Route::get('og-image/poetry/{slug}', [\App\Http\Controllers\OgImageController::class, 'generatePoetryImage'])->name('og.poetry');

/*
|--------------------------------------------------------------------------
| Sitemap Routes (must be before SPA catch-all)
|--------------------------------------------------------------------------
*/
Route::prefix('sitemap')->group(function () {
    Route::get('/pages.xml', [\App\Http\Controllers\SitemapController::class, 'pages'])->name('sitemap.pages');
    Route::get('/poets.xml', [\App\Http\Controllers\SitemapController::class, 'poets'])->name('sitemap.poets');
    Route::get('/poets-{year}-{month}.xml', [\App\Http\Controllers\SitemapController::class, 'poetsByMonth'])->name('sitemap.poets.month');
    Route::get('/poetry.xml', [\App\Http\Controllers\SitemapController::class, 'poetry'])->name('sitemap.poetry');
    Route::get('/poetry-{year}-{month}.xml', [\App\Http\Controllers\SitemapController::class, 'poetryByMonth'])->name('sitemap.poetry.month');
    Route::get('/couplets.xml', [\App\Http\Controllers\SitemapController::class, 'couplets'])->name('sitemap.couplets');
    Route::get('/couplets-{year}-{month}.xml', [\App\Http\Controllers\SitemapController::class, 'coupletsByMonth'])->name('sitemap.couplets.month');
    Route::get('/categories.xml', [\App\Http\Controllers\SitemapController::class, 'categories'])->name('sitemap.categories');
    Route::get('/tags.xml', [\App\Http\Controllers\SitemapController::class, 'tags'])->name('sitemap.tags');
    Route::get('/tags-{year}-{month}.xml', [\App\Http\Controllers\SitemapController::class, 'tagsByMonth'])->name('sitemap.tags.month');
    Route::get('/topics.xml', [\App\Http\Controllers\SitemapController::class, 'topics'])->name('sitemap.topics');
});
Route::get('sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.index');

Route::get('/test-mail', function () {
    try {
        $to = request('to', 'admin@baakh.com');
        echo "Attempting to send a test email to: <b>$to</b>...<br>";

        // Debugging .env issues
        $pwd = config('mail.mailers.smtp.password');
        if (str_ends_with($pwd, ';')) {
            echo "<span style='color:orange'>WARNING: Your MAIL_PASSWORD in .env ends with a semicolon (;). Is this intended?</span><br>";
        }

        \Illuminate\Support\Facades\Mail::raw('This is a test email to verify SMTP settings.', function ($message) use ($to) {
            $message->to($to)
                ->subject('SMTP Verification Test');
        });

        echo "<span style='color:green'>Success! Email sent.</span><br>";
        echo "Check your inbox (and spam folder).";
    } catch (\Exception $e) {
        echo "<span style='color:red'>Failed to send email.</span><br>";
        echo "<b>Error:</b> " . $e->getMessage() . "<br><br>";
        echo "<b>Current Configuration:</b><br>";
        echo "Mailer: " . config('mail.default') . "<br>";
        echo "Host: " . config('mail.mailers.smtp.host') . "<br>";
        echo "Port: " . config('mail.mailers.smtp.port') . "<br>";
        echo "Encryption: " . config('mail.mailers.smtp.encryption') . "<br>";
        echo "Username: " . config('mail.mailers.smtp.username') . "<br>";
        echo "From: " . config('mail.from.address') . "<br>";

        if (config('mail.default') === 'smtp') {
            echo "<br><b>Recommendation:</b> Try changing <code>MAIL_MAILER=sendmail</code> in your .env file.";
        }
    }
});

Route::get('/debug-logs', function () {
    if (file_exists(storage_path('logs/laravel.log'))) {
        return response(file_get_contents(storage_path('logs/laravel.log')), 200)
            ->header('Content-Type', 'text/plain');
    }
    return 'Log file not found.';
});

/*
|--------------------------------------------------------------------------
| Deploy diagnostics (do not expose without DEPLOY_HEALTH_SECRET)
|--------------------------------------------------------------------------
*/
Route::get('/_health/ping', function () {
    return response()->json([
        'ok' => true,
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
    ]);
});

Route::get('/_health/database', function () {
    $secret = trim((string) env('DEPLOY_HEALTH_SECRET'));
    abort_unless($secret !== '' && hash_equals($secret, (string) request()->query('token', '')), 404);

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();

        return response()->json([
            'database' => 'connected',
            'connection' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'database' => 'failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| API fallback routes
|--------------------------------------------------------------------------
| Some serverless/proxy combinations can bypass RouteServiceProvider's api group
| and fall through to the SPA catch-all. Keep critical public JSON endpoints
| reachable by defining them here under explicit /api/* paths.
*/
Route::prefix('api')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'ok' => true,
            'service' => 'api-fallback',
            'laravel' => app()->version(),
            'environment' => app()->environment(),
        ]);
    });

    Route::prefix('v1')->group(function () {
        Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
        Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
        Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);
        Route::get('explore-topics', [App\Http\Controllers\Api\ExploreTopicController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Serverless path-normalized API fallback routes
|--------------------------------------------------------------------------
| Vercel PHP routing may forward /api/* into Laravel as stripped paths
| (e.g. /api/v1/feed -> /v1/feed). Mirror critical endpoints without /api
| so frontend always receives JSON instead of SPA HTML.
*/
Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'api-fallback-stripped',
        'laravel' => app()->version(),
        'environment' => app()->environment(),
    ]);
});

Route::prefix('v1')->group(function () {
    Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
    Route::get('poets', [App\Http\Controllers\Api\PoetController::class, 'index']);
    Route::get('poet-tags', [App\Http\Controllers\Api\PoetController::class, 'tags']);
    Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
    Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);
    Route::get('explore-topics', [App\Http\Controllers\Api\ExploreTopicController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Generic stripped-path forwarder
|--------------------------------------------------------------------------
| If serverless routing strips the /api prefix, forward known API families
| back to /api/* so the same controllers/middleware are used application-wide.
*/
Route::any('{apiPath}', function (Request $request, string $apiPath) {
    $forwardPath = '/api/' . ltrim($apiPath, '/');
    $query = $request->getQueryString();
    $forwardUrl = $query ? "{$forwardPath}?{$query}" : $forwardPath;

    $subRequest = Request::create(
        $forwardUrl,
        $request->getMethod(),
        $request->request->all(),
        $request->cookies->all(),
        $request->files->all(),
        $request->server->all(),
        $request->getContent()
    );

    $subRequest->headers->replace($request->headers->all());

    try {
        return app('router')->dispatch($subRequest);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'API forwarder failed',
            'path' => $apiPath,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ], 500);
    }
})->where('apiPath', '^(v1|auth|admin)(/.*)?$');

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api|robots\.txt|_health).*$')->name('web.spa');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

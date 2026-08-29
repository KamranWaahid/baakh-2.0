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
| Admin SPA + stripped-path API (Vercel)
|--------------------------------------------------------------------------
| Serverless routing often forwards /api/admin/* as /admin/*. The catch-all
| admin route would otherwise return HTML for XHRs. When the request looks
| like an API call and has a sub-path, dispatch the same /api/admin/* route.
*/
$forwardStrippedAdminApi = static function (Request $request, ?string $any): ?\Symfony\Component\HttpFoundation\Response {
    $suffix = trim((string) ($any ?? ''), '/');
    if ($suffix === '') {
        return null;
    }
    $wantsApi = $request->expectsJson()
        || $request->ajax()
        || $request->bearerToken() !== null
        || strcasecmp((string) $request->header('X-Requested-With', ''), 'XMLHttpRequest') === 0;
    if (!$wantsApi) {
        return null;
    }

    $forwardPath = '/api/admin/' . $suffix;
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
            'message' => 'Admin API forward failed',
            'path' => $suffix,
            'error' => $e->getMessage(),
        ], 500);
    }
};

// Allow SPA to load (Frontend handles auth via API)
Route::any('admin/{any?}', function (Request $request, $any = null) use ($forwardStrippedAdminApi) {
    $forwarded = $forwardStrippedAdminApi($request, $any);
    if ($forwarded !== null) {
        return $forwarded;
    }

    if (!$request->isMethod('get')) {
        abort(404);
    }

    return view('admin.app');
})->where('any', '.*')->name('admin.spa');

// Admin SPA with locale prefix (e.g. /sd/admin, /en/admin)
Route::any('{lang}/admin/{any?}', function (Request $request, string $lang, $any = null) use ($forwardStrippedAdminApi) {
    if (!in_array($lang, ['en', 'sd'], true)) {
        abort(404);
    }
    app()->setLocale($lang);

    $forwarded = $forwardStrippedAdminApi($request, $any);
    if ($forwarded !== null) {
        return $forwarded;
    }

    if (!$request->isMethod('get')) {
        abort(404);
    }

    return view('admin.app');
})->where('lang', 'en|sd')->where('any', '.*')->name('admin.spa.locale');

Route::get('og-image/poetry/{slug}', [\App\Http\Controllers\OgImageController::class, 'generatePoetryImage'])
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('og.poetry');

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

    Route::prefix('auth')->group(function () {
        Route::get('google/redirect', [\App\Http\Controllers\LoginWithGoogleController::class, 'loginWithGoogle']);
        Route::get('google/mobile', [\App\Http\Controllers\Api\Auth\MobileGoogleController::class, 'help']);
        Route::post('google/mobile', [\App\Http\Controllers\Api\Auth\MobileGoogleController::class, 'login']);
    });

    Route::prefix('v1')->group(function () {
        Route::get('auth/ui', [\App\Http\Controllers\Api\Auth\MobileGoogleController::class, 'ui']);
        Route::get('auth/google/redirect', [\App\Http\Controllers\LoginWithGoogleController::class, 'loginWithGoogle']);
        Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
        Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
        Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);
        Route::get('explore-topics', [App\Http\Controllers\Api\ExploreTopicController::class, 'index']);
        Route::get('lyrics', [App\Http\Controllers\Api\PublicLyricsController::class, 'index']);
        Route::get('lyrics/{slug}', [App\Http\Controllers\Api\PublicLyricsController::class, 'show']);
        Route::get('lyrics-genres', [App\Http\Controllers\Api\PublicLyricsGenreController::class, 'index']);
        Route::get('lyrics-genres/{slug}', [App\Http\Controllers\Api\PublicLyricsGenreController::class, 'show']);
        Route::get('singers', [App\Http\Controllers\Api\PublicSingerController::class, 'index']);
        Route::get('singers/{slug}/lyrics', [App\Http\Controllers\Api\PublicSingerController::class, 'lyrics']);
        Route::get('singers/{slug}', [App\Http\Controllers\Api\PublicSingerController::class, 'show']);
        Route::get('bands', [App\Http\Controllers\Api\PublicBandController::class, 'index']);
        Route::get('bands/{slug}/lyrics', [App\Http\Controllers\Api\PublicBandController::class, 'lyrics']);
        Route::get('bands/{slug}', [App\Http\Controllers\Api\PublicBandController::class, 'show']);
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
    Route::get('auth/ui', [App\Http\Controllers\Api\Auth\MobileGoogleController::class, 'ui']);
    Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
    Route::get('poets', [App\Http\Controllers\Api\PoetController::class, 'index']);
    Route::get('poet-tags', [App\Http\Controllers\Api\PoetController::class, 'tags']);
    Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
    Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);
    Route::get('explore-topics', [App\Http\Controllers\Api\ExploreTopicController::class, 'index']);
    Route::get('lyrics', [App\Http\Controllers\Api\PublicLyricsController::class, 'index']);
    Route::get('lyrics/{slug}', [App\Http\Controllers\Api\PublicLyricsController::class, 'show']);
    Route::get('lyrics-genres', [App\Http\Controllers\Api\PublicLyricsGenreController::class, 'index']);
    Route::get('lyrics-genres/{slug}', [App\Http\Controllers\Api\PublicLyricsGenreController::class, 'show']);
    Route::get('singers', [App\Http\Controllers\Api\PublicSingerController::class, 'index']);
    Route::get('singers/{slug}/lyrics', [App\Http\Controllers\Api\PublicSingerController::class, 'lyrics']);
    Route::get('singers/{slug}', [App\Http\Controllers\Api\PublicSingerController::class, 'show']);
    Route::get('bands', [App\Http\Controllers\Api\PublicBandController::class, 'index']);
    Route::get('bands/{slug}/lyrics', [App\Http\Controllers\Api\PublicBandController::class, 'lyrics']);
    Route::get('bands/{slug}', [App\Http\Controllers\Api\PublicBandController::class, 'show']);
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

/*
|--------------------------------------------------------------------------
| Lyrics public site (lyrics.baakh.com)
|--------------------------------------------------------------------------
| Separate SPA from the poetry archive. Same Laravel app / API, different
| Vite entry + blade. Local preview: /lyrics-site/*
*/
$lyricsSpa = static function (Request $request, $any = null) {
    return app(\App\Http\Controllers\LyricsSpaController::class)->index($request, $any);
};

foreach (config('app.lyrics_hosts', ['lyrics.baakh.com']) as $lyricsHost) {
    Route::domain($lyricsHost)->group(function () use ($lyricsSpa, $lyricsHost) {
        Route::get('{any?}', $lyricsSpa)
            ->where('any', '^(?!api|build|robots\.txt|_health|admin).*$')
            ->name('lyrics.spa.' . str_replace('.', '_', $lyricsHost));
    });
}

Route::get('lyrics-site/{any?}', $lyricsSpa)
    ->where('any', '.*')
    ->name('lyrics.spa.local');

// Main archive: send /lyrics to the separate lyrics site (not the archive SPA)
Route::get('{lang}/lyrics', function (string $lang) {
    if (!in_array($lang, ['en', 'sd'], true)) {
        abort(404);
    }
    $base = rtrim((string) config('app.lyrics_url', 'https://lyrics.baakh.com'), '/');
    return redirect()->away("{$base}/{$lang}", 301);
})->where('lang', 'en|sd');

/*
|--------------------------------------------------------------------------
| Archive SPA — canonical public URLs (not the v1 API path shapes)
|--------------------------------------------------------------------------
| Public pages: /{lang}/poet/{slug} and /{lang}/poet/{slug}/{category}/{poem}
| API lookup:   /api/v1/poets/{slug} and /api/v1/poetry/{slug}
| Legacy HTML   /poets/{slug}, /poetry/{slug}, /tags/{slug} 301 via NormalizeSeoUrls.
| Lyrics/singers live on lyrics.baakh.com, not the archive SPA.
*/
Route::prefix('{lang}')
    ->where(['lang' => 'en|sd'])
    ->group(function () {
        Route::get('poet/{slug}/{category}/{poemSlug}', [\App\Http\Controllers\SpaController::class, 'index'])
            ->name('spa.poetry');
        Route::get('poet/{slug}', [\App\Http\Controllers\SpaController::class, 'index'])
            ->name('spa.poet');
        Route::get('tag/{slug}', [\App\Http\Controllers\SpaController::class, 'index'])
            ->name('spa.tag');
        Route::get('topic/{slug}', [\App\Http\Controllers\SpaController::class, 'index'])
            ->name('spa.topic');
    });

Route::get('llms.txt', [\App\Http\Controllers\WellKnownController::class, 'llmsTxt'])->name('llms.txt');
Route::get('llms.md', [\App\Http\Controllers\WellKnownController::class, 'llmsTxt']);
Route::get('agents.md', [\App\Http\Controllers\WellKnownController::class, 'agentsMarkdown']);
Route::get('index.md', [\App\Http\Controllers\WellKnownController::class, 'indexMarkdown']);
Route::get('auth.md', [\App\Http\Controllers\WellKnownController::class, 'authMarkdown']);
Route::get('developers.md', [\App\Http\Controllers\WellKnownController::class, 'developersMarkdown']);
Route::get('api.md', [\App\Http\Controllers\WellKnownController::class, 'apiMarkdown']);
Route::get('skill.md', [\App\Http\Controllers\WellKnownController::class, 'skillMarkdown']);
Route::get('agent.md', [\App\Http\Controllers\WellKnownController::class, 'agentMarkdown']);
Route::get('developer.md', [\App\Http\Controllers\WellKnownController::class, 'developerMarkdown']);
Route::get('.well-known/llms.txt', [\App\Http\Controllers\WellKnownController::class, 'llmsTxt']);
Route::get('.well-known/ai-catalog.json', [\App\Http\Controllers\WellKnownController::class, 'aiCatalog']);
Route::get('.well-known/api-catalog', [\App\Http\Controllers\WellKnownController::class, 'apiCatalog']);

Route::match(['GET', 'HEAD'], '.well-known/agent-skills/{path?}', [\App\Http\Controllers\AgentDiscoveryController::class, 'show'])
    ->where('path', '.*')
    ->name('agent-skills');

Route::get('{lang}/index.md', [\App\Http\Controllers\WellKnownController::class, 'indexMarkdown'])
    ->where('lang', 'en|sd');
Route::get('{lang}/{section}/llms.txt', [\App\Http\Controllers\WellKnownController::class, 'sectionLlms'])
    ->where(['lang' => 'en|sd', 'section' => 'poets|poetry|couplets|genre|period|explore|prosody|about|contact|help|privacy|terms']);

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api|build|robots\.txt|llms\.txt|llms\.md|agents\.md|index\.md|auth\.md|developers\.md|api\.md|skill\.md|agent\.md|developer\.md|_health|lyrics-site|\.well-known).*$')->name('web.spa');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

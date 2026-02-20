<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api).*$')->name('web.spa');


Route::get('/run-migrations', function () {
    try {
        echo "Running migrations...<br>";
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo \Illuminate\Support\Facades\Artisan::output();
        echo "<br>Done!";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');


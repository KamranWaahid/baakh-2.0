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

Route::get('/test-mail', function () {
    try {
        $to = request('to', 'admin@baakh.com');
        echo "Attempting to send a test email to: <b>$to</b>...<br>";

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
    }
});

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api).*$')->name('web.spa');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

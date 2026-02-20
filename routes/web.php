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

Route::get('/debug-admin', function () {
    $email = 'admin@baakh.com';
    $targetHash = hash('sha256', strtolower($email));

    $userByHash = \App\Models\User::where('email_hash', $targetHash)->first();

    echo "Target Email: $email<br>";
    echo "Target Hash: $targetHash<br>";
    echo "User found by hash: " . ($userByHash ? "YES (ID: {$userByHash->id})" : "NO") . "<br>";

    if ($userByHash) {
        echo "User Name: {$userByHash->name}<br>";
        echo "User Role (field): {$userByHash->role}<br>";
        echo "User Roles (Spatie): " . implode(', ', $userByHash->getRoleNames()->toArray()) . "<br>";
        echo "User Status: {$userByHash->status}<br>";

        if ($userByHash->email_hash !== $targetHash) {
            echo "<span style='color:red'>HASH MISMATCH! DB: {$userByHash->email_hash} vs Target: $targetHash</span><br>";
        } else {
            echo "<span style='color:green'>HASH MATCH!</span><br>";
        }
    } else {
        // Try searching by name just in case
        $userByName = \App\Models\User::where('name', 'LIKE', '%Admin%')->get();
        echo "Users found by name 'Admin': " . $userByName->count() . "<br>";
        foreach ($userByName as $u) {
            echo " - ID: {$u->id}, Email Hash: {$u->email_hash}<br>";
        }
    }
});

Route::get('/fix-admin', function () {
    try {
        $email = 'admin@baakh.com';
        $password = 'password';

        $user = \App\Models\User::where('email_hash', hash('sha256', strtolower($email)))->first();

        if (!$user) {
            echo "Creating new user...<br>";
            $user = new \App\Models\User();
        } else {
            echo "Updating existing user (ID: {$user->id})...<br>";
        }

        $user->name = 'Super Admin';
        $user->email = $email; // This will get encrypted by cast
        $user->password = \Illuminate\Support\Facades\Hash::make($password);
        $user->username = 'superadmin';
        $user->status = 'active';
        $user->role = 'admin';
        $user->email_verified_at = now();
        $user->save();

        // Force set the hash after the first save to ensure it's not messed up by any event logic
        $user->email_hash = hash('sha256', strtolower($email));
        $user->save();

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole($role);

        echo "Admin user fixed/created successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
        echo "Try logging in now.";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');


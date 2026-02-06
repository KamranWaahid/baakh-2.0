<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class);
    Route::post('/register', RegisterController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::get('poetry/{slug}', [App\Http\Controllers\PoetryController::class, 'apiShow']);
    Route::get('poets/{slug}/couplets', [App\Http\Controllers\Api\PoetController::class, 'getCouplets']);
    Route::get('poet-tags', [App\Http\Controllers\Api\PoetController::class, 'tags']);
    Route::get('poets/{slug}/categories', [App\Http\Controllers\Api\PoetController::class, 'getCategories']);
    Route::get('poets/{slug}/poetry', [App\Http\Controllers\Api\PoetController::class, 'getPoetry']);
    Route::get('poets/{slug}', [App\Http\Controllers\Api\PoetController::class, 'show']);
    Route::get('poets', [App\Http\Controllers\Api\PoetController::class, 'index']);
    Route::get('search', [App\Http\Controllers\Api\GlobalSearchController::class, 'search']);
    Route::post('feedback', [App\Http\Controllers\Api\FeedbackController::class, 'store']);
    Route::post('report', [App\Http\Controllers\Api\ReportController::class, 'store']);

    // Sidebar Routes
    Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
    Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);

    // Feed Routes
    Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
    Route::get('categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('couplets', [App\Http\Controllers\Api\CoupletController::class, 'index']);
    Route::get('couplet-tags', [App\Http\Controllers\Api\CoupletController::class, 'tags']);
    Route::get('periods', [App\Http\Controllers\Api\PeriodController::class, 'index']);
    Route::get('periods/{id}/poets', [App\Http\Controllers\Api\PeriodController::class, 'poets']);
    Route::get('prosody', [App\Http\Controllers\Api\ProsodyController::class, 'index']);
});


use App\Http\Controllers\Api\Admin\TeamController;
use App\Http\Controllers\Api\Admin\TeamMemberController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\DatabaseController;
use App\Http\Controllers\Api\Admin\CountryController;
use App\Http\Controllers\Api\Admin\ProvinceController;
use App\Http\Controllers\Api\Admin\CityController;

// ... (Auth routes remain)

// Admin / Team Management Routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    // Teams
    Route::apiResource('teams', TeamController::class);

    // Team Members
    Route::get('teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::put('teams/{team}/members/{userId}', [TeamMemberController::class, 'update']);
    Route::delete('teams/{team}/members/{userId}', [TeamMemberController::class, 'destroy']);

    // Roles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [PermissionController::class, 'index']);

    // Locations
    Route::apiResource('countries', CountryController::class);
    Route::apiResource('provinces', ProvinceController::class);
    Route::apiResource('cities', CityController::class);
    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index']);

    // Languages
    Route::apiResource('languages', LanguageController::class);

    // Database Backups
    Route::get('databases', [DatabaseController::class, 'index']);
    Route::post('databases', [DatabaseController::class, 'store']);
    Route::delete('databases/{file_name}', [DatabaseController::class, 'destroy']);
    Route::get('databases/download', [DatabaseController::class, 'download'])->name('backup.download');

    // Existing Dashboard route
    Route::get('/dashboard', [App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
});

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function () {
        Route::get('poets/create', [\App\Http\Controllers\Api\Admin\PoetController::class, 'create']);
        Route::apiResource('poets', \App\Http\Controllers\Api\Admin\PoetController::class);
        Route::get('poetry/create', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'create']);
        Route::apiResource('poetry', \App\Http\Controllers\Api\Admin\PoetryController::class);
        Route::get('couplets', [\App\Http\Controllers\Api\Admin\CoupletController::class, 'index']);
        Route::apiResource('tags', \App\Http\Controllers\Api\Admin\TagController::class);
        Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::post('hesudhar/refresh', [\App\Http\Controllers\Api\Admin\HesudharController::class, 'refresh']);
        Route::apiResource('hesudhar', \App\Http\Controllers\Api\Admin\HesudharController::class);
        Route::post('romanizer/refresh', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'refresh']);
        Route::post('romanizer/check-words', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'checkWords']);
        Route::apiResource('romanizer', \App\Http\Controllers\Api\Admin\RomanizerController::class);



        Route::get('dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        Route::patch('poetry/{id}/toggle-visibility', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleVisibility']);
        Route::patch('poetry/{id}/toggle-featured', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleFeatured']);
    });

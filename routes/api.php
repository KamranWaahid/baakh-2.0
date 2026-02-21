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
    Route::middleware('throttle:6,1')->post('/login', LoginController::class);
    Route::middleware('throttle:6,1')->post('/register', RegisterController::class);
    Route::middleware('throttle:6,1')->post('/forgot-password', [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::middleware('throttle:6,1')->post('/reset-password', [\App\Http\Controllers\Api\Auth\ResetPasswordController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
        Route::put('/profile', [\App\Http\Controllers\Api\Auth\ProfileController::class, 'update']);
        Route::post('/profile', [\App\Http\Controllers\Api\Auth\ProfileController::class, 'update']); // FormData compat
        Route::delete('/profile', [\App\Http\Controllers\Api\Auth\ProfileController::class, 'destroy']);
        Route::put('/password', [\App\Http\Controllers\Api\Auth\ProfileController::class, 'changePassword']);
        Route::put('/password/set', [\App\Http\Controllers\Api\Auth\ProfileController::class, 'setPassword']);
        Route::get('/privacy/view-as-team', [\App\Http\Controllers\Api\Auth\PrivacyController::class, 'viewAsTeam']);

        // Per-user Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
        Route::delete('/notifications/clear', [\App\Http\Controllers\Api\NotificationController::class, 'clear']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('roles');
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

    // Word Dictionary Lookup (public)
    Route::get('word/{word}', [App\Http\Controllers\Api\WordLookupController::class, 'lookup']);

    // Sidebar Routes
    Route::get('sidebar/staff-picks', [App\Http\Controllers\Api\SidebarController::class, 'staffPicks']);
    Route::get('sidebar/topics', [App\Http\Controllers\Api\SidebarController::class, 'topics']);
    Route::get('explore-topics', [App\Http\Controllers\Api\ExploreTopicController::class, 'index']);
    Route::get('tags/{slug}', [App\Http\Controllers\Api\TopicController::class, 'showTag']);
    Route::get('topic-categories/{slug}', [App\Http\Controllers\Api\TopicController::class, 'showCategory']);

    // Feed Routes
    Route::get('feed', [App\Http\Controllers\HomeController::class, 'feed']);
    Route::get('categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('couplets', [App\Http\Controllers\Api\CoupletController::class, 'index']);
    Route::get('couplet-tags', [App\Http\Controllers\Api\CoupletController::class, 'tags']);
    Route::get('periods', [App\Http\Controllers\Api\PeriodController::class, 'index']);
    Route::get('periods/{id}/poets', [App\Http\Controllers\Api\PeriodController::class, 'poets']);
    Route::get('prosody', [App\Http\Controllers\Api\ProsodyController::class, 'index']);

    // Interaction Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('interactions/like', [App\Http\Controllers\Api\UserInteractionController::class, 'toggleLike']);
        Route::post('interactions/bookmark', [App\Http\Controllers\Api\UserInteractionController::class, 'toggleBookmark']);
        Route::post('send-email', [\App\Http\Controllers\Api\EmailController::class, 'send']);
    });
});


use App\Http\Controllers\Api\Admin\UserController;
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
use App\Http\Controllers\Api\Admin\ErrorManagementController;
use App\Http\Controllers\Api\Admin\PerformanceController;
use App\Http\Controllers\Api\Admin\AnalyticsController;

// ... (Auth routes remain)

// Admin / Team Management Routes
// Admin / Team Management Routes
Route::middleware(['auth:sanctum', 'user_role'])->prefix('admin')->group(function () {

    // Users
    Route::middleware('can:assign_roles')->apiResource('users', UserController::class);

    // Teams
    Route::middleware('can:assign_roles')->apiResource('teams', TeamController::class);

    // Team Members (Using 'manage_team_members' permission if exists, or 'assign_roles')
    // Seeder didn't explicitly list 'manage_team_members' for Admin? Let's check. 
    // Yes, 'manage_team_members' was in permission list but removed for Admin role to enforce isolation?
    // Let's use 'assign_roles' for consistency with Users.
    Route::middleware('can:assign_roles')->group(function () {
        Route::get('teams/{team}/members', [TeamMemberController::class, 'index']);
        Route::post('teams/{team}/members', [TeamMemberController::class, 'store']);
        Route::put('teams/{team}/members/{userId}', [TeamMemberController::class, 'update']);
        Route::delete('teams/{team}/members/{userId}', [TeamMemberController::class, 'destroy']);
    });

    // Roles & Permissions
    Route::middleware('can:assign_roles')->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('permissions', [PermissionController::class, 'index']);
    });

    // Locations
    Route::apiResource('countries', CountryController::class);
    Route::apiResource('provinces', ProvinceController::class);
    Route::apiResource('cities', CityController::class);
    // Activity Logs
    Route::middleware('can:view_activity_logs')->get('activity-logs', [ActivityLogController::class, 'index']);

    // Languages
    Route::apiResource('languages', LanguageController::class);

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'markAllRead']);
    Route::delete('notifications/clear', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'clear']);

    // Database & Maintenance
    Route::get('databases', [DatabaseController::class, 'index']);
    Route::post('databases', [DatabaseController::class, 'store']);
    Route::get('databases/status', [DatabaseController::class, 'status']);
    Route::post('databases/sync', [DatabaseController::class, 'syncMigrations']);
    Route::post('databases/migrate', [DatabaseController::class, 'migrate']);
    Route::post('databases/repair-permissions', [DatabaseController::class, 'repairPermissions']);
    Route::post('databases/clear-cache', [DatabaseController::class, 'clearCache']);
    Route::delete('databases/{file_name}', [DatabaseController::class, 'destroy']);
    Route::get('databases/download', [DatabaseController::class, 'download'])->name('backup.download');
    Route::get('databases/schema', [DatabaseController::class, 'getSchema']);

    // Existing Dashboard route
    Route::get('/dashboard', [App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

    // Server Management
    Route::get('server/commands', [\App\Http\Controllers\Api\Admin\ServerController::class, 'index']);
    Route::post('server/commands/run', [\App\Http\Controllers\Api\Admin\ServerController::class, 'run']);
    Route::get('server/stats', [\App\Http\Controllers\Api\Admin\ServerController::class, 'stats']);
    Route::get('server/logs', [\App\Http\Controllers\Api\Admin\ServerController::class, 'logs']);
    Route::post('server/logs/clear', [\App\Http\Controllers\Api\Admin\ServerController::class, 'clearLogs']);
    Route::post('server/shell', [\App\Http\Controllers\Api\Admin\ServerController::class, 'shell']);

    // Advanced Server Features
    Route::get('server/env', [\App\Http\Controllers\Api\Admin\ServerController::class, 'getEnv']);
    Route::post('server/env', [\App\Http\Controllers\Api\Admin\ServerController::class, 'updateEnv']);
    Route::get('server/queues', [\App\Http\Controllers\Api\Admin\ServerController::class, 'getQueues']);
    Route::post('server/queues/manage', [\App\Http\Controllers\Api\Admin\ServerController::class, 'manageFailedJob']);
    Route::get('server/search/stats', [\App\Http\Controllers\Api\Admin\ServerController::class, 'getSearchStats']);
    Route::get('server/health', [\App\Http\Controllers\Api\Admin\ServerController::class, 'getHealth']);
    Route::get('server/deployment/history', [\App\Http\Controllers\Api\Admin\ServerController::class, 'getDeploymentHistory']);

    // Error Management
    Route::post('system-errors/clear', [ErrorManagementController::class, 'clear']);
    Route::post('system-errors/verify', [ErrorManagementController::class, 'verify']);
    Route::post('system-errors/{error}/verify', [ErrorManagementController::class, 'verifyOne']);
    Route::apiResource('system-errors', ErrorManagementController::class);

    // Moderation
    Route::apiResource('reports', \App\Http\Controllers\Api\Admin\ReportController::class);
    Route::apiResource('feedback', \App\Http\Controllers\Api\Admin\FeedbackController::class);

    // Performance Analysis
    Route::post('performance/analyze-heap', [PerformanceController::class, 'analyzeHeap']);
    Route::post('performance/optimize-images', [PerformanceController::class, 'optimizeImages']);

    // System Activity
    Route::get('activity-logs', [\App\Http\Controllers\Api\Admin\ActivityLogController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'user_role'])
    ->prefix('admin')
    ->group(function () {
        Route::get('poets/create', [\App\Http\Controllers\Api\Admin\PoetController::class, 'create']);
        Route::apiResource('poets', \App\Http\Controllers\Api\Admin\PoetController::class);
        Route::get('poet-books/poet/{poet_id}', [\App\Http\Controllers\Api\Admin\PoetBookController::class, 'getPoetBooks']);
        Route::get('poet-books/{id}/pages', [\App\Http\Controllers\Api\Admin\PoetBookPageController::class, 'index']);
        Route::post('poet-books/{id}/pages/sync', [\App\Http\Controllers\Api\Admin\PoetBookPageController::class, 'sync']);
        Route::put('poet-books/{id}/pages/{pageId}', [\App\Http\Controllers\Api\Admin\PoetBookPageController::class, 'update']);
        Route::post('poet-books/{id}/pages/batch-update', [\App\Http\Controllers\Api\Admin\PoetBookPageController::class, 'batchUpdate']);
        Route::apiResource('poet-books', \App\Http\Controllers\Api\Admin\PoetBookController::class);
        Route::get('poetry/check-slug', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'checkSlug']);
        Route::get('poetry/create', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'create']);
        Route::apiResource('poetry', \App\Http\Controllers\Api\Admin\PoetryController::class);
        Route::apiResource('couplets', \App\Http\Controllers\Api\Admin\CoupletController::class);
        Route::apiResource('tags', \App\Http\Controllers\Api\Admin\TagController::class);
        Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::post('hesudhar/refresh', [\App\Http\Controllers\Api\Admin\HesudharController::class, 'refresh']);
        Route::post('hesudhar/standardize', [\App\Http\Controllers\Api\Admin\HesudharController::class, 'standardize']);
        Route::post('hesudhar/check-words', [\App\Http\Controllers\Api\Admin\HesudharController::class, 'checkWords']);
        Route::apiResource('hesudhar', \App\Http\Controllers\Api\Admin\HesudharController::class);
        Route::post('romanizer/refresh', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'refresh']);
        Route::post('romanizer/standardize', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'standardize']);
        Route::post('romanizer/check-words', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'checkWords']);
        Route::post('romanizer/transliterate', [\App\Http\Controllers\Api\Admin\RomanizerController::class, 'transliterate']);
        Route::apiResource('romanizer', \App\Http\Controllers\Api\Admin\RomanizerController::class);

        Route::get('dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        Route::apiResource('topic-categories', \App\Http\Controllers\Api\Admin\TopicCategoryController::class);
        Route::patch('poetry/{id}/toggle-visibility', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleVisibility']);
        Route::patch('poetry/{id}/toggle-featured', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleFeatured']);

        // Corpus Routes
        Route::get('corpus/sentences', [\App\Http\Controllers\Api\Admin\CorpusController::class, 'index']);
        Route::get('corpus/stats', [\App\Http\Controllers\Api\Admin\CorpusController::class, 'stats']);
        Route::get('corpus/clusters', [\App\Http\Controllers\Api\Admin\CorpusController::class, 'clusters']);
        Route::get('corpus/trends', [\App\Http\Controllers\Api\Admin\CorpusController::class, 'trends']);

        // Analytics Routes
        Route::get('analytics/frequency', [AnalyticsController::class, 'frequency']);

        // Dictionary Routes
        Route::apiResource('dictionary/lemmas', \App\Http\Controllers\Api\Admin\DictionaryController::class);
        Route::patch('dictionary/lemmas/{id}/approve', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'approve']);
        Route::post('dictionary/lemmas/{lemmaId}/senses', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'storeSense']);
        Route::put('dictionary/senses/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'updateSense']);
        Route::post('dictionary/senses', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'storeSense']);
        Route::delete('dictionary/senses/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'destroySense']);
        Route::post('dictionary/senses/{senseId}/examples', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'storeExample']);
        Route::put('dictionary/examples/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'updateExample']);
        Route::delete('dictionary/examples/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'destroyExample']);
        Route::put('dictionary/lemmas/{id}/morphology', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'updateMorphology']);
        Route::post('dictionary/lemmas/{id}/variants', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'storeVariant']);
        Route::delete('dictionary/variants/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'destroyVariant']);
        Route::post('dictionary/lemmas/{id}/relations', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'storeRelation']);
        Route::delete('dictionary/relations/{id}', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'destroyRelation']);
        Route::post('dictionary/lemmas/{id}/scrape-sindhila', [\App\Http\Controllers\Api\Admin\DictionaryController::class, 'scrapeSindhila']);

        // ── Mokhii SEO Dashboard ────────────────────
        Route::get('mokhii/dashboard', [\App\Http\Controllers\Api\Admin\MokhiiDashboardController::class, 'index']);
        Route::post('mokhii/crawl', [\App\Http\Controllers\Api\Admin\MokhiiDashboardController::class, 'triggerCrawl']);
        Route::post('mokhii/compute', [\App\Http\Controllers\Api\Admin\MokhiiDashboardController::class, 'triggerCompute']);
        Route::post('mokhii/autofix', [\App\Http\Controllers\Api\Admin\MokhiiDashboardController::class, 'triggerAutoFix']);
    });

/*
|--------------------------------------------------------------------------
| Mokhii Public API (Structured Data for AI Agents & Search Engines)
|--------------------------------------------------------------------------
*/
Route::prefix('mokhii')->middleware('detect.ai.agent')->group(function () {
    Route::get('health', [\App\Http\Controllers\Api\MokhiiApiController::class, 'health']);
    Route::get('context/{slug}', [\App\Http\Controllers\Api\MokhiiApiController::class, 'context']);
    Route::get('graph/{type}/{id}', [\App\Http\Controllers\Api\MokhiiApiController::class, 'graph']);
    Route::get('schema/{slug}', [\App\Http\Controllers\Api\MokhiiApiController::class, 'schema']);
    Route::get('cluster/{topic}', [\App\Http\Controllers\Api\MokhiiApiController::class, 'cluster']);
    Route::get('audits', [\App\Http\Controllers\Api\MokhiiApiController::class, 'audits']);
});

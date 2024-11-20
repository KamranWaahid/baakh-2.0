<?php

use App\Http\Controllers\BaakhSearchController;
use App\Http\Controllers\BundlesController;
use App\Http\Controllers\CoupletsController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LikeDislikeController;
use App\Http\Controllers\PeriodsController;
use App\Http\Controllers\PoetryController;
use App\Http\Controllers\PoetsController;
use App\Http\Controllers\ProsodyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\Users\UserCommentsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

 // Home URL
 Route::get('/', [HomeController::class, 'index'])->name('web.index');
 //Route::get('/home', [HomeController::class, 'index'])->name('web.home');
 Route::get('/about', [HomeController::class, 'about'])->name('web.about');
 //Route::get('/contact', [HomeController::class, 'contact'])->name('web.contact');
 Route::post('/test-fun', [HomeController::class, '_test_fun']);

 Route::prefix('/poetry')->group(function () {
     Route::post('/quiz-check', [HomeController::class, 'quizCheck']);
 });

Route::prefix('tags/')->group(function(){

    Route::get('', [TagsController::class, 'index'])->name('web.tags');
    Route::get('{tag}/{category?}', [TagsController::class, 'show'])->name('poetry.with-tag');
    Route::post('poetry/load-more-poetry', [TagsController::class, 'load_more_poetry']);

});


 Route::prefix('/poets')->group(function () {
     Route::get('/', [PoetsController::class, 'index'])->name('poets.all');
     Route::get('/filter/{tag}', [PoetsController::class, 'with_tags'])->name('poets.with-tags');
     Route::get('/{name}/{category?}', [PoetsController::class, 'with_slug'])->name('poets.slug');
     Route::post('/poetry/load-more-poetry', [PoetsController::class, 'load_more_poetry'])->name('poets.more-poetry');
 });

// Bundles
Route::prefix('/bundles')->group(function(){
    Route::get('/', [BundlesController::class, 'index'])->name('poetry.bundle');
    Route::get('/{slug}', [BundlesController::class, 'with_slug'])->name('poetry.bundle.slug');

});

 Route::prefix('/poetry')->group(function(){
     Route::get('/', [PoetryController::class, 'index'])->name('poetry.index');
     //Route::get('/genre/{slug}', [PoetryController::class, 'with_genre'])->name('poetry.with-genre');
     Route::get('/{category}/{slug}', [PoetryController::class, 'with_slug'])->name('poetry.with-slug');
 });


 Route::prefix('couplets')->group(function () {
    Route::get('', [CoupletsController::class, 'index'])->name('web.couplets');
    Route::get('most-liked', [CoupletsController::class, 'mostLikedCouplets'])->name('web.couplets.most-liked');
    Route::get('/{slug}', [CoupletsController::class, 'show'])->name('web.couplets.single');
 });


Route::post('like/{likableType}/{likableId}', [LikeDislikeController::class, 'like'])->name('like');
Route::post('dislike/{likableType}/{likableId}', [LikeDislikeController::class, 'dislike'])->name('dislike');
Route::get('count-likes/{likableType}/{likableId}', [LikeDislikeController::class, 'countLikes'])->name('count.likes');


// comments
Route::prefix('comments/')->group(function(){
    Route::post('submit', [UserCommentsController::class, 'postComment'])->middleware(['web', 'auth'])->name('comment.send');
    Route::post('update', [UserCommentsController::class, 'updateComment'])->middleware(['web', 'auth'])->name('comment.update');
    Route::post('load-more', [PoetryController::class, 'loadMoreComments'])->name('comments.load-more');
    Route::post('like-dislike', [LikeDislikeController::class, 'like'])->name('item.like-dislike');
});

// Genres
Route::prefix('genres/')->group(function(){
    Route::get('', [GenreController::class, 'index'])->name('genres');
    Route::get('detail/{slug}', [GenreController::class, 'show'])->name('genres.show');
    Route::get('{slug}/poetry', [GenreController::class, 'poetry'])->name('genres.poetry');
});

// Daur
Route::prefix('periods/')->group(function(){
    Route::get('', [PeriodsController::class, 'index'])->name('periods');
    Route::get('poets/{period}', [PeriodsController::class, 'poets'])->name('periods.poets');
});

// Chhand
Route::prefix('prosody/')->group(function(){
    Route::get('', [ProsodyController::class, 'index'])->name('prosody');
    Route::post('result', [ProsodyController::class, 'result'])->name('prosody.result');
});

// Search
Route::prefix('search')->name('web.search.')->controller(BaakhSearchController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/generate-json', 'generateJson')->name('generate-json');
    Route::get('/suggestions/{q}/{lang}', 'getSuggestions')->name('suggestions');
});

/**
 * Sitemap files
 */
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('sitemap/poets.xml', [SitemapController::class, 'poets'])->name('sitemap.poets');
Route::get('sitemap/poets/{year}/{month}.xml', [SitemapController::class, 'poetsByMonth'])->name('sitemap.poets.month');
Route::get('sitemap/poetry.xml', [SitemapController::class, 'poetry'])->name('sitemap.poetry');
Route::get('sitemap/poetry/{year}/{month}.xml', [SitemapController::class, 'poetryByMonth'])->name('sitemap.poetry.month');
Route::get('sitemap/couplets.xml', [SitemapController::class, 'couplets'])->name('sitemap.couplets');
Route::get('sitemap/couplets/{year}/{month}.xml', [SitemapController::class, 'coupletsByMonth'])->name('sitemap.couplets.month');
Route::get('sitemap/tags.xml', [SitemapController::class, 'tags'])->name('sitemap.tags');
Route::get('sitemap/tags/{year}/{month}.xml', [SitemapController::class, 'tagsByMonth'])->name('sitemap.tags.month');

Route::get('sitemap/pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('sitemap/categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');


 Auth::routes();
 require __DIR__.'/auth.php';
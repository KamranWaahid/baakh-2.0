<?php
use App\Http\Controllers\admin\AdminBundlesController;
use App\Http\Controllers\admin\AdminCategoriesController;
use App\Http\Controllers\admin\AdminCitiesController;
use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\AdminCountriesController;
use App\Http\Controllers\admin\AdminCoupletsController;
use App\Http\Controllers\admin\AdminMediaController;
use App\Http\Controllers\admin\AdminPoetryController;
use App\Http\Controllers\admin\AdminPoetryTranslationsController;
use App\Http\Controllers\admin\AdminPoetsController;
use App\Http\Controllers\admin\AdminProvincesController;
use App\Http\Controllers\admin\AdminRomanizerController;
use App\Http\Controllers\admin\AdminSlidersController;
use App\Http\Controllers\admin\DoodlesController as AdminDoodles;
use App\Http\Controllers\admin\AdminTagsController;
use App\Http\Controllers\admin\AdminUsersController;
use App\Http\Controllers\Admin\HesudharController;
use App\Http\Controllers\admin\LanguagesController;
use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\admin\ProfileController;
use App\Http\Controllers\admin\RoleController;
use Illuminate\Support\Facades\Route;
 


 

Route::get('', [AdminController::class, 'index'])->name('admin.home')->middleware('permission:view.dashboard');
Route::get('dashboard', [AdminController::class, 'index'])->name('dashboard')->middleware('permission:view.dashboard');

// Admins
Route::get('/admins', [AdminUsersController::class, 'admins'])->name('admin.admins')->middleware('permission:users.menu');


// Users
Route::prefix('users')->group(function(){
    Route::get('', [AdminUsersController::class, 'index'])->name('admin.users')->middleware('permission:users.menu');
    Route::get('create', [AdminUsersController::class, 'create'])->name('admin.users.create')->middleware('permission:users.add');
    Route::post('store', [AdminUsersController::class, 'store'])->name('admin.users.store')->middleware('permission:users.add');
    Route::get('{id}/edit', [AdminUsersController::class, 'edit'])->name('admin.users.edit')->middleware('permission:users.edit');
    Route::post('{id}/update', [AdminUsersController::class, 'update'])->name('admin.users.update')->middleware('permission:users.edit');
    Route::delete('{id}/delete', [AdminUsersController::class, 'index'])->name('admin.users.delete')->middleware('permission:users.delete');
});


// User Permissions
Route::prefix('permissions')->group(function(){
    Route::get('/', [PermissionController::class, 'index'])->name('permissions.index')->middleware('permission:permissions.menu');
    Route::get('create', [PermissionController::class, 'create'])->name('permissions.create')->middleware('permission:permissions.add');
    Route::post('store', [PermissionController::class, 'store'])->name('permissions.store')->middleware('permission:permissions.add');
    Route::get('edit/{id}', [PermissionController::class, 'edit'])->name('permissions.edit')->middleware('permission:permissions.edit');
    Route::put('/update/{id}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('permission:permissions.edit');
    Route::delete('destroy/{id}', [PermissionController::class, 'destroy'])->name('permissions.delete')->middleware('permission:permissions.delete');
});

// Permission Roles (Groups)
Route::prefix('roles')->group(function(){
    Route::get('/', [RoleController::class, 'index'])->name('role.index')->middleware('permission:roles.menu');
    Route::post('store', [RoleController::class, 'store'])->name('role.store')->middleware('permission:roles.add');
    Route::get('edit/{id}', [RoleController::class, 'edit'])->name('role.edit')->middleware('permission:roles.edit');
    Route::put('/update/{id}', [RoleController::class, 'update'])->name('role.update')->middleware('permission:roles.edit');
    Route::delete('destroy/{id}', [RoleController::class, 'destroy'])->name('role.delete')->middleware('permission:roles.delete');

    // roles in permissions
    Route::get('permissions', [RoleController::class, 'RolesInPermissionsAll'])->name('role.permissions.index')->middleware('permission:permissions.menu');
    Route::get('permissions/create', [RoleController::class, 'RolesInPermissionsCreate'])->name('role.permission.create')->middleware('permission:permissions.add');
    Route::post('permissions/store', [RoleController::class, 'RolesInPermissionsStore'])->name('role.permission.store')->middleware('permission:permissions.add');
    Route::get('permissions/edit/{id}', [RoleController::class, 'RolesInPermissionsEdit'])->name('role.permission.edit')->middleware('permission:permissions.edit');
    Route::put('permissions/update/{id}', [RoleController::class, 'RolesInPermissionsUpdate'])->name('role.permission.update')->middleware('permission:permissions.edit');
    Route::delete('permissions/delete/{id}', [RoleController::class, 'RolesInPermissionsDelete'])->name('role.permission.delete')->middleware('permission:permissions.delete');
});



// profile
Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit')->middleware('permission:profile.edit');
Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('permission:profile.edit');
Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware('permission:profile.delete');

// For Sliders
Route::prefix('sliders')->group(function () {
    Route::get('', [AdminSlidersController::class, 'index'])->name('admin.sliders.index')->middleware('permission:sliders.menu');
    Route::get('trashed', [AdminSlidersController::class, 'trashed'])->name('admin.sliders.trash')->middleware('permission:sliders.menu');
    Route::get('create', [AdminSlidersController::class, 'create'])->name('admin.sliders.create')->middleware('permission:sliders.add');
    Route::post('sliders', [AdminSlidersController::class, 'store'])->name('admin.sliders.store')->middleware('permission:sliders.add');
    Route::get('{id}/edit', [AdminSlidersController::class, 'edit'])->name('admin.sliders.edit')->middleware('permission:sliders.edit');
    Route::put('{id}', [AdminSlidersController::class, 'update'])->name('admin.sliders.update')->middleware('permission:sliders.edit');
    Route::delete('{id}', [AdminSlidersController::class, 'destroy'])->name('admin.sliders.destroy')->middleware('permission:sliders.delete');
    Route::delete('{id}/hard-delete', [AdminSlidersController::class, 'hardDelete'])->name('admin.sliders.hard-delete')->middleware('permission:sliders.delete');
    Route::put('{id}/restore', [AdminSlidersController::class, 'restore'])->name('admin.sliders.restore')->middleware('permission:sliders.edit');
    Route::put('{id}/toggle-visibility', [AdminSlidersController::class, 'toggleVisibility'])->name('admin.sliders.toggle-visibility')->middleware('permission:sliders.edit');
});

Route::name('admin.')->group(function () {
    Route::get('doodles/trashed', [AdminDoodles::class, 'trashed'])->name('doodles.trash');
    Route::put('{id}/restore', [AdminDoodles::class, 'restore'])->name('doodles.restore');
    Route::delete('{id}/hard-delete', [AdminDoodles::class, 'hardDelete'])->name('doodles.hard-delete');
    Route::resource('doodles', AdminDoodles::class);
});

// For Poetry
Route::prefix('poetry')->group(function () {
    Route::get('', [AdminPoetryController::class, 'index'])->name('admin.poetry.index')->middleware('permission:poetry.menu');
    Route::get('poet/{slug}', [AdminPoetryController::class, 'poet'])->name('admin.poetry.filter')->middleware('permission:poetry.menu');
    Route::get('trashed', [AdminPoetryController::class, 'trashed'])->name('admin.poetry.trash')->middleware('permission:poetry.menu');
    Route::get('create', [AdminPoetryController::class, 'create'])->name('admin.poetry.create')->middleware('permission:poetry.add');
    Route::post('store', [AdminPoetryController::class, 'store'])->name('admin.poetry.store')->middleware('permission:poetry.add');
    Route::get('{id}/edit', [AdminPoetryController::class, 'edit'])->name('admin.poetry.edit')->middleware('permission:poetry.edit');
    
    Route::put('{id}/update', [AdminPoetryController::class, 'update'])->name('admin.poetry.update')->middleware('permission:poetry.edit');
    Route::delete('{id}/delete', [AdminPoetryController::class, 'destroy'])->name('admin.poetry.destroy')->middleware('permission:poetry.delete');
    Route::put('{id}/toggle-visibility', [AdminPoetryController::class, 'toggleVisibility'])->name('admin.poetry.toggle-visibility')->middleware('permission:poetry.edit');
    Route::put('{id}/toggle-featured', [AdminPoetryController::class, 'toggleFeatured'])->name('admin.poetry.toggle-featured')->middleware('permission:poetry.edit');
    Route::delete('{id}/hard-delete', [AdminPoetryController::class, 'hardDelete'])->name('admin.poetry.hard-delete')->middleware('permission:poetry.delete');
    Route::put('{id}/restore', [AdminPoetryController::class, 'restore'])->name('admin.poetry.restore')->middleware('permission:poetry.edit');
    /* Route::get('{id}/duplicate', [AdminPoetryController::class, 'duplicate'])->name('admin.poetry.duplicate')->middleware('permission:poetry.add'); */
    Route::post('check-slug', [AdminPoetryController::class, 'check_slug'])->name('admin.poetry.check-slug')->middleware('permission:poetry.menu');
    Route::get('datatable', [AdminPoetryController::class, 'dataTablePoetry'])->name('admin.poetry.datatable')->middleware('permission:poetry.menu');

    // Translations
    Route::prefix('{id}/translations/{language}/')->group(function() {
        Route::get('create', [AdminPoetryTranslationsController::class, 'createTranslations'])->name('admin.poetry.add-translation')->middleware('permission:poetry.edit');
        Route::get('edit', [AdminPoetryTranslationsController::class, 'editTranslations'])->name('admin.poetry.edit-translation');
        Route::post('store/info', [AdminPoetryTranslationsController::class, 'addInfo'])->name('admin.poetry.translation.store-info');
        Route::put('update/info', [AdminPoetryTranslationsController::class, 'updateInfo'])->name('admin.poetry.translation.update-info');
        
        Route::post('store/couplets', [AdminPoetryTranslationsController::class, 'addCoupletsTranslation'])->name('admin.poetry.translation.store-couplets');
        Route::put('update/couplets', [AdminPoetryTranslationsController::class, 'updateCoupletsTranslation'])->name('admin.poetry.translation.update-couplets');
    });
    

});


// languages
Route::prefix('languages')->group(function () {
    Route::get('', [LanguagesController::class, 'index'])->name('languages.index');
    Route::get('create', [LanguagesController::class, 'create'])->name('languages.create');
    Route::post('', [LanguagesController::class, 'store'])->name('languages.store');
    Route::get('{id}', [LanguagesController::class, 'show'])->name('languages.show');
    Route::get('{id}/edit', [LanguagesController::class, 'edit'])->name('languages.edit');
    Route::put('{id}', [LanguagesController::class, 'update'])->name('languages.update');
    Route::delete('{id}', [LanguagesController::class, 'destroy'])->name('languages.destroy');

});

// Categories
Route::prefix('categories')->group(function () {
    Route::get('', [AdminCategoriesController::class, 'index'])->name('admin.categories.index')->middleware('permission:categories.menu');
    Route::get('trashed', [AdminCategoriesController::class, 'trashed'])->name('admin.categories.trash')->middleware('permission:categories.menu');
    Route::post('', [AdminCategoriesController::class, 'store'])->name('admin.categories.store')->middleware('permission:categories.add');
    Route::get('{id}', [AdminCategoriesController::class, 'show'])->name('admin.categories.show')->middleware('permission:categories.add');
    Route::get('{id}/edit', [AdminCategoriesController::class, 'edit'])->name('admin.categories.edit')->middleware('permission:categories.edit');
    Route::put('{id}', [AdminCategoriesController::class, 'update'])->name('admin.categories.update')->middleware('permission:categories.edit');
    Route::delete('{id}', [AdminCategoriesController::class, 'destroy'])->name('admin.categories.destroy')->middleware('permission:categories.delete');
    Route::delete('{id}/hard-delete', [AdminCategoriesController::class, 'hardDelete'])->name('admin.categories.hard-delete')->middleware('permission:categories.delete');
    Route::put('{id}/restore', [AdminCategoriesController::class, 'restore'])->name('admin.categories.restore')->middleware('permission:categories.edit');
    Route::get('{id},{language}/duplicate', [AdminCategoriesController::class, 'duplicate'])->name('admin.categories.duplicate')->middleware('permission:categories.add');
});

// Countries
Route::prefix('countries')->group(function () {
    Route::get('', [AdminCountriesController::class, 'index'])->name('admin.countries.index')->middleware('permission:countries.menu');
    Route::post('', [AdminCountriesController::class, 'store'])->name('admin.countries.store')->middleware('permission:countries.add');
    Route::get('{id}', [AdminCountriesController::class, 'show'])->name('admin.countries.show')->middleware('permission:countries.add');
    Route::get('{id}/edit', [AdminCountriesController::class, 'edit'])->name('admin.countries.edit')->middleware('permission:countries.edit');
    Route::put('{id}', [AdminCountriesController::class, 'update'])->name('admin.countries.update')->middleware('permission:countries.edit');
    Route::delete('{id}', [AdminCountriesController::class, 'destroy'])->name('admin.countries.destroy')->middleware('permission:countries.delete');

});

// Provinces
Route::prefix('provinces')->group(function () {
    Route::get('', [AdminProvincesController::class, 'index'])->name('admin.provinces.index')->middleware('permission:province.menu');
    Route::post('', [AdminProvincesController::class, 'store'])->name('admin.provinces.store')->middleware('permission:province.add');
    Route::get('{id}', [AdminProvincesController::class, 'show'])->name('admin.provinces.show')->middleware('permission:province.add');
    Route::get('{id}/edit', [AdminProvincesController::class, 'edit'])->name('admin.provinces.edit')->middleware('permission:province.edit');
    Route::put('{id}', [AdminProvincesController::class, 'update'])->name('admin.provinces.update')->middleware('permission:province.edit');
    Route::delete('{id}', [AdminProvincesController::class, 'destroy'])->name('admin.provinces.destroy')->middleware('permission:province.delete');
});

// Cities
Route::prefix('cities')->group(function () {
    Route::get('', [AdminCitiesController::class, 'index'])->name('admin.cities.index')->middleware('permission:cities.menu');
    Route::post('', [AdminCitiesController::class, 'store'])->name('admin.cities.store')->middleware('permission:cities.add');
    //Route::get('{id}', [AdminCitiesController::class, 'show'])->name('admin.cities.show')->middleware('permission:cities.add');
    Route::get('{id}/edit', [AdminCitiesController::class, 'edit'])->name('admin.cities.edit')->middleware('permission:cities.edit');
    Route::put('{id}/update', [AdminCitiesController::class, 'update'])->name('admin.cities.update')->middleware('permission:cities.edit');
    Route::delete('{id}/delete', [AdminCitiesController::class, 'destroy'])->name('admin.cities.destroy')->middleware('permission:cities.delete');
});

Route::get('getProvinces/{countryId}', [AdminCitiesController::class, 'getProvinces']);
Route::get('getCitiesByLang/{lang}', [AdminCitiesController::class, 'getCitiesByLang']);

// Poets Routes
Route::prefix('poets')->group(function () {
    Route::get('', [AdminPoetsController::class, 'index'])->name('admin.poets.index')->middleware('permission:poets.menu');
    Route::get('trashed', [AdminPoetsController::class, 'trashed'])->name('admin.poets.trash')->middleware('permission:poets.menu');
    Route::get('create', [AdminPoetsController::class, 'create'])->name('admin.poets.create')->middleware('permission:poets.add');
    Route::post('', [AdminPoetsController::class, 'store'])->name('admin.poets.store')->middleware('permission:poets.add');
    Route::get('{id}', [AdminPoetsController::class, 'show'])->name('admin.poets.show')->middleware('permission:poets.add');
    Route::get('{id}/edit', [AdminPoetsController::class, 'edit'])->name('admin.poets.edit')->middleware('permission:poets.edit');
    Route::put('{id}', [AdminPoetsController::class, 'update'])->name('admin.poets.update')->middleware('permission:poets.edit');
    Route::delete('{id}', [AdminPoetsController::class, 'destroy'])->name('admin.poets.destroy')->middleware('permission:poets.delete');
    Route::put('{id}/toggle-visibility', [AdminPoetsController::class, 'toggleVisibility'])->name('admin.poets.toggle-visibility')->middleware('permission:poets.edit');
    Route::put('{id}/toggle-featured', [AdminPoetsController::class, 'toggleFeatured'])->name('admin.poets.toggle-featured')->middleware('permission:poets.edit');
    Route::delete('{id}/hard-delete', [AdminPoetsController::class, 'hardDelete'])->name('admin.poets.hard-delete')->middleware('permission:poets.delete');
    Route::put('{id}/restore', [AdminPoetsController::class, 'restore'])->name('admin.poets.restore')->middleware('permission:poets.edit');
    Route::get('poets-by-language/{lang}', [AdminPoetsController::class, 'with_language'])->name('admin.poets.by-lang')->middleware('permission:poets.menu');
    // get poet by name ajax
    Route::post('/ajax-by-name', [AdminPoetsController::class, 'ajax_poets'])->name('admin.poets.by-name');
});

// Romanizers Routes
Route::prefix('romanizer')->group(function () {
    Route::get('', [AdminRomanizerController::class, 'index'])->name('admin.romanizer.index')->middleware('permission:romanizer.menu');
    Route::get('words', [AdminRomanizerController::class, 'words'])->name('admin.romanizer.words')->middleware('permission:romanizer.menu');
    Route::get('words/trashed', [AdminRomanizerController::class, 'with_trashed'])->name('admin.romanizer.words-trashed')->middleware('permission:romanizer.menu');
    Route::post('words', [AdminRomanizerController::class, 'store'])->name('admin.romanizer.store')->middleware('permission:romanizer.add');
    Route::delete('{id}', [AdminRomanizerController::class, 'destroy'])->name('admin.romanizer.destroy')->middleware('permission:romanizer.delete');
    Route::delete('{id}/hard-delete', [AdminRomanizerController::class, 'hardDelete'])->name('admin.romanizer.hard-delete')->middleware('permission:romanizer.delete');
    Route::put('{id}/restore', [AdminRomanizerController::class, 'restore'])->name('admin.romanizer.restore')->middleware('permission:romanizer.edit');
    Route::get('refresh-dictionary', [AdminRomanizerController::class, 'refresh'])->name('admin.romanizer.refresh-dictionary')->middleware('permission:romanizer.menu');
    Route::put('check-words', [AdminRomanizerController::class, 'checkWords'])->name('admin.romanizer.check-words')->middleware('permission:romanizer.menu');
    Route::get('words/all', [AdminRomanizerController::class, 'dataTableWords'])->name('admin.romanizer.data')->middleware('permission:romanizer.menu');
    Route::get('words/all-trashed', [AdminRomanizerController::class, 'dataTableWords_trashed'])->name('admin.romanizer.data-trashed')->middleware('permission:romanizer.menu');
    Route::post('update', [AdminRomanizerController::class, 'update'])->name('admin.romanizer.edit')->middleware('permission:romanizer.edit');
});

// Hesudhar
Route::prefix('hesudhar/')->middleware(['auth'])->group(function() {
    Route::get('', [HesudharController::class, 'index'])->name('admin.hesudhar');
    Route::get('trashed', [HesudharController::class, 'with_trashed'])->name('admin.hesudhar.words-trashed');
    Route::post('add-new', [HesudharController::class, 'store'])->name('admin.hesudhar.store');
    Route::post('update', [HesudharController::class, 'update'])->name('admin.hesudhar.edit');
    Route::delete('{id}', [HesudharController::class, 'destroy'])->name('admin.hesudhar.destroy');
    Route::delete('{id}/hard-delete', [HesudharController::class, 'hardDelete'])->name('admin.hesudhar.hard-delete');
    Route::put('{id}/restore', [HesudharController::class, 'restore'])->name('admin.hesudhar.restore');
    Route::get('refresh-file', [HesudharController::class, 'refresh'])->name('admin.hesudhar.refresh-file');
    Route::get('words/all', [HesudharController::class, 'dataTableWords'])->name('admin.hesudhar.data');
    Route::get('words/all-trashed', [HesudharController::class, 'dataTableWords_trashed'])->name('admin.hesudhar.data-trashed');
});

// Bundles Routes
Route::prefix('bundles')->group(function () {
    Route::get('', [AdminBundlesController::class, 'index'])->name('admin.bundle.index')->middleware('permission:bundles.menu');
    Route::get('trashed', [AdminBundlesController::class, 'trashed'])->name('admin.bundle.trash')->middleware('permission:bundles.menu');
    Route::get('create', [AdminBundlesController::class, 'create'])->name('admin.bundle.create')->middleware('permission:bundles.add');
    Route::put('', [AdminBundlesController::class, 'store'])->name('admin.bundle.store')->middleware('permission:bundles.add');
    Route::get('{id}/edit', [AdminBundlesController::class, 'edit'])->name('admin.bundle.edit')->middleware('permission:bundles.edit');
    Route::put('{id}/update', [AdminBundlesController::class, 'update'])->name('admin.bundle.update')->middleware('permission:bundles.edit');
    Route::put('{id}/update-translation', [AdminBundlesController::class, 'update_translation'])->name('admin.bundle.edit-translation')->middleware('permission:bundles.edit');
    Route::delete('{id}', [AdminBundlesController::class, 'destroy'])->name('admin.bundle.destroy')->middleware('permission:bundles.delete');
    Route::delete('{id}/hard-delete', [AdminBundlesController::class, 'hardDelete'])->name('admin.bundle.hard-delete')->middleware('permission:bundles.delete');
    Route::put('{id}/restore', [AdminBundlesController::class, 'restore'])->name('admin.bundle.restore')->middleware('permission:bundles.edit');
    Route::post('couplets', [AdminBundlesController::class, 'searchCouplets'])->name('admin.bundle.couplets')->middleware('permission:bundles.menu');
    Route::put('{id}/toggle-visibility', [AdminBundlesController::class, 'toggleVisibility'])->name('admin.bundle.toggle-visibility')->middleware('permission:bundles.edit');
    Route::put('{id}/toggle-featured', [AdminBundlesController::class, 'toggleFeatured'])->name('admin.bundle.toggle-featured')->middleware('permission:bundles.edit');
});

// Couplets Routes
Route::prefix('couplets')->group(function () {
    Route::get('', [AdminCoupletsController::class, 'index'])->name('admin.couplets.index')->middleware('permission:couplets.menu');
    Route::get('trashed', [AdminCoupletsController::class, 'trashed'])->name('admin.couplets.trash')->middleware('permission:couplets.menu');
    Route::get('create', [AdminCoupletsController::class, 'create'])->name('admin.couplets.create')->middleware('permission:couplets.add');
    Route::put('', [AdminCoupletsController::class, 'store'])->name('admin.couplets.store')->middleware('permission:couplets.add');
    Route::get('{id}/edit', [AdminCoupletsController::class, 'edit'])->name('admin.couplets.edit')->middleware('permission:couplets.edit');
    Route::put('{id}', [AdminCoupletsController::class, 'update'])->name('admin.couplets.update')->middleware('permission:couplets.edit');
    Route::delete('{id}', [AdminCoupletsController::class, 'destroy'])->name('admin.couplets.destroy')->middleware('permission:couplets.delete');
    Route::delete('{id}/hard-delete', [AdminCoupletsController::class, 'hardDelete'])->name('admin.couplets.hard-delete')->middleware('permission:couplets.delete');
    Route::put('{id}/restore', [AdminCoupletsController::class, 'restore'])->name('admin.couplets.restore')->middleware('permission:couplets.edit');
    Route::get('data-table', [AdminCoupletsController::class, 'dataTableCouplets'])->name('admin.couplets.dataTableCouplets')->middleware('permission:couplets.menu');

    Route::post('check-slug', [AdminCoupletsController::class, 'checkUniqueSlug'])->name('check.unique.slug.couplets');
});

// Tags Routes
Route::prefix('tags')->group(function () {
    Route::get('', [AdminTagsController::class, 'index'])->name('admin.tags.index')->middleware('permission:tags.menu');
    Route::get('trashed', [AdminTagsController::class, 'with_trashed'])->name('admin.tags.trashed')->middleware('permission:tags.menu');
    Route::get('create', [AdminTagsController::class, 'create'])->name('admin.tags.create')->middleware('permission:tags.add');
    Route::post('', [AdminTagsController::class, 'store'])->name('admin.tags.store')->middleware('permission:tags.add');
    Route::get('edit/{id}', [AdminTagsController::class, 'edit'])->name('admin.tags.edit')->middleware('permission:tags.edit');
    Route::put('update', [AdminTagsController::class, 'update'])->name('admin.tags.update')->middleware('permission:tags.edit');
    Route::delete('delete/{id}', [AdminTagsController::class, 'destroy'])->name('admin.tags.destroy')->middleware('permission:tags.delete');
    Route::delete('delete/{id}/hard-delete', [AdminTagsController::class, 'hardDelete'])->name('admin.tags.hard-delete')->middleware('permission:tags.delete');
    Route::put('{id}/restore', [AdminTagsController::class, 'restore'])->name('admin.tags.restore')->middleware('permission:tags.edit');
    Route::get('with-lang/{lang}', [AdminTagsController::class, 'with_language'])->middleware('permission:tags.menu');
    Route::get('/data-tables', [AdminTagsController::class, 'allTagsDataTable'])->name('admin.tags.data-table')->middleware('permission:tags.menu');
});


// Baakh Media
Route::prefix('media')->group(function(){
    Route::get('create/{id}', [AdminMediaController::class, 'index'])->name('admin.media.create')->middleware('permission:media.add');
    Route::get('edit/{id}', [AdminMediaController::class, 'edit'])->name('admin.media.edit')->middleware('permission:media.edit');
    Route::put('store', [AdminMediaController::class, 'store'])->name('admin.media.store')->middleware('permission:media.add');
    Route::delete('delete/{id}', [AdminMediaController::class, 'destroy'])->name('admin.media.delete')->middleware('permission:media.delete');
});
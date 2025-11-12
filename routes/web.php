<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;

Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/upload', [ProfileController::class, 'show_files'])->name('profile.upload');
    Route::post('/profile/upload', [FileUploadController::class, 'upload']);
    Route::get('/api/geojson/{id}', [FileUploadController::class, 'getGeojsonMetadata']);
    Route::get('/profile/change-password', [ProfileController::class, 'changepassword'])->name('profile.change-password');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/blank-page', [App\Http\Controllers\ProfileController::class, 'blank'])->name('blank');
    Route::post('/profile/add-dashboard', [App\Http\Controllers\ProfileController::class, 'add_dashboard']);
    Route::get('/profile/add-dashboard', function () { return view('profile.add-dashboard');})->name('profile.add-dashboard');
    Route::get('/profile/dashboard/{id}', [App\Http\Controllers\DashboardController::class, 'show_dashboard'])->name('profile.dashboard');
    Route::post('/profile/dashboard/update-bounds', [\App\Http\Controllers\DashboardController::class, 'updateBounds'])->name('dashboard.update-bounds'); //bounds of map update widgets
    Route::get('/profile/add-widgets/{id}', [App\Http\Controllers\DashboardController::class, 'add_widgets'])->name('profile.add-widgets');
    Route::post('/profile/add-widgets/{id}', [App\Http\Controllers\DashboardController::class, 'add_widget']);
    Route::post('/profile/delete-widget/{id}', [App\Http\Controllers\DashboardController::class, 'delete_widget'])->name('profile.delete-widget');
    Route::post('/profile/delete-dashboard/{id}', [App\Http\Controllers\DashboardController::class, 'delete_dashboard'])->name('profile.delete-dashboard');
    Route::post('/profile/get-file-metadata', [App\Http\Controllers\ProfileController::class, 'get_file_metadata']);
    Route::get('/profile/get-geojson/{filename}', [App\Http\Controllers\DashboardController::class, 'get_geojson'])->name('profile.get-geojson');
    Route::get('/geojson/{id}/collection', [FileUploadController::class, 'toFeatureCollection']);
    Route::post('/profile/widget-columns', [App\Http\Controllers\DashboardController::class, 'saveWidgetColumns'])->name('profile.save-widget-columns');

    Route::get('/hakakses', [App\Http\Controllers\HakaksesController::class, 'index'])->name('hakakses.index')->middleware('superadmin');
    Route::get('/hakakses/edit/{id}', [App\Http\Controllers\HakaksesController::class, 'edit'])->name('hakakses.edit')->middleware('superadmin');
    Route::put('/hakakses/update/{id}', [App\Http\Controllers\HakaksesController::class, 'update'])->name('hakakses.update')->middleware('superadmin');
    Route::delete('/hakakses/delete/{id}', [App\Http\Controllers\HakaksesController::class, 'destroy'])->name('hakakses.delete')->middleware('superadmin');


});

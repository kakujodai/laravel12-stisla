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

    Route::get('/hakakses', [App\Http\Controllers\HakaksesController::class, 'index'])->name('hakakses.index')->middleware('superadmin');
    Route::get('/hakakses/edit/{id}', [App\Http\Controllers\HakaksesController::class, 'edit'])->name('hakakses.edit')->middleware('superadmin');
    Route::put('/hakakses/update/{id}', [App\Http\Controllers\HakaksesController::class, 'update'])->name('hakakses.update')->middleware('superadmin');
    Route::delete('/hakakses/delete/{id}', [App\Http\Controllers\HakaksesController::class, 'destroy'])->name('hakakses.delete')->middleware('superadmin');

    Route::get('/table-example', [App\Http\Controllers\ExampleController::class, 'table'])->name('table.example');
    Route::get('/clock-example', [App\Http\Controllers\ExampleController::class, 'clock'])->name('clock.example');
    Route::get('/chart-example', [App\Http\Controllers\ExampleController::class, 'chart'])->name('chart.example');
    Route::get('/form-example', [App\Http\Controllers\ExampleController::class, 'form'])->name('form.example');
    Route::get('/map-example', [App\Http\Controllers\ExampleController::class, 'map'])->name('map.example');
    Route::get('/calendar-example', [App\Http\Controllers\ExampleController::class, 'calendar'])->name('calendar.example');
    Route::get('/gallery-example', [App\Http\Controllers\ExampleController::class, 'gallery'])->name('gallery.example');
    Route::get('/todo-example', [App\Http\Controllers\ExampleController::class, 'todo'])->name('todo.example');
    Route::get('/contact-example', [App\Http\Controllers\ExampleController::class, 'contact'])->name('contact.example');
    Route::get('/faq-example', [App\Http\Controllers\ExampleController::class, 'faq'])->name('faq.example');
    Route::get('/news-example', [App\Http\Controllers\ExampleController::class, 'news'])->name('news.example');
    Route::get('/about-example', [App\Http\Controllers\ExampleController::class, 'about'])->name('about.example');


});

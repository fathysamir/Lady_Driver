<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\CarMarkController;
use App\Http\Controllers\Dashboard\CarModelController;
use App\Http\Controllers\Dashboard\CarController;
use App\Http\Controllers\Dashboard\TripController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\ContactUsController;
use App\Http\Controllers\Dashboard\FeedBackController;
use App\Http\Controllers\Dashboard\ComplaintController;
use App\Http\Controllers\Dashboard\MotorcycleController;
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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/terms&conditions/{lang}', [AuthController::class, 'terms_conditions'])->name('terms_conditions');
Route::get('/privacy_policy/{lang}', [AuthController::class, 'privacy_policy'])->name('privacy_policy');
Route::get('/remove_account', [AuthController::class, 'remove_account'])->name('remove_account');
Route::get('/contact_us', [AuthController::class, 'contact_us'])->name('contact_us');
Route::post('/contact_us', [AuthController::class, 'save_contact_us'])->name('save_contact_us');
Route::get('/admin-dashboard/login', [AuthController::class, 'login_view'])->name('login.view');
Route::post('/admin-dashboard/login', [AuthController::class, 'login'])->name('login');
Route::get('/admin-dashboard', function () {

    if (!auth()->user()) {
        return redirect('/admin-dashboard/login');
    } else {
        return redirect('/admin-dashboard/home');
    }
});
Route::group(['middleware' => ['admin'], 'prefix' => 'admin-dashboard'], function () {
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/change_theme', [AuthController::class,'change_theme'])->name('change_theme');
    Route::any('/users', [UserController::class, 'index'])->name('users');
    // Route::get('/users/create', [UserController::class, 'create'])->name('add.user');
    // Route::post('/users/create', [UserController::class, 'store'])->name('create.user');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('edit.user');
    Route::post('/user/update/{id}', [UserController::class, 'update'])->name('update.user');
    Route::get('/user/delete/{id}', [UserController::class, 'delete'])->name('delete.user');

    Route::any('/archived-users', [UserController::class, 'index_archives'])->name('archived_users');
    /////////////////////////////////////////
    Route::any('/car-marks', [CarMarkController::class, 'index'])->name('car-marks');
    Route::get('/car-marks/create', [CarMarkController::class, 'create'])->name('add.car.mark');
    Route::post('/car-marks/create', [CarMarkController::class, 'store'])->name('create.car.mark');
    Route::get('/car-mark/edit/{id}', [CarMarkController::class, 'edit'])->name('edit.car.mark');
    Route::post('/car-mark/update/{id}', [CarMarkController::class, 'update'])->name('update.car.mark');
    Route::get('/car-mark/delete/{id}', [CarMarkController::class, 'delete'])->name('delete.car.mark');
    /////////////////////////////////////////
    Route::any('/motorcycles', [MotorcycleController::class, 'index'])->name('motorcycles');
    Route::get('/motorcycles/create', [MotorcycleController::class, 'create'])->name('add.motorcycle');
    Route::post('/motorcycles/create', [MotorcycleController::class, 'store'])->name('create.motorcycle');
    Route::get('/motorcycle/edit/{id}', [MotorcycleController::class, 'edit'])->name('edit.motorcycle');
    Route::post('/motorcycle/update/{id}', [MotorcycleController::class, 'update'])->name('update.motorcycle');
    Route::get('/motorcycle/delete/{id}', [MotorcycleController::class, 'delete'])->name('delete.motorcycle');

    /////////////////////////////////////////
    Route::any('/car-models', [CarModelController::class, 'index'])->name('car-models');
    Route::get('/car-models/create', [CarModelController::class, 'create'])->name('add.car.model');
    Route::post('/car-models/create', [CarModelController::class, 'store'])->name('create.car.model');
    Route::get('/car-model/edit/{id}', [CarModelController::class, 'edit'])->name('edit.car.model');
    Route::post('/car-model/update/{id}', [CarModelController::class, 'update'])->name('update.car.model');
    Route::get('/car-model/delete/{id}', [CarModelController::class, 'delete'])->name('delete.car.model');

    /////////////////////////////////////////
    Route::any('/cars', [CarController::class, 'index'])->name('cars');
    // Route::get('/users/create', [UserController::class, 'create'])->name('add.user');
    // Route::post('/users/create', [UserController::class, 'store'])->name('create.user');
    Route::get('/car/edit/{id}', [CarController::class, 'edit'])->name('edit.car');
    Route::post('/car/update/{id}', [CarController::class, 'update'])->name('update.car');
    Route::get('/car/delete/{id}', [CarController::class, 'delete'])->name('delete.car');
    Route::get('/getModels', [CarController::class, 'getModels'])->name('getModels');
    Route::get('/car-location/{id}', [CarController::class, 'getLocation']);
    //////////////////////////////////////////
    Route::any('/trips', [TripController::class, 'index'])->name('trips');
    Route::get('/trip/view/{id}', [TripController::class, 'view'])->name('view.trip');
    //////////////////////////////////////////
    Route::any('/settings', [SettingController::class, 'index'])->name('settings');
    Route::get('/setting/edit/{id}', [SettingController::class, 'edit'])->name('edit.setting');
    Route::post('/setting/update/{id}', [SettingController::class, 'update'])->name('update.setting');
    ////////////////////////////////////////
    Route::any('/reasons-cancelling-trips', [SettingController::class, 'reasons_cancelling_trips'])->name('reasons-cancelling-trips');
    Route::get('/reasons-cancelling-trips/create', [SettingController::class, 'create_reason'])->name('add.reason');
    Route::post('/reasons-cancelling-trips/create', [SettingController::class, 'store_reason'])->name('create.reason');
    Route::get('/reason-cancelling-trip/edit/{id}', [SettingController::class, 'edit_reason'])->name('edit.reason');
    Route::post('/reason-cancelling-trip/update/{id}', [SettingController::class, 'update_reason'])->name('update.reason');
    Route::get('/reason-cancelling-trip/delete/{id}', [SettingController::class, 'delete_reason'])->name('delete.reason');
    ///////////////////////////////////////////////
    Route::any('/contact_us', [ContactUsController::class, 'index'])->name('contact_us');
    Route::get('/contact_us/view/{id}', [ContactUsController::class, 'view'])->name('edit.contact_us');
    Route::post('/contact_us/update/{id}', [ContactUsController::class, 'update'])->name('update.contact_us');

    Route::get('/about_us/view', [SettingController::class, 'about_us'])->name('edit.about_us');
    Route::post('/about_us/update', [SettingController::class, 'update_about_us'])->name('update.about_us');
    Route::any('/feed_back', [FeedBackController::class, 'index'])->name('feed_back');
    Route::get('/feed_back/view/{id}', [FeedBackController::class, 'view'])->name('edit.feed_back');
    ///////////////////////////////////////////////
    Route::any('/complaints', [ComplaintController::class, 'index'])->name('complaints');
    Route::get('/complaint/view/{id}', [ComplaintController::class, 'view'])->name('view.complaint');
});

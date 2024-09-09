<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\ClientController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register',[AuthController::class,'register'])->name('register');
Route::post('/login',[AuthController::class,'login'])->name('login');
Route::post('/verifyOTP',[AuthController::class,'verifyOTP'])->name('verifyOTP');
Route::post('/resend_otp',[AuthController::class,'resend_otp'])->name('resend_otp');
Route::post('/reset_password',[AuthController::class,'reset_password'])->name('reset_password');
Route::middleware('auth:sanctum')->group( function () {
    Route::post('/logout',[AuthController::class,'logout'])->name('logout');
    Route::post('/activation',[DriverController::class,'activation'])->name('activation');
    Route::post('/create_Car',[DriverController::class,'create_car'])->name('create_car');
    Route::post('/edit_car',[DriverController::class,'edit_car'])->name('edit_car');
    Route::get('/car',[DriverController::class,'car'])->name('car');
    Route::post('/add_driving_license',[DriverController::class,'add_driving_license'])->name('add_driving_license');
    Route::get('/driving_license',[DriverController::class,'driving_license'])->name('driving_license');
    Route::get('/profile/{id}',[AuthController::class,'profile'])->name('profile');
    Route::post('/edit_personal_info',[AuthController::class,'edit_personal_info'])->name('edit_personal_info');
    Route::post('/create_trip',[ClientController::class,'create_trip'])->name('create_trip');
    Route::get('/expire_trip/{id}',[ClientController::class,'expire_trip'])->name('expire_trip');

    Route::get('/created_trips',[DriverController::class,'created_trips'])->name('created_trips');
    Route::get('/expire_offer/{id}',[DriverController::class,'expire_offer'])->name('expire_offer');
    Route::post('/create_offer',[DriverController::class,'create_offer'])->name('create_offer');
    Route::get('/marks',[DriverController::class,'marks'])->name('marks');
    Route::get('/models',[DriverController::class,'models'])->name('models');

});



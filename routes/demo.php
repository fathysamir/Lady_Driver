<?php

use App\Http\Controllers\Demo\AuthController;
// use App\Http\Controllers\API\ClientController;
// use App\Http\Controllers\API\DriverController;
// use App\Http\Controllers\API\LiveLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/verifyOTP', [AuthController::class, 'verifyOTP'])->name('verifyOTP');

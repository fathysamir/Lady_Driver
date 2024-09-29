<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\CarMarkController;
use App\Http\Controllers\Dashboard\CarModelController;
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
Route::get('/admin-dashboard/login', [AuthController::class, 'login_view'])->name('login.view');
Route::post('/admin-dashboard/login', [AuthController::class, 'login'])->name('login');
Route::get('/admin-dashboard', function () {
    
    if(!auth()->user()){
        return redirect('/admin-dashboard/login');
    }else{
        return redirect('/admin-dashboard/home');
    }
});
Route::group(['middleware' => ['admin'], 'prefix' => 'admin-dashboard'], function () {
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/change_theme',[AuthController::class,'change_theme'])->name('change_theme');
        Route::any('/users', [UserController::class, 'index'])->name('users'); 
        // Route::get('/users/create', [UserController::class, 'create'])->name('add.user');
        // Route::post('/users/create', [UserController::class, 'store'])->name('create.user');
        Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('edit.user');
        Route::post('/user/update/{id}', [UserController::class, 'update'])->name('update.user');
        Route::get('/user/delete/{id}', [UserController::class, 'delete'])->name('delete.user');
    /////////////////////////////////////////
        Route::any('/car-marks', [CarMarkController::class, 'index'])->name('car-marks'); 
        Route::get('/car-marks/create', [CarMarkController::class, 'create'])->name('add.car.mark');
        Route::post('/car-marks/create', [CarMarkController::class, 'store'])->name('create.car.mark');
        Route::get('/car-mark/edit/{id}', [CarMarkController::class, 'edit'])->name('edit.car.mark');
        Route::post('/car-mark/update/{id}', [CarMarkController::class, 'update'])->name('update.car.mark');
        Route::get('/car-mark/delete/{id}', [CarMarkController::class, 'delete'])->name('delete.car.mark');
        
    /////////////////////////////////////////
        Route::any('/car-models', [CarModelController::class, 'index'])->name('car-models'); 
        Route::get('/car-models/create', [CarModelController::class, 'create'])->name('add.car.model');
        Route::post('/car-models/create', [CarModelController::class, 'store'])->name('create.car.model');
        Route::get('/car-model/edit/{id}', [CarModelController::class, 'edit'])->name('edit.car.model');
        Route::post('/car-model/update/{id}', [CarModelController::class, 'update'])->name('update.car.model');
        Route::get('/car-model/delete/{id}', [CarModelController::class, 'delete'])->name('delete.car.model');
        
    /////////////////////////////////////////
});
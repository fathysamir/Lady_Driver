<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\LiveLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('/marks', [DriverController::class, 'marks'])->name('marks');
Route::get('/models', [DriverController::class, 'models'])->name('models');
Route::get('/scooter_marks', [DriverController::class, 'scooter_marks'])->name('scooter_marks');
Route::get('/scooter_models', [DriverController::class, 'scooter_models'])->name('scooter_models');

Route::post('/save_image', [AuthController::class, 'save_image'])->name('save_image');
Route::get('/privacy-policy', [AuthController::class, 'getPrivacyPolicy'])->name('getPrivacyPolicy');
Route::get('/terms-conditions', [AuthController::class, 'getTermsAndConditions'])->name('terms_conditions');
Route::get('/cities', [AuthController::class, 'cities'])->name('cities');
Route::get('/app_version', [AuthController::class, 'app_version'])->name('app_version');
Route::post('/update_app_version', [AuthController::class, 'update_app_version'])->name('update_app_version');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/driver_register', [AuthController::class, 'driver_register'])->name('driver_register');
Route::post('/client_register', [AuthController::class, 'client_register'])->name('client_register');
Route::post('/register2', [AuthController::class, 'register2'])->name('register2');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/contact_us', [AuthController::class, 'save_contact_us'])->name('save_contact_us');
Route::post('/careers', [AuthController::class, 'careers'])->name('career.apply');
Route::get('/about_us', [AuthController::class, 'about_us'])->name('about_us');
Route::post('/verifyOTP', [AuthController::class, 'verifyOTP'])->name('verifyOTP');
Route::post('/resend_otp', [AuthController::class, 'resend_otp'])->name('resend_otp');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetpassword'])->name('reset-password');
Route::get('/live-location/data/{token}', [LiveLocationController::class, 'getLocation']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/device_tocken', [AuthController::class, 'device_tocken'])->name('device_tocken');
    Route::get('/user_notification', [AuthController::class, 'user_notification'])->name('user_notification');
    Route::post('/seen_notification', [AuthController::class, 'seen_notification'])->name('seen_notification');
    Route::post('/update_password', [AuthController::class, 'update_password'])->name('update_password');
    Route::get('/FAQs', [AuthController::class, 'FAQs'])->name('FAQs');
    Route::get('/get_dashboard_messages', [AuthController::class, 'get_dashboard_messages'])->name('get_dashboard_messages');
    Route::post('/change_lang', [AuthController::class, 'change_lang'])->name('change_lang');
    Route::post('/save_student_data', [AuthController::class, 'save_student_data'])->name('save_student_data');
    Route::post('/check_barcode', [ClientController::class, 'check_barcode'])->name('check_barcode');
    Route::post('/pay250Pound', [AuthController::class, 'pay250Pound'])->name('pay250Pound');

    Route::get('/change_student_discount_service', [AuthController::class, 'change_student_discount_service'])->name('change_student_discount_service');

    Route::post('/activation', [DriverController::class, 'activation'])->name('activation');
    Route::post('/create_Car', [DriverController::class, 'create_car'])->name('create_car');
    Route::post('/edit_car', [DriverController::class, 'edit_car'])->name('edit_car');
    Route::get('/car', [DriverController::class, 'car'])->name('car');
    Route::post('/create_scooter', [DriverController::class, 'create_scooter'])->name('create_scooter');
    Route::post('/edit_scooter', [DriverController::class, 'edit_scooter'])->name('edit_scooter');
    Route::get('/scooter', [DriverController::class, 'scooter'])->name('scooter');
    Route::post('/add_driving_license', [DriverController::class, 'add_driving_license'])->name('add_driving_license');
    Route::post('/add_car_inspection', [DriverController::class, 'add_car_inspection'])->name('add_car_inspection');

    Route::get('/driving_license', [DriverController::class, 'driving_license'])->name('driving_license');
    Route::get('/profile/{id}', [AuthController::class, 'profile'])->name('profile');
    Route::post('/edit_personal_info', [AuthController::class, 'edit_personal_info'])->name('edit_personal_info');
    //Route::post('/create_trip', [ClientController::class, 'create_trip'])->name('create_trip');
    Route::post('/create_temporary_trip', [ClientController::class, 'create_temporary_trip'])->name('create_temporary_trip');
    Route::get('/expire_trip/{id}', [ClientController::class, 'expire_trip'])->name('expire_trip');
    Route::post('/remove_account', [AuthController::class, 'remove_account'])->name('remove_account');

    Route::get('/created_trips', [DriverController::class, 'created_trips'])->name('created_trips');

    Route::get('/expire_offer/{id}', [DriverController::class, 'expire_offer'])->name('expire_offer');
    Route::post('/create_offer', [DriverController::class, 'create_offer'])->name('create_offer');
    Route::post('/driver_arriving', [DriverController::class, 'driver_arriving'])->name('driver_arriving');

    Route::get('/current_trip', [ClientController::class, 'current_trip'])->name('current_trip');
    Route::post('/accept_offer', [ClientController::class, 'accept_offer'])->name('accept_offer');
    Route::get('/driver_current_trip', [DriverController::class, 'driver_current_trip'])->name('driver_current_trip');
    Route::post('/start_trip', [DriverController::class, 'start_trip'])->name('start_trip');
    Route::post('/pay_trip', [ClientController::class, 'pay_trip'])->name('pay_trip');
    Route::get('/completed_trips', [ClientController::class, 'completed_trips'])->name('completed_trips');
    Route::get('/cancelled_trips', [ClientController::class, 'cancelled_trips'])->name('cancelled_trips');
    Route::get('/driver_completed_trips', [DriverController::class, 'driver_completed_trips'])->name('driver_completed_trips');
    Route::get('/driver_cancelled_trips', [DriverController::class, 'driver_cancelled_trips'])->name('driver_cancelled_trips');
    Route::post('/update_location_car', [DriverController::class, 'update_location_car'])->name('update_location_car');
    Route::post('/rate_trip', [ClientController::class, 'rate_trip'])->name('rate_trip');
    Route::post('/cancel_trip', [ClientController::class, 'cancel_trip'])->name('cancel_trip');
    Route::post('/update_trip_price', [ClientController::class, 'update_trip_price'])->name('update_trip_price');
    Route::get('/cancellation_reasons', [ClientController::class, 'cancellation_reasons'])->name('cancellation_reasons');
    Route::post('/add_feed_back', [AuthController::class, 'add_feed_back'])->name('add_feed_back');

    Route::post('/trip-chats/send', [ClientController::class, 'sendMessage']);
    Route::get('/trip-chats/{id}', [ClientController::class, 'getTripMessages']);
    Route::get('/trip-chats/message/{id}', [ClientController::class, 'getMessage']);
    Route::post('/user/update-location', [ClientController::class, 'updateUserLocation']);

    Route::post('/live-location/create', [LiveLocationController::class, 'create']);
    Route::post('/live-location/update', [LiveLocationController::class, 'update']);
    Route::get('/get_near_drivers', [ClientController::class, 'get_near_drivers']);
    Route::post('/add-address', [ClientController::class, 'add_address']);
    Route::get('/get_all_user_addresses', [ClientController::class, 'get_all_user_addresses']);
    Route::get('/delete_address', [ClientController::class, 'delete_address']);



});

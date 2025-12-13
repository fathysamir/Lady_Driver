<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Mail\ForgotPasswordMail;
use App\Mail\SendOTP;
use App\Models\AboutUs;
use App\Models\Careers;
use App\Models\Car;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\DashboardMessage;
use App\Models\DriverLicense;
use App\Models\FAQ;
use App\Models\FawryTransaction;
use App\Models\FeedBack;
use App\Models\Notification;
use App\Models\PrivacyAndTerm;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Trip;
use App\Models\User;
use App\Services\FawryService;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

//  "DA:D5:25:D7:5C:32:1D:61:A4:2F:72:E4:E4:63:BF:7F:C9:9D:29:57:BB:8E:83:B8:51:62:9E:A2:31:B8:81:C5"

class AuthController extends ApiController
{
    protected $firebaseService;
    protected $fawry;
    public function __construct(FirebaseService $firebaseService, FawryService $fawry)
    {
        $this->firebaseService = $firebaseService;
        $this->fawry           = $fawry;
    }

    public function register2(Request $request)
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
                    ->whereNull('deleted_at'),
            ],
            'password'     => 'required|string|min:8|confirmed',
            'mode'         => 'required|in:driver,client',
            'country_code' => 'required|string|max:10',
            'phone'        => [
                'required',
                Rule::unique('users')
                    ->where(function ($query) use ($request) {
                        return $query->where('country_code', $request->country_code)
                            ->whereNull('deleted_at');
                    }),
            ],
            'city_id'      => 'required|exists:cities,id',

        ];

        if ($request->input('mode') === 'driver') {

            $rules['driver_type'] = 'required|in:scooter,car';
            $rules['year']        = 'required|integer|min:2000|max:' . date('Y');

            $rules['national_ID'] = 'required|digits:14';
        }

        if ($request->input('mode') === 'client') {

            $rules['gendor'] = 'required|in:Male,Female';

        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        Log::info('Incoming Request from Flutter:', $request->all());
        $otpCode = generateOTP();
        do {
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        } while (User::where('invitation_code', $invitation_code)->exists());
        if ($request->birth_date) {
            $age = Carbon::parse($request->birth_date)->age;
        } else {
            $age = null;
        }
        if ($request->input('mode') === 'driver') {
            if ($request->driver_type == 'car') {
                $comfort_year = Setting::where('key', 'comfort_car_start_from_year')->where('category', 'General')->where('type', 'number')->first()->value;
                if (intval($request->year) >= intval($comfort_year)) {
                    $driver_type = 'comfort_car';
                } else {
                    $driver_type = 'car';
                }
            } else {
                $driver_type = 'scooter';
            }

        } else {
            $driver_type = null;
        }

        $user = User::create([

            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'country_code'    => $request->country_code,
            'mode'            => $request->mode,
            'OTP'             => $otpCode,
            'invitation_code' => $invitation_code,
            'gendor'          => $request->gendor,
            'birth_date'      => $request->birth_date,
            'age'             => $age,
            'city_id'         => $request->city_id,
            'national_id'     => $request->national_ID,
            'driver_type'     => $driver_type,
        ]);
        if ($request->mode == 'driver') {
            $user->level = '1';

        }
        $user->save();
        $role = Role::where('name', 'Client')->first();

        $user->assignRole([$role->id]);
        // Generate OTP

        // Send OTP via Email (or SMS)
        Mail::to($request->email)->send(new SendOTP($otpCode, $request->name));

        return $this->sendResponse(null, 'OTP sent to your email address.', 200);

    }

    public function driver_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                        => 'required|string|max:255',
            'email'                       => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
                    ->whereNull('deleted_at')->where('is_verified', '1'),
            ],
            'password'                    => 'required|string|min:8|confirmed',
            'country_code'                => 'required|string|max:10',
            'phone'                       => [
                'required',
                Rule::unique('users')
                    ->where(function ($query) use ($request) {
                        return $query->where('country_code', $request->country_code)
                            ->whereNull('deleted_at')->where('is_verified', '1');
                    }),
            ],
            'image'                       => 'required|string',
            'birth_date'                  => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'city_id'                     => ['required', Rule::exists('cities', 'id')->whereNull('deleted_at')],

            'national_ID'                 => 'nullable|digits:14|required_without:passport_ID',
            'ID_front_image'              => 'required_with:national_ID|string',
            'ID_back_image'               => 'required_with:national_ID|string',

            'passport_ID'                 => 'nullable|required_without:national_ID',
            'passport_image'              => 'required_with:passport_ID|string',

            'driving_license_number'      => 'required|string|max:50',
            'license_expire_date'         => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'license_front_image'         => 'required|string',
            'license_back_image'          => 'required|string',

            'vehicle_type'                => ['required', Rule::in(['car', 'scooter'])],
            'car_mark_id'                 => [
                'required_if:vehicle_type,car',
                'nullable',
                Rule::exists('car_marks', 'id'),
            ],
            'car_model_id'                => [
                'required_if:vehicle_type,car',
                'nullable',
                Rule::exists('car_models', 'id'),
            ],
            'scooter_mark_id'             => [
                'required_if:vehicle_type,scooter',
                'nullable',
                Rule::exists('motorcycle_marks', 'id'),
            ],
            'scooter_model_id'            => [
                'required_if:vehicle_type,scooter',
                'nullable',
                Rule::exists('motorcycle_models', 'id'),
            ],
            'air_conditioned'             => 'nullable|boolean',
            'allow_pets'                  => 'nullable|boolean',
            'color'                       => 'required|string|max:255',
            'year'                        => 'required|integer|min:1990|max:' . date('Y'),
            'plate_num'                   => 'required|string|max:255',
            'vehicle_image'               => 'required|string',
            'plate_image'                 => 'required|string',
            'vehicle_license_expire_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'vehicle_license_front_image' => 'required|string',
            'vehicle_license_back_image'  => 'required|string',
            'registration_id'             => 'required',
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        Log::info('Incoming Request from Flutter:', $request->all());
        $otpCode = generateOTP();
        do {
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        } while (User::where('invitation_code', $invitation_code)->exists());
        if ($request->birth_date) {
            $age = Carbon::parse($request->birth_date)->age;
        } else {
            $age = null;
        }

        if ($request->vehicle_type == 'car') {
            $comfort_year = Setting::where('key', 'comfort_car_start_from_year')->where('category', 'General')->where('type', 'number')->first()->value;
            if (intval($request->year) >= intval($comfort_year)) {
                $driver_type = 'comfort_car';
                $is_comfort  = '1';
            } else {
                $driver_type = 'car';
                $is_comfort  = '0';
            }
        } else {
            $driver_type = 'scooter';
        }

        $username = username_Generation($request->name);

        $user = User::create([
            'name'            => $request->name,
            'username' => $username,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'country_code'    => $request->country_code,
            'mode'            => 'driver',
            'OTP'             => $otpCode,
            'invitation_code' => $invitation_code,
            'gendor'          => 'Female',
            'birth_date'      => $request->birth_date,
            'age'             => $age,
            'city_id'         => $request->city_id,
            'national_id'     => $request->national_ID,
            'passport_id'     => $request->passport_ID,
            'driver_type'     => $driver_type,
            'level'           => '1',
        ]);
        $role = Role::where('name', 'Client')->first();

        $user->assignRole([$role->id]);
        if ($request->file('image')) {
            //uploadMedia($request->image, $user->avatarCollection, $user);
            uploadMediaByURL($request->image, $user->avatarCollection, $user);
        }
        if ($request->file('ID_front_image')) {
            //uploadMedia($request->ID_front_image, $user->IDfrontImageCollection, $user);
            uploadMediaByURL($request->ID_front_image, $user->IDfrontImageCollection, $user);

        }
        if ($request->file('ID_back_image')) {
            // uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
            uploadMediaByURL($request->ID_back_image, $user->IDbackImageCollection, $user);

        }
        if ($request->file('passport_image')) {
            // uploadMedia($request->passport_image, $user->passportImageCollection, $user);
            uploadMediaByURL($request->passport_image, $user->passportImageCollection, $user);

        }

        $license = DriverLicense::create(['user_id' => $user->id,
            'license_num'                               => $request->driving_license_number,
            'expire_date'                               => $request->license_expire_date]);

        // uploadMedia($request->license_front_image, $license->LicenseFrontImageCollection, $license);
        uploadMediaByURL($request->license_front_image, $license->LicenseFrontImageCollection, $license);

        // uploadMedia($request->license_back_image, $license->LicenseBackImageCollection, $license);
        uploadMediaByURL($request->license_back_image, $license->LicenseBackImageCollection, $license);

        if ($request->vehicle_type == 'car') {
            $lastCar = Car::orderBy('id', 'desc')->first();

            if ($lastCar) {
                $lastCode = $lastCar->code;
                $code     = 'CAR-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
            } else {
                $code = 'CAR-000000001';
            }
            $car = Car::create(['user_id' => $user->id,
                'car_mark_id'                 => $request->car_mark_id,
                'code'                        => $code,
                'car_model_id'                => $request->car_model_id,
                'color'                       => $request->color,
                'year'                        => $request->year,
                'car_plate'                   => $request->plate_num,
                'passenger_type'              => 'female',
                'license_expire_date'         => $request->vehicle_license_expire_date,
                'is_comfort'                  => $is_comfort,
            ]);
            if ($request->air_conditioned) {
                $car->air_conditioned = '1';
            } else {
                $car->air_conditioned = '0';
            }

            if ($request->allow_pets) {
                $car->animals = '1';
            } else {
                $car->animals = '0';
            }
            $car->save();
            uploadMediaByURL($request->vehicle_image, $car->avatarCollection, $car);
            uploadMediaByURL($request->plate_image, $car->PlateImageCollection, $car);
            uploadMediaByURL($request->vehicle_license_front_image, $car->LicenseFrontImageCollection, $car);
            uploadMediaByURL($request->vehicle_license_front_image, $car->LicenseBackImageCollection, $car);
        } elseif ($request->vehicle_type == 'scooter') {
            $lastScooter = Scooter::orderBy('id', 'desc')->first();

            if ($lastScooter) {
                $lastCode = $lastScooter->code;
                $code     = 'SCO-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
            } else {
                $code = 'SCT-000000001';
            }
            $scooter = Scooter::create(['user_id' => auth()->user()->id,
                'motorcycle_mark_id'                  => $request->scooter_mark_id,
                'code'                                => $code,
                'motorcycle_model_id'                 => $request->scooter_model_id,
                'color'                               => $request->color,
                'year'                                => $request->year,
                'scooter_plate'                       => $request->plate_num,
                'license_expire_date'                 => $request->vehicle_license_expire_date,
            ]);
            uploadMediaByURL($request->vehicle_image, $scooter->avatarCollection, $scooter);
            uploadMediaByURL($request->plate_image, $scooter->PlateImageCollection, $scooter);
            uploadMediaByURL($request->vehicle_license_front_image, $scooter->LicenseFrontImageCollection, $scooter);
            uploadMediaByURL($request->vehicle_license_front_image, $scooter->LicenseBackImageCollection, $scooter);
        }
        Mail::to($request->email)->send(new SendOTP($otpCode, $request->name));
        $used_paths = [];

        if (isset($request->image)) {
            $used_paths[] = $request->image;
        }

        if (isset($request->ID_front_image)) {
            $used_paths[] = $request->ID_front_image;
        }

        if (isset($request->ID_back_image)) {
            $used_paths[] = $request->ID_back_image;
        }

        if (isset($request->passport_image)) {
            $used_paths[] = $request->passport_image;
        }

        if (isset($request->license_front_image)) {
            $used_paths[] = $request->license_front_image;
        }

        if (isset($request->license_back_image)) {
            $used_paths[] = $request->license_back_image;
        }

        if (isset($request->vehicle_image)) {
            $used_paths[] = $request->vehicle_image;
        }

        if (isset($request->plate_image)) {
            $used_paths[] = $request->plate_image;
        }

        if (isset($request->vehicle_license_front_image)) {
            $used_paths[] = $request->vehicle_license_front_image;
        }

        if (isset($request->vehicle_license_back_image)) {
            $used_paths[] = $request->vehicle_license_back_image;
        }

        if ($request->registration_id) {
            deleteUnusedRegistrationImages($request->registration_id, $used_paths);
        }
        $res['otp'] = $otpCode;
        $res['username'] = $user->username;
        return $this->sendResponse($res, 'OTP sent to your email address.', 200);

    }

    public function save_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        $response = uploadImage($request->image, $request->registration_id);
        return $this->sendResponse($response, 'Image Uploaded successfully.', 200);

    }
    public function client_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
                    ->whereNull('deleted_at')->where('is_verified', '1'),
            ],
            'password'     => 'required|string|min:8|confirmed',
            'country_code' => 'required|string|max:10',
            'phone'        => [
                'required',
                Rule::unique('users')
                    ->where(function ($query) use ($request) {
                        return $query->where('country_code', $request->country_code)
                            ->whereNull('deleted_at')->where('is_verified', '1');
                    }),
            ],
            'image'        => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'birth_date'   => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        Log::info('Incoming Request from Flutter:', $request->all());
        $otpCode = generateOTP();
        do {
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        } while (User::where('invitation_code', $invitation_code)->exists());
        if ($request->birth_date) {
            $age = Carbon::parse($request->birth_date)->age;
        } else {
            $age = null;
        }
        $username = username_Generation($request->name);

        $user = User::create([

            'name'            => $request->name,
            'username'        => $username,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'country_code'    => $request->country_code,
            'mode'            => 'client',
            'OTP'             => $otpCode,
            'invitation_code' => $invitation_code,
            'gendor'          => 'Female',
            'birth_date'      => $request->birth_date,
            'age'             => $age,
            'driver_type'     => null,
        ]);
        $role = Role::where('name', 'Client')->first();

        $user->assignRole([$role->id]);
        // Generate OTP

        if ($request->file('image')) {
            uploadMedia($request->image, $user->avatarCollection, $user);
        }
        Mail::to($request->email)->send(new SendOTP($otpCode, $request->name));
        $res['otp'] = $otpCode;
        $res['username'] = $user->username;
        return $this->sendResponse($res, 'OTP sent to your email address.', 200);
    }
    public function register(Request $request)
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
                    ->whereNull('deleted_at')->where('is_verified', '1'),
            ],
            'password'     => 'required|string|min:8|confirmed',
            'mode'         => 'required|in:driver,client',
            'country_code' => 'required|string|max:10',
            'phone'        => [
                'required',
                Rule::unique('users')
                    ->where(function ($query) use ($request) {
                        return $query->where('country_code', $request->country_code)
                            ->whereNull('deleted_at')->where('is_verified', '1');
                    }),
            ],
            'city_id'      => 'required|exists:cities,id',

        ];

        if ($request->input('mode') === 'driver') {
            $rules['image']      = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['birth_date'] = [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ];
            $rules['driver_type']    = 'required|in:scooter,car';
            $rules['year']           = 'required|integer|min:2000|max:' . date('Y');
            $rules['ID_front_image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['ID_back_image']  = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['national_ID']    = 'required|digits:14';
        }

        if ($request->input('mode') === 'client') {
            $rules['ID_front_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['passport']       = 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['gendor']         = 'required|in:Male,Female';
            $rules['birth_date']     = [
                'nullable',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        Log::info('Incoming Request from Flutter:', $request->all());
        $otpCode = generateOTP();
        do {
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        } while (User::where('invitation_code', $invitation_code)->exists());
        if ($request->birth_date) {
            $age = Carbon::parse($request->birth_date)->age;
        } else {
            $age = null;
        }
        if ($request->input('mode') === 'driver') {
            if ($request->driver_type == 'car') {
                $comfort_year = Setting::where('key', 'comfort_car_start_from_year')->where('category', 'General')->where('type', 'number')->first()->value;
                if (intval($request->year) >= intval($comfort_year)) {
                    $driver_type = 'comfort_car';
                } else {
                    $driver_type = 'car';
                }
            } else {
                $driver_type = 'scooter';
            }

        } else {
            $driver_type = null;
        }

        $user = User::create([

            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'country_code'    => $request->country_code,
            'mode'            => $request->mode,
            'OTP'             => $otpCode,
            'invitation_code' => $invitation_code,
            'gendor'          => $request->gendor,
            'birth_date'      => $request->birth_date,
            'age'             => $age,
            'city_id'         => $request->city_id,
            'national_id'     => $request->national_ID,
            'driver_type'     => $driver_type,
        ]);
        if ($request->mode == 'driver') {
            $user->level = '1';

        }
        $user->save();
        $role = Role::where('name', 'Client')->first();

        $user->assignRole([$role->id]);
        // Generate OTP

        if ($request->file('image')) {
            uploadMedia($request->image, $user->avatarCollection, $user);
        }
        if ($request->file('ID_front_image')) {
            uploadMedia($request->ID_front_image, $user->IDfrontImageCollection, $user);
        }
        if ($request->file('ID_back_image')) {
            uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
        }
        if ($request->file('passport')) {
            uploadMedia($request->passport, $user->passportImageCollection, $user);
        }

        // Send OTP via Email (or SMS)
        Mail::to($request->email)->send(new SendOTP($otpCode, $request->name));

        return $this->sendResponse(null, 'OTP sent to your email address.', 200);

    }

    public function pay250Pound(Request $request)
    {
        $user = auth()->user();
        if ($user->mode !== 'driver') {
            return $this->sendError(null, 'Only drivers can perform this action.', 403);
        }

        // تحقق إن الرصيد أقل من 250
        if ($user->wallet >= 250) {
            return $this->sendError(null, 'Your wallet already has the required amount.', 403);

        }

        $v = Validator::make($request->all(), [
            'paymentMethod'   => 'required|string|in:PayAtFawry,PayUsingCC,MWALLET',
            'amount'          => 'required|numeric|min:0.01',
            'customerMobile'  => 'required|string',
            'customerEmail'   => 'required|email',

            'customerName'    => 'nullable|string',
            'description'     => 'nullable|string',

            // Card
            'cardNumber'      => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cardExpiryYear'  => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cardExpiryMonth' => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cvv'             => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'returnUrl'       => 'required_if:paymentMethod,PayUsingCC|nullable|url',

            'walletMobile'    => 'required_if:paymentMethod,MWALLET|nullable|string',
            //'walletProviderService' => 'required_if:paymentMethod,MWALLET|nullable|string',
        ]);

        // if ($v->fails()) {
        //     return response()->json(['error' => $v->errors()->all()], 422);
        // }
        if ($v->fails()) {

            $errors = implode(" / ", $v->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $amount = $request->amount;
        // استدعاء createPayment من ApiController
        $merchantRefNum = 'md-' . Str::random(10) . '-' . time();
        $amount         = number_format((float) $amount, 2, '.', '');

        $method = $request->paymentMethod;

        // ====== Build signature depending on method ======
        switch ($method) {
            case 'PayAtFawry':
                $sig = $this->fawry->makeReferenceSignature(
                    $merchantRefNum,
                    auth()->user()->id,
                    $method,
                    $amount
                );
                break;

            case 'PayUsingCC':
                $sig = $this->fawry->make3DSCardSignature(
                    $merchantRefNum,
                    auth()->user()->id,
                    $method,
                    $amount,
                    $request->cardNumber,
                    $request->cardExpiryYear,
                    $request->cardExpiryMonth,
                    $request->cvv,
                    $request->returnUrl
                );
                break;

            case 'MWALLET':
                $sig = $this->fawry->makeWalletSignature(
                    $merchantRefNum,
                    strval(auth()->user()->id),
                    $method,
                    $amount,
                    $request->walletMobile
                );
                break;

            default:
                return $this->sendError(null, 'Unsupported payment method', 400);
        }

        $paymentExpiry = (time() + (30 * 60)) * 1000;
        // ====== Build payload ======
        if ($method === 'PayAtFawry') {
            $payload = [
                'merchantCode'      => config('services.fawry.merchant_code'),
                'merchantRefNum'    => $merchantRefNum,
                'customerMobile'    => $request->customerMobile,
                'customerEmail'     => $request->customerEmail,
                'customerName'      => $request->customerName ?? '',
                'customerProfileId' => strval(auth()->user()->id),
                'amount'            => $amount,
                'paymentExpiry'     => $paymentExpiry,
                'currencyCode'      => 'EGP',
                'language'          => 'en-gb',
                'chargeItems'       => [
                    [
                        'itemId'      => '33563hbdyug53468465',
                        'description' => 'Driver wallet activation deposit',
                        'price'       => $amount,
                        'quantity'    => "1",
                    ],
                ],
                'signature'         => $sig,
                'paymentMethod'     => $method,
                'description'       => $request->description ?? 'Payment',
                'orderWebHookUrl'   => route('api.fawry.webhook'),
            ];
        }

        // extra fields for Card
        if ($method === 'PayUsingCC') {
            $payload = [
                'merchantCode'      => config('services.fawry.merchant_code'),
                'merchantRefNum'    => $merchantRefNum,
                'customerMobile'    => $request->customerMobile,
                'customerEmail'     => $request->customerEmail,
                'customerName'      => $request->customerName ?? '',
                'customerProfileId' => strval(auth()->user()->id),
                'amount'            => $amount,
                'paymentExpiry'     => $paymentExpiry,
                'currencyCode'      => 'EGP',
                'language'          => 'en-gb',
                'chargeItems'       => [
                    [
                        'itemId'      => '33563hbdyug53468465',
                        'description' => 'Driver wallet activation deposit',
                        'price'       => $amount,
                        'quantity'    => "1",
                    ],
                ],
                'signature'         => $sig,
                'paymentMethod'     => $method,
                'description'       => $request->description ?? 'Payment',
                'orderWebHookUrl'   => route('api.fawry.webhook'),

                'cardNumber'        => $request->cardNumber,
                'cardExpiryYear'    => $request->cardExpiryYear,
                'cardExpiryMonth'   => $request->cardExpiryMonth,
                'cvv'               => $request->cvv,
                'returnUrl'         => $request->returnUrl,
                'enable3DS'         => true,
            ];
        }

        // extra fields for Wallet
        if ($method === 'MWALLET') {
            $payload = [
                'merchantCode'        => config('services.fawry.merchant_code'),
                'merchantRefNum'      => $merchantRefNum,
                'customerName'        => $request->customerName ?? '',
                'customerMobile'      => $request->customerMobile,
                'customerEmail'       => $request->customerEmail,

                'customerProfileId'   => strval(auth()->user()->id),
                'amount'              => $amount,
                'paymentExpiry'       => $paymentExpiry,
                'currencyCode'        => 'EGP',
                'language'            => 'en-gb',
                'chargeItems'         => [
                    [
                        'itemId'      => '33563hbdyug53468465',
                        'description' => 'Driver wallet activation deposit',
                        'price'       => $amount,
                        'quantity'    => 1,
                    ],
                ],
                'debitMobileWalletNo' => $request->walletMobile,
                'signature'           => $sig,
                'paymentMethod'       => $method,
                'description'         => $request->description ?? 'Payment',

                'orderWebHookUrl'     => route('api.fawry.webhook'),

                // 'walletMobile'          => $request->walletMobile,
                // 'walletProviderService' => $request->walletProviderService,
                // 'returnUrl'             => $request->returnUrl,
            ];
        }

        // ====== Store local transaction ======
        $trx = FawryTransaction::create([
            'user_id'        => auth()->id() ?? null,
            'merchant_ref'   => $merchantRefNum,
            'amount'         => $amount,
            'payment_method' => $method,
            'status'         => 'PENDING',
        ]);

        // call correct method
        if ($method === 'PayAtFawry') {
            $resp = $this->fawry->createReferenceCharge($payload);
        } elseif ($method === 'PayUsingCC') {
            $resp = $this->fawry->create3DSCardCharge($payload);
        } else {

            $resp = $this->fawry->createWalletCharge($payload);
        }

        $trx->reference_number = $resp['referenceNumber'] ?? null;
        $trx->response         = $resp;
        $trx->status           = $resp['orderStatus'] ?? $resp['statusDescription'] ?? $trx->status;
        $trx->save();

        return $this->sendResponse($resp, 'Success Payment', 200);

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|string',
            'password'     => 'required|string|min:8',
            'device_token' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $login     = $request->email;
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user      = User::where($fieldType, $login)->first();

        // ===== Security Config =====
        $maxAttempts = config('security.max_attempts', 5);
        $lockMinutes = config('security.lock_minutes', 15);
        $logChannel  = config('security.log_channel', 'auth');
        $key         = Str::lower("login:" . $login);
        //===================================
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::channel($logChannel)->warning('Blocked login attempt', [
                $fieldType          => $login,
                'remaining_minutes' => $minutes,
            ]);

            return $this->sendError(null, "Account locked. Try again in $minutes minutes", 423);
        }

        if ($user) {

            //Wrong Password
            if (! Hash::check($request->password, $user->password)) {

                RateLimiter::hit($key, $lockMinutes * 60);
                $attemptsLeft = $maxAttempts - RateLimiter::attempts($key);

                Log::channel($logChannel)->info('Wrong password attempt', [
                    $fieldType      => $login,
                    'attempts_left' => max(0, $attemptsLeft),
                ]);

                if ($attemptsLeft <= 0) {
                    Log::channel($logChannel)->error('Account temporarily locked due to too many failed attempts', [
                        $fieldType => $login,
                    ]);
                }

                return $this->sendError(null, 'Invalid credentials', 401);
            }

            // Password correct + reset limiter
            RateLimiter::clear($key);

            Log::channel($logChannel)->info('Successful login', [
                $fieldType => $login,
            ]);
        } else {

            Log::channel($logChannel)->info('User not found', [
                $fieldType => $login,
            ]);

            RateLimiter::hit($key, $lockMinutes * 60);

            return $this->sendError(null, "Invalid $fieldType", 401);
        }
        if ($user->status == 'blocked') {
            return $this->sendError(null, 'This account is blocked', 401);
        }

        if ($user->is_verified == '0') {
            $user->image        = getFirstMediaUrl($user, $user->avatarCollection);
            $user->verification = '0';
            $user->token        = '';
            $user->driver_type  = ($user->driver_type == 'car' || $user->driver_type == 'comfort_car') ? 'car' : $user->driver_type;
            $user->hasVehicle   = $user->car()->exists() || $user->scooter()->exists();

            return $this->sendResponse($user, 'This account is not verified', 200);
        }

        $user->device_token = $request->device_token;
        $user->is_online    = '1';
        $user->save();

        $user->token        = $user->createToken('api')->plainTextToken;
        $user->image        = getFirstMediaUrl($user, $user->avatarCollection);
        $user->hasVehicle   = $user->car()->exists() || $user->scooter()->exists();
        $user->driver_type  = ($user->driver_type == 'car' || $user->driver_type == 'comfort_car') ? 'car' : $user->driver_type;
        $user->verification = '1';

        return $this->sendResponse($user, null, 200);
    }

    public function device_tocken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $user               = auth()->user();
        $user->device_token = $request->device_token;
        $user->save();
        return $this->sendResponse(null, 'FCM-Tocken saved successfully.', 200);

    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'           => 'required|string|email',
            'otp'             => 'required|string',
            'device_token'    => 'required',
            'invitation_code' => 'nullable|string',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $user) {
            return $this->sendError(null, 'Invalid or expired OTP', 401);

        }
        $user->device_token = $request->device_token;
        $user->is_online    = '1';
        if ($user->is_verified == '0') {
            $user->is_verified = '1';
            $user->save();
            $acceptLang = request()->header('Accept-Language');
            //$locale = substr($acceptLang, 0, 2);
            App::setLocale($acceptLang);
            if ($user->mode == 'client') {
                $message = __('general.client_welcome_message');
                DashboardMessage::create(['receiver_id' => $user->id, 'message' => $message]);
            } else {
                $message = __('general.driver_welcome_message');
                DashboardMessage::create(['receiver_id' => $user->id, 'message' => $message]);

            }
        }
        $user->save();

        if ($request->invitation_code) {
            $invitation_exchange           = floatval(Setting::where('key', 'invitation_exchange')->where('category', 'Users')->where('type', 'number')->first()->value);
            $invitation_code_owner         = User::where('invitation_code', $request->invitation_code)->first();
            $invitation_code_owner->wallet = $invitation_code_owner->wallet + floatval($invitation_exchange);
            $invitation_code_owner->save();
        }

        $user->token        = $user->createToken('api')->plainTextToken;
        $user->image        = getFirstMediaUrl($user, $user->avatarCollection);
        $user->verification = '1';

        // Here you can either log the user in or confirm their registration

        return $this->sendResponse($user, 'OTP verified successfully.', 200);

    }

    public function resend_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $otpCode = generateOTP();
        $user    = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError(null, 'There is no account with this email', 401);
        }
        $user->OTP = $otpCode;
        $user->save();
        Mail::to($request->email)->send(new SendOTP($otpCode, $user->name));
        return $this->sendResponse(null, 'OTP sent to your email address.', 200);
    }

    public function logout(Request $request)
    {
        $user         = $request->user();
        $currentToken = $user->currentAccessToken();
        // Revoke the token of the current device
        $user->is_online = '0';
        $user->save();
        $currentToken->delete();

        return $this->sendResponse(null, 'logout successfuly', 200);

    }

    public function profile($id)
    {
        $user                = User::where('id', $id)->with('city:id,name')->first();
        $user->image         = getFirstMediaUrl($user, $user->avatarCollection);
        $user->ID_frontImage = getFirstMediaUrl($user, $user->IDfrontImageCollection);
        $user->ID_backImage  = getFirstMediaUrl($user, $user->IDbackImageCollection);
        if ($user->mode == 'client') {
            $user->rate = Trip::where('user_id', $user->id)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate') ?? 0.00;
        } elseif ($user->mode == 'driver') {
            $user->rate = Trip::whereHas('car', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 0.00;
        }
        return $this->sendResponse($user, null, 200);
    }

    public function edit_personal_info(Request $request)
    {
        $rules = [

            'name'           => 'required|string|max:255',
            'email'          => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth()->user()->id)->whereNull('deleted_at'),
            ],
            'country_code'   => 'required',

            'phone'          => [
                'required',
                Rule::unique('users')->ignore(auth()->user()->id)->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                        ->whereNull('deleted_at');
                }),
            ],
            'address'        => 'nullable',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'ID_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'ID_back_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'lat'            => 'nullable',
            'lng'            => 'nullable',
            'city_id'        => [
                'required',
                'exists:cities,id',
            ],
            'passport'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

        ];
        if (auth()->user()->mode === 'driver') {
            $rules['birth_date'] = [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
            ];

        }
        $validator = Validator::make($request->all(), $rules);

        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        User::where('id', auth()->user()->id)->update(['name' => $request->name,
            'email'                                               => $request->email,
            'phone'                                               => $request->phone,
            'country_code'                                        => $request->country_code,
            'birth_date'                                          => $request->birth_date,
            'address'                                             => $request->address,
            'lat'                                                 => floatval($request->lat),
            'lng'                                                 => floatval($request->lng),
            'city_id'                                             => $request->city_id,
        ]);
        $user = auth()->user();
        if ($request->file('image')) {
            $image = getFirstMediaUrl($user, $user->avatarCollection);
            if ($image != null) {
                deleteMedia($user, $user->avatarCollection);
                uploadMedia($request->image, $user->avatarCollection, $user);
            } else {
                uploadMedia($request->image, $user->avatarCollection, $user);
            }
        }
        if ($request->file('ID_front_image')) {
            $image2 = getFirstMediaUrl($user, $user->IDfrontImageCollection);
            if ($image2 != null) {
                deleteMedia($user, $user->IDfrontImageCollection);
                uploadMedia($request->ID_front_image, $user->IDfrontImageCollection, $user);
            } else {
                uploadMedia($request->ID_front_image, $user->IDfrontImageCollection, $user);
            }
        }
        if ($request->file('ID_back_image')) {
            $image3 = getFirstMediaUrl($user, $user->IDbackImageCollection);
            if ($image3 != null) {
                deleteMedia($user, $user->IDbackImageCollection);
                uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
            } else {
                uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
            }
        }
        if ($request->file('passport')) {
            $image4 = getFirstMediaUrl($user, $user->passportImageCollection);
            if ($image4 != null) {
                deleteMedia($user, $user->passportImageCollection);
                uploadMedia($request->passport, $user->passportImageCollection, $user);
            } else {
                uploadMedia($request->passport, $user->passportImageCollection, $user);
            }
        }
        $user                = User::find(auth()->user()->id);
        $user->image         = getFirstMediaUrl($user, $user->avatarCollection);
        $user->ID_frontImage = getFirstMediaUrl($user, $user->IDfrontImageCollection);
        $user->ID_backImage  = getFirstMediaUrl($user, $user->IDbackImageCollection);
        return $this->sendResponse($user, 'Account Updated Successfuly', 200);

    }

    public function forgotPassword(Request $request)
    {
        // Check email format with custom regex
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email:rfc',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
            ],
        ], [
            'email.required' => 'Email field is required',
            'email.email'    => 'Email must be a valid format like user@example.com',
            'email.regex'    => 'Email must be a valid format like user@example.com',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email format or email field is required.',
            ], 422);
        }

        $userEmail = trim(strtolower($request->email));

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$userEmail])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Email address not found.',
            ], 404);
        }

        $userName = $user->name ?? 'User';

        $token = bin2hex(random_bytes(32));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $userEmail],
            [
                'token'      => $token,
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/open-reset?token=' . $token . '&email=' . $userEmail);
        Mail::to($userEmail)->send(new ForgotPasswordMail($userName, $resetUrl));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link has been sent to your email.',
        ], 200);
    }

    public function resetpassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|string',
                'token'    => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'email.required'     => 'The email field is required.',
                'token.required'     => 'The token field is required.',
                'password.required'  => 'The password field is required.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password.min'       => 'The password must be at least 8 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Check if the provided email and token exist
            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (! $record) {
                return response()->json(['message' => 'Invalid or expired token.'], 400);
            }

            // Check if the user exists
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            // Update the password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete used token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Password has been reset successfully.'], 200);

        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }

    public function FAQs()
    {
        $FAQs = FAQ::where('is_active', '1')->get();
        return $this->sendResponse($FAQs, null, 200);
    }

    public function update_password(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password'     => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        // Check if the old password matches the user's current password
        if (! Hash::check($request->old_password, auth()->user()->password)) {
            return $this->sendError(null, 'The old password is incorrect.', 400);

        }

        // Update the user's password
        $user           = auth()->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->sendResponse(null, 'Password updated successfully.', 200);

    }

    public function careers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:191',
            'last_name'    => 'required|string|max:191',
            'email' => 'required|email:rfc,dns|max:191',
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'position'     => 'required|string|max:191',
            'cv' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:6144',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $career = Careers::create($validator->validated());

        if ($request->hasFile('cv')) {
            uploadMedia($request->cv,$career->CvCollection,$career);

        }

        return $this->sendResponse($career, 'Application received', 200);
    }



    public function save_contact_us(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'subject'      => ['required', 'string', 'max:191'],
            'name'         => ['required', 'string', 'max:191'],
            'email'        => ['required', 'string', 'max:191', 'email'],
            'phone'        => ['required', 'numeric'],
            'message'      => ['required', 'string'],
            'country_code' => ['required'],
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        ContactUs::create(['subject' => $request->subject, 'name' => $request->name, 'email' => $request->email, 'message' => $request->message, 'phone' => $request->country_code . $request->phone]);
        return $this->sendResponse(null, 'Your request has been sent and we will respond to you later.', 200);
    }
    /////////////////////////////// محتاجة نزود الحاجات الناقصة ///////////////////////////

    public function about_us()
    {

        $response['description']      = AboutUs::where('key', 'description')->first()->value;
        $response['phone1']           = AboutUs::where('key', 'phone1')->first()->value;
        $response['email1']           = AboutUs::where('key', 'email1')->first()->value;
        $response['phone2']           = AboutUs::where('key', 'phone2')->first()->value;
        $response['email2']           = AboutUs::where('key', 'email2')->first()->value;
        $response['phone3']           = AboutUs::where('key', 'phone3')->first()->value;
        $response['email3']           = AboutUs::where('key', 'email3')->first()->value;
        $response['phone4']           = AboutUs::where('key', 'phone4')->first()->value;
        $response['email4']           = AboutUs::where('key', 'email4')->first()->value;
        $response['facebook']         = AboutUs::where('key', 'facebook')->first()->value;
        $response['instagram']        = AboutUs::where('key', 'instagram')->first()->value;
        $response['twitter']          = AboutUs::where('key', 'twitter')->first()->value;
        $response['tiktok']           = AboutUs::where('key', 'tiktok')->first()->value;
        $response['linked-in']        = AboutUs::where('key', 'linked-in')->first()->value;
        $response['app_link_android'] = AboutUs::where('key', 'app-link-android')->first()->value;
        $response['app_link_IOS']     = AboutUs::where('key', 'app-link-IOS')->first()->value;
        $response['website']          = AboutUs::where('key', 'website')->first()->value;
        return $this->sendResponse($response, null, 200);

    }

    public function remove_account(Request $request)
    {

        $user = $request->user();
        if ($user) {
            $tokens = $user->tokens;
            foreach ($tokens as $token) {
                $token->delete();
            }
            Car::where('user_id', $user->id)->delete();
            $user->delete();
            return $this->sendResponse(null, 'Account Removed successfuly', 200);
        } else {
            // Handle the case when the user is not authenticated
            return $this->sendError(null, "This Account doesn't existed", 400);
        }
    }

    public function add_feed_back(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feed_back' => ['required'],
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        FeedBack::create(['user_id' => auth()->user()->id, 'feed_back' => $request->feed_back]);
        if (auth()->user()->device_token) {
            $this->firebaseService->sendNotification(auth()->user()->device_token, 'Lady Driver - Feed Back', "Thank you for your feed back", ["screen" => "Feed Back"]);
            $data = [
                "title"   => "Lady Driver - Feed Back",
                "message" => "Thank you for your feed back",
                "screen"  => "Feed Back",
            ];
            Notification::create(['user_id' => auth()->user()->id, 'data' => json_encode($data)]);

        }

        return $this->sendResponse(null, 'Your Feed Back has been sent and we will respond to you later.', 200);

    }
    public function user_notification()
    {
        $notifications = Notification::where('user_id', auth()->user()->id)->orderBy('id', 'desc')->get()->map(function ($notification) {
            $notification->data = json_decode($notification->data);
            return $notification;
        });
        return $this->sendResponse($notifications, null, 200);
    }

    public function seen_notification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id',
        ]);

        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $notification       = Notification::findOrFail($request->notification_id);
        $notification->seen = '1';
        $notification->save();
        return $this->sendResponse(null, 'Notification seen successfully', 200);
    }

    public function app_version()
    {
        $version = Setting::where('key', 'app_version')->where('category', 'General')->where('type', 'string')->first()->value;
        return $this->sendResponse($version, null, 200);

    }
    public function update_app_version(Request $request)
    {
        Setting::where('key', 'app_version')->where('category', 'General')->where('type', 'string')->update(['value' => $request->version]);
        return $this->sendResponse(null, 'Version Updated Successfully', 200);

    }

    public function get_dashboard_messages()
    {
        $messages = DashboardMessage::where('receiver_id', auth()->user()->id)->get()->map(function ($message) {
            $message->images  = getMediaUrl($message, $message->imageCollection);
            $message->videos  = getMediaUrl($message, $message->videoCollection);
            $message->records = getMediaUrl($message, $message->recordCollection);
            return $message;
        });
        return $this->sendResponse($messages, null, 200);
    }

    public function change_lang(Request $request)
    {
        $acceptLang = request()->header('Accept-Language');
        //$locale = substr($acceptLang, 0, 2);
        App::setLocale($acceptLang);
        $user = auth()->user();
        if ($user->mode == 'client') {
            $message = __('general.client_welcome_message');
        } else {
            $message = __('general.driver_welcome_message');
        }
        $mes = DashboardMessage::where('receiver_id', $user->id)->first();
        if ($mes) {
            $mes->update(['message' => $message]);
        }

        return $this->sendResponse($acceptLang, 'success', 200);

    }

    public function cities()
    {
        $cities = City::all();
        return $this->sendResponse($cities, null, 200);
    }


    public function save_student_data(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'university_name' => 'required|string|max:255',
            'graduation_year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'id_front_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $student = Student::updateOrCreate(
            ['user_id' => auth()->user()->id],
            [
                'university_name'          => $request->university_name,
                'graduation_year'          => $request->graduation_year,
                'status'                   => 'pending',
                'student_discount_service' => '0',
            ]
        );

        if ($request->file('id_front_image')) {
            $image = getFirstMediaUrl($student, $student->IDfrontImageCollection);
            if ($image != null) {
                deleteMedia($student, $student->IDfrontImageCollection);
                uploadMedia($request->id_front_image, $student->IDfrontImageCollection, $student);
            } else {
                uploadMedia($request->id_front_image, $student->IDfrontImageCollection, $student);
            }
        }

        $user = auth()->user();
        if (! $user->student_code) {
            do {
                $barcode = Str::uuid();
            } while (User::where('student_code', $barcode)->exists());
            $user->student_code = $barcode;
            $user->save();
        }

        return $this->sendResponse($student, 'Success', 200);
    }

    public function change_student_discount_service()
    {
        $student = Student::where('user_id', auth()->user()->id)->first();
        if (! $student) {
            return $this->sendError(null, 'Student not found', 404);
        }
        $student->student_discount_service = $student->student_discount_service == 1 ? 0 : 1;
        $student->save();
        return $this->sendResponse($student, 'Student discount service updated successfully', 200);
    }

    ////////////////////////////////////////////////////////////////////////////////
    public function getPrivacyPolicy(Request $request)
    {
        $lang = $request->header('lang', 'en');

        $privacy = PrivacyAndTerm::where('type', 'privacy')
            ->where('lang', $lang)
            ->first();

        if (! $privacy) {
            return response()->json([
                'status'  => false,
                'message' => 'No content found for this language',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'lang'   => $lang,
            'value'  => $privacy->value,
        ]);
    }

    public function getTermsAndConditions(Request $request)
    {
        $lang = $request->header('lang', 'en');

        $terms = PrivacyAndTerm::where('type', 'terms')
            ->where('lang', $lang)
            ->first();

        if (! $terms) {
            return response()->json([
                'status'  => false,
                'message' => 'No content found for this language',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'lang'   => $lang,
            'value'  => $terms->value,
        ]);
    }

}
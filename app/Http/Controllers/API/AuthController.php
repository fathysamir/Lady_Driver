<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Mail\SendOTP;
use App\Models\AboutUs;
use App\Models\Car;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\DashboardMessage;
use App\Models\FAQ;
use App\Models\FawryTransaction;
use App\Models\FeedBack;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Trip;
use App\Models\User;
use App\Services\FawryService;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AuthController extends ApiController
{
    protected $firebaseService;
    protected $fawry;
    public function __construct(FirebaseService $firebaseService, FawryService $fawry)
    {
        $this->firebaseService = $firebaseService;
        $this->fawry           = $fawry;
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
            $rules['image']      = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['birth_date'] = [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
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
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

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
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        $login     = $request->email;
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Find the user based on email or phone
        $user = User::where($fieldType, $login)->first();

        if ($user) {
            if (! Hash::check($request->password, $user->password)) {
                return $this->sendError(null, 'Invalid credentials, password is incorrect', 401);
            }
        } else {
            return $this->sendError(null, 'Invalid credentials, your ' . $fieldType . ' is incorrect', 401);
        }
        if ($user->status == 'blocked') {
            return $this->sendError(null, 'this account is blocked', 401);
        }
        if ($user->is_verified == '0') {
            $user->image        = getFirstMediaUrl($user, $user->avatarCollection);
            $user->verification = '0';
            $user->token        = '';
            $user->driver_type  = ($user->driver_type == 'car' || $user->driver_type == 'comfort_car') ? 'car' : $user->driver_type;
            $user->hasVehicle   = $user->car()->exists() || $user->scooter()->exists();

            return $this->sendResponse($user, 'this account not verified', 200);
        }
        // Generate OTP
        // $otpCode = generateOTP();
        // $user->OTP= $otpCode ;
        // $user->save();
        $user->device_token = $request->device_token;
        $user->is_online    = '1';
        $user->save();
        $user->token       = $user->createToken('api')->plainTextToken;
        $user->image       = getFirstMediaUrl($user, $user->avatarCollection);
        $user->hasVehicle  = $user->car()->exists() || $user->scooter()->exists();
        $user->driver_type = ($user->driver_type == 'car' || $user->driver_type == 'comfort_car') ? 'car' : $user->driver_type;

        $user->verification = '1';
        // Send OTP via Email (or SMS)
        //Mail::to($request->email)->send(new SendOTP($otpCode));

        //return $this->sendResponse(null,'OTP sent to your email address.',200);
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

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'otp'      => 'required|string',
            'password' => 'required|string|min:8|confirmed',
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
        $user->password = Hash::make($request->password);
        $user->save();
        return $this->sendResponse(null, 'Password updated successfully, You can login with new password.', 200);
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
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $student = Student::updateOrCreate(
            ['user_id' => auth()->user()->id], // الشرط: كل يوزر ليه record واحد
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

        return $this->sendResponse($student, 'success', 200);

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

    public function Privacy_Policy()
    {
        $html = ' <div class="entry-content clear" itemprop="text" style="font-family: \'Poppins\', sans-serif;">


        <div data-elementor-type="wp-page" data-elementor-id="2467" class="elementor elementor-2467"
            data-elementor-post-type="page">
            <div class="elementor-element elementor-element-0c980a9 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="0c980a9" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-1816760 elementor-widget elementor-widget-text-editor"
                        data-id="1816760" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>Privacy Policy of Lady Driver Smart Transportation Company</p>
                            <p>Welcome to “Lady Driver Transportation,” where we prioritize the safety, comfort, and
                                privacy of women at the highest level. This privacy policy governs how we collect, use,
                                and protect the personal information of our users in accordance with Egyptian laws and
                                regulations. We are committed to providing a secure and reliable service for all our
                                users.</p>
                            <p>The company’s services form a technology platform that enables users of mobile
                                applications or websites provided as part of Lady Driver’s services to arrange and
                                schedule transportation and/or logistics services with independent third-party providers
                                of such services, including independent transportation and logistics service providers
                                under agreement with the company. The services are provided for your personal,
                                non-commercial use only, unless otherwise agreed in a separate written agreement. You
                                acknowledge that the company does not provide transportation or logistics services or
                                operate as a transportation company. All services are provided by independent
                                contractors who are not employees of the company or any of its affiliates.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-f50698a e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="f50698a" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-2c82d4c elementor-widget elementor-widget-text-editor"
                        data-id="2c82d4c" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>Company Information</p>
                            <p>Trade Name: Lady Driver for Smart Transportation</p>
                            <p>Commercial Registration Number: 243941</p>
                            <p>Headquarters: Cairo, Egypt</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-9c056ab e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="9c056ab" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-9e8847d elementor-widget elementor-widget-text-editor"
                        data-id="9e8847d" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>1. Information We Collect</p>
                            <p>A. Personal Information<br>When registering or using our services, we may collect the
                                following information to ensure a smooth and secure experience:</p>
                            <p>Full Name</p>
                            <p>Phone Number</p>
                            <p>Email Address</p>
                            <p>Home Address or Preferred Pickup Points</p>
                            <p>Payment and Billing Information (if necessary)</p>
                            <p>ID or Driver’s License Photo (for drivers)</p>
                            <p><br>B. Technical Information<br>We collect certain technical data when using the app or
                                website, such as:</p>
                            <p>Geolocation data for accurate service and better user experience</p>
                            <p>Device type and operating system for platform compatibility</p>
                            <p>IP address for improved security and suspicious activity detection</p>
                            <p>Cookies and tracking technologies to analyze performance and improve service</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-04d54fc e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="04d54fc" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-5a6b424 elementor-widget elementor-widget-text-editor"
                        data-id="5a6b424" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>2. How We Use the Information</p>
                            <p>The information we collect is used for the following purposes:</p>
                            <p>To provide safe and efficient transportation services</p>
                            <p>To personalize the user experience based on past preferences</p>
                            <p>To securely process payments and issue invoices</p>
                            <p>To send trip notifications, promotional offers, and updates</p>
                            <p>To comply with Egyptian legal and regulatory requirements</p>
                            <p>To enhance security and monitor any illegal or suspicious activity</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-ce6e69a e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="ce6e69a" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-51a59bb elementor-widget elementor-widget-text-editor"
                        data-id="51a59bb" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>3. Sharing Information with Third Parties</p>
                            <p>We do not sell or rent your data to any third party. However, some data may be shared in
                                specific cases:</p>
                            <p>Service Providers: Such as payment gateways or cloud services to ensure a smooth and
                                secure experience</p>
                            <p>Legal Compliance: If required by Egyptian law or upon request by competent authorities
                            </p>
                            <p>Emergencies: To protect the safety of users or the public in urgent cases</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-3271cb3 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="3271cb3" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-efec544 elementor-widget elementor-widget-text-editor"
                        data-id="efec544" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>4. Data Security</p>
                            <p>We take your data security very seriously and implement strict measures, including:</p>
                            <p>Encrypting sensitive data to prevent unauthorized access</p>
                            <p>Using modern security protocols during data transmission and storage</p>
                            <p>Restricting data access to authorized personnel only</p>
                            <p>Deploying intrusion detection systems to prevent security breaches</p>
                            <p>Conducting regular security audits to detect and fix vulnerabilities</p>
                            <p>Using two-factor authentication (2FA) for secure login</p>
                            <p>Keeping activity logs to monitor unauthorized access attempts</p>
                            <p>Training employees on privacy and security best practices</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-1f385d1 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="1f385d1" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-5756ba6 elementor-widget elementor-widget-text-editor"
                        data-id="5756ba6" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>5. User Rights</p>
                            <p>As a user of Lady Driver, you have the right to:</p>
                            <p>Access Your Data: Request a copy of your stored personal information</p>
                            <p>Correct Your Information: Update or correct inaccurate data</p>
                            <p>Request Deletion: Ask for your data to be deleted, subject to legal obligations</p>
                            <p>Withdraw Consent: Opt-out of data usage anytime via account settings</p>
                            <p>Object to Processing: Refuse the use of your data for specific purposes (e.g., marketing)
                            </p>
                            <p>Restrict Processing: Request data processing restrictions in specific cases</p>
                            <p>Data Portability: Request your data to be transferred to another service, if technically
                                possible</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-818edf8 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="818edf8" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-a2ae0a6 elementor-widget elementor-widget-text-editor"
                        data-id="a2ae0a6" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>6. Account Suspension and Deletion</p>
                            <p>Lady Driver reserves the right to suspend or delete any user account in the following
                                cases:</p>
                            <p>Violating Egyptian laws and regulations</p>
                            <p>Breaching company policies or terms of use</p>
                            <p>Using the service in a manner that compromises user safety</p>
                            <p>Providing false or misleading information</p>
                            <p>Committing any illegal or abusive act against drivers or other users</p>
                            <p>Abusing the service (e.g., fake bookings or frequent unjustified cancellations)</p>
                            <p>Exploiting the platform for fraudulent or criminal activities</p>
                            <p>Sharing or posting inappropriate or offensive content via the app</p>
                            <p><br>Users will be notified of the action taken against them with reasons provided when
                                necessary. They may appeal in some cases according to company policy.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-80d14a9 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="80d14a9" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-f3e7223 elementor-widget elementor-widget-text-editor"
                        data-id="f3e7223" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>7. Commissions and Financial Penalties</p>
                            <p>The company reserves the right to adjust driver commission fees based on economic
                                conditions and operational needs to maintain high-quality service.</p>
                            <p>Financial penalties may be imposed on drivers for:</p>
                            <p>Insulting customers in any way</p>
                            <p>Discriminating based on race, gender, or ethnicity</p>
                            <p>Engaging in conduct that violates professional standards or damages the company’s
                                reputation</p>
                            <p><br>If a customer misuses the app or commits a legal violation (e.g., verbal or physical
                                harassment or abuse), the company reserves the right to take appropriate legal action,
                                including prosecution under Egyptian law.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-410e402 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="410e402" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-37f21f5 elementor-widget elementor-widget-text-editor"
                        data-id="37f21f5" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>8. Minimum Age</p>
                            <p>The minimum age to use Lady Driver’s services is 18 years.<br>Minors are not allowed to
                                use the service unless accompanied by a responsible adult.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-4d178f2 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="4d178f2" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-6655f18 elementor-widget elementor-widget-text-editor"
                        data-id="6655f18" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>9. Protection of Children and Minors’ Data</p>
                            <p>At Lady Driver, we place great importance on protecting the privacy of children and
                                minors under the age of 18. We do not collect or process personal data from children
                                without explicit consent from a parent or legal guardian, in accordance with Egyptian
                                law.</p>
                            <p>If we discover that any child’s data has been collected without the necessary consent, we
                                will take immediate steps to delete it. We encourage parents to monitor their children’s
                                online activity and contact us with any questions or concerns.</p>
                            <p>For more information about our handling of minors’ data or to request deletion of
                                unintended data, please contact us through official support channels.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-8551f8d e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="8551f8d" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-5b59dc7 elementor-widget elementor-widget-text-editor"
                        data-id="5b59dc7" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>10. Privacy Policy Updates</p>
                            <p>We may update this privacy policy from time to time in response to legal or operational
                                changes, including:</p>
                            <p>Data Collection Updates: Adding new data types to improve user experience</p>
                            <p>Changes in Data Sharing: Expanding partnerships with service providers</p>
                            <p>Security &amp; Compliance Revisions: Applying new standards or changes in applicable law
                            </p>
                            <p><br>How users will be notified:</p>
                            <p>Notifications will be sent via email and app for any major updates</p>
                            <p>Updates will include the effective date and a summary of changes</p>
                            <p>Continuing to use the service after updates means you accept the revised policies</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-2a76289 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="2a76289" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-c660395 elementor-widget elementor-widget-text-editor"
                        data-id="c660395" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p><img draggable="false" style="width: 13px;" role="img" class="emoji"
                                    alt="🛡" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f6e1.svg">
                                Anti-Discrimination &amp; Exploitation Policy – Lady Driver<br>At Lady Driver, we
                                believe that dignity, justice, and equality are fundamental values that cannot be
                                compromised. Driven by our vision to empower women and provide a safe and respectful
                                environment for them, we are committed to combating all forms of discrimination, racism,
                                and exploitation within our platform.</p>
                            <p><img draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="🚫" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f6ab.svg"> Zero
                                Tolerance for Discrimination<br>Lady Driver strongly condemns all forms of
                                discrimination based on race, color, nationality, religion, social background, economic
                                status, gender, or personal orientation.<br>We do not tolerate under any circumstances
                                the presence, promotion, or justification of such actions within the app or between its
                                users.</p>
                            <p><img draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="❌" src="https://s.w.org/images/core/emoji/16.0.1/svg/274c.svg"> No
                                Room for Racism or Gender Bias<br>We believe that racism and gender bias undermine human
                                dignity and contradict our core principles.<br>Therefore, any racist or gender-biased
                                behavior, whether from a captain or a rider, is considered a serious violation of our
                                platform’s terms of use and will result in immediate and permanent suspension of the
                                violating account without prior warning.</p>
                            <p><img draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="🗣" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f5e3.svg"><img
                                    draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="🚫" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f6ab.svg">
                                Complete Rejection of Sexual Harassment<br>We are committed to providing a platform free
                                from any form of sexual harassment, whether verbal, physical, or visual.<br>Any behavior
                                that includes inappropriate comments, sexual insinuations, or unwelcome advances will be
                                considered a severe violation and will lead to immediate and permanent banning of the
                                offending user, along with legal action if necessary.</p>
                            <p><img draggable="false" role="img"style="width: 13px;" class="emoji"
                                    alt="🧒" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f9d2.svg"><img
                                    draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="🚫" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f6ab.svg">
                                Protection Against Exploitation<br>Lady Driver absolutely rejects the exploitation of
                                any individual, particularly children, whether directly or indirectly, under any
                                circumstance.<br>We are committed to reporting any suspected cases to the relevant
                                authorities and encourage our users to immediately report any inappropriate or
                                suspicious behavior.</p>
                            <p><img draggable="false" style="width: 13px;"role="img" class="emoji" alt="🚺"
                                    src="https://s.w.org/images/core/emoji/16.0.1/svg/1f6ba.svg"> A Platform for Women,
                                Not Against Men<br>While Lady Driver is exclusively designed for women drivers and
                                riders, this does not constitute any form of racial or gender bias against men.<br>This
                                is a decision made with the intention of protecting women and providing them with a
                                safe, comfortable, and private travel environment, especially given the challenges they
                                may face in public spaces.</p>
                            <p><img draggable="false"style="width: 13px;" role="img" class="emoji"
                                    alt="💜" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f49c.svg">
                                Towards Empowering Women and Respecting All<br>Lady Driver was established to empower
                                women economically and socially, granting them the opportunity to enter the workforce
                                independently and safely.<br>At the same time, we hold the values of mutual respect and
                                universal humanity in high regard and believe that no community can progress unless the
                                dignity of every individual is upheld, free from discrimination or exclusion of any
                                kind.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-0b9ca6b e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="0b9ca6b" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-8176cf0 elementor-widget elementor-widget-text-editor"
                        data-id="8176cf0" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>11. Contact Us</p>
                            <p>If you have any questions about this privacy policy or how we handle your personal data,
                                please contact us via:</p>
                            <p>Email: Ladydriver900@gmail.com</p>
                            <p>Phone: 01100362888/01154695582</p>
                            <p>Address: Cairo, Egypt</p>
                            <p>Website: Lady-driver.com</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-96e9256 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="96e9256" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-c09b03e elementor-widget elementor-widget-text-editor"
                        data-id="c09b03e" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>At Lady Driver, your privacy is the cornerstone of a safe, comfortable, and trustworthy
                                transportation experience tailored to you. We don’t just offer rides — we offer a
                                platform designed to empower you and support your independence, with privacy and safety
                                at the heart of every journey.</p>
                            <p>We commit to the highest global standards in personal data protection and utilize
                                advanced security technologies to ensure confidentiality and prevent unauthorized use.
                                We also believe in transparency, providing you with all the details on how your data is
                                collected and used — giving you full control over your privacy settings.</p>
                            <p>We’re always here to support you. If you have any concerns or questions about your
                                privacy or personal data, our dedicated team is ready to assist via official
                                communication channels. We also regularly review and update our policies to keep pace
                                with the latest legal and technological developments, ensuring continued secure and
                                reliable service.</p>
                            <p>By choosing Lady Driver, you’re not just choosing an app — you’re joining a community
                                designed to support Egyptian women in their daily commutes with safety, privacy, and
                                confidence. Thank you for your trust. We look forward to being your first choice for
                                every ride.</p>
                            <p>Together, we make every journey more comfortable and safe.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        </div>';

        return $this->sendResponse($html, null, 200);

    }

    public function terms_conditions()
    {
        $html = '<div class="entry-content clear" itemprop="text">


        <div data-elementor-type="wp-page" data-elementor-id="2453" class="elementor elementor-2453"
            data-elementor-post-type="page">
            <div class="elementor-element elementor-element-edf29dd e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="edf29dd" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-2693034 elementor-widget elementor-widget-text-editor"
                        data-id="2693034" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>1. Introduction</p>
                            <p>Welcome to Lady Driver, the first app designed specifically for women to offer smart
                                transportation services with complete safety, comfort, and privacy. Lady Driver is an
                                Egyptian application headquartered in Cairo, aiming to support women’s financial
                                independence and promote a safe and healthy transportation environment. By using this
                                app, you agree to all the terms outlined in this policy.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-f8d9e16 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="f8d9e16" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-b495484 elementor-widget elementor-widget-text-editor"
                        data-id="b495484" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>2. Safety and Privacy<br><br></p>
                            <p>The app provides a safe and comfortable environment for every woman, featuring options
                                such as location sharing with trusted contacts and emergency contact numbers like the
                                police, ambulance, rescue, and fire services.</p>
                            <p>We enforce strict registration procedures to ensure that all users are women only, with a
                                thorough review of personal information and official documents.</p>
                            <p><span style="font-style: inherit; font-weight: inherit;">The app includes a secure
                                    internal communication system, voice recording during conversations between the
                                    client and the driver, and the ability to send images for precise location
                                    sharing.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">All user data is stored with
                                    complete confidentiality according to global digital protection standards and is not
                                    shared with third parties without the user’s explicit consent</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-57fa62a e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="57fa62a" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-a699c88 elementor-widget elementor-widget-text-editor"
                        data-id="a699c88" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>3. Driver Requirements</p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Drivers must be at least 21
                                    years old.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Vehicles must be suitable for
                                    comfortable and safe passenger transportation.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">The minimum accepted car model
                                    is 2006; models from 2015 and below are required to undergo inspection at a
                                    certified center.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Drivers are not considered
                                    employees of the company, and the company is not liable for any incidents occurring
                                    during trips.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Drivers must maintain a balance
                                    of 250 EGP in their in-app wallet to allow for commission deductions.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Drivers can withdraw their
                                    earnings twice a week.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Men are strictly prohibited from
                                    working as drivers on the app.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">All drivers must comply with
                                    local traffic laws and regulations.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Drivers must ensure a premium
                                    experience for clients, including maintaining vehicle cleanliness and not playing
                                    music without the client’s approval.</span></p>
                            <div>&nbsp;</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-49b7eed e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="49b7eed" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-8ae24b7 elementor-widget elementor-widget-text-editor"
                        data-id="8ae24b7" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>4. Healthy and Sustainable Environment</p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Smoking is strictly prohibited
                                    during rides; any violation grants the affected party the right to cancel the trip
                                    without fees.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">The app supports green and
                                    sustainable policies and encourages drivers to own eco-friendly electric
                                    vehicles.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Pets are allowed if the driver
                                    consents.</span></p>
                            <p><span style="font-style: inherit; font-weight: inherit;">Both drivers and clients are
                                    educated on the importance of environmental conservation and reducing carbon
                                    emissions.</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-8fbcf21 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="8fbcf21" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-7d61142 elementor-widget elementor-widget-text-editor"
                        data-id="7d61142" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>5. Available Services</p>
                            <p>Standard rides, comfort rides, scooter services, and bus services.</p>
                            <p>Intercity rides, long-distance, and short-distance trips.</p>
                            <p>Advanced booking available through the app.</p>
                            <p>Option to select a preferred driver for recurring trips.</p>
                            <p>Clients can choose the type of vehicle according to their preference.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-7f74b55 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="7f74b55" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-a18972e elementor-widget elementor-widget-text-editor"
                        data-id="a18972e" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>6. Payment and E-Wallet Policy</p>
                            <p>The app supports payments via Visa cards, bank cards, and e-wallets available in Egypt.
                            </p>
                            <p>Clients can add funds to their in-app wallet for alternative payment methods.</p>
                            <p>If a driver wishes to delete their account, they may reclaim the remaining wallet balance
                                if there are no outstanding amounts.</p>
                            <p>Users can review their financial transaction history and payment details with full
                                transparency.</p>
                            <p>Users bear any additional fees imposed by financial institutions for electronic payments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-288cf9a e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="288cf9a" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-c4e3125 elementor-widget elementor-widget-text-editor"
                        data-id="c4e3125" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>7. Tax and Legal Responsibility</p>
                            <p>The company complies with all Egyptian tax laws, and drivers are responsible for paying
                                their applicable taxes according to the regulations of the Egyptian Tax Authority.</p>
                            <p>Drivers are responsible for their own tax obligations and must issue invoices if
                                required.</p>
                            <p>Lady Driver is not liable for any misuse of the app by users.</p>
                            <p>Possessing or transporting drugs, hashish, or any other illegal substances in the vehicle
                                will result in legal accountability for the driver or the client, and the company holds
                                no responsibility in such cases.</p>
                            <p>Drivers are fully responsible for the cleanliness and safety of their vehicles and must
                                conduct regular vehicle inspections.</p>
                            <p>In the event of an accident, the driver bears full legal and insurance responsibility for
                                their vehicle</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-b415def e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="b415def" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-c9d262e elementor-widget elementor-widget-text-editor"
                        data-id="c9d262e" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>8. Incentives and Rewards</p>
                            <p>The company motivates drivers with periodic rewards and bonuses in recognition of their
                                efforts.</p>
                            <p>Drivers can earn loyalty points that can be redeemed for cash or exclusive in-app
                                benefits.</p>
                            <p>Free training courses are provided to drivers to enhance driving and customer service
                                skills.</p>
                            <p>Promotional offers are presented to both clients and drivers from time to time.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-266ab3a e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="266ab3a" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-51f4069 elementor-widget elementor-widget-text-editor"
                        data-id="51f4069" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>9. Ease of Use</p>
                            <p>The app is designed to be user-friendly and intuitive to ensure the best experience for
                                both clients and drivers.</p>
                            <p>It supports global languages and includes a dark mode for a more comfortable experience.
                            </p>
                            <p>The interface can be customized to suit the needs of the client or the driver.</p>
                            <p>24/7 technical support is available within the app.</p>
                            <p>The app includes a mutual rating feature between clients and drivers to maintain quality
                                standards.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-b121166 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="b121166" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-61a4d20 elementor-widget elementor-widget-text-editor"
                        data-id="61a4d20" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>10. Contact Channels</p>
                            <p>Lady Driver is active on all major social media platforms, including Facebook, Instagram,
                                TikTok, LinkedIn, and others.</p>
                            <p>Technical support can be reached via email or the in-app chat service.</p>
                            <p>The company offers regular newsletters containing service updates and exclusive offers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-973baf2 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="973baf2" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-da31e61 elementor-widget elementor-widget-text-editor"
                        data-id="da31e61" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>Shipping Policy – Lady Driver Smart Transportation</p>
                            <p>1. Nature of the Service<br>Lady Driver is a ride-hailing platform dedicated to passenger
                                transport only.<br>We do not provide shipping, delivery, or courier services for
                                packages, parcels, or goods—whether accompanied or unaccompanied by the passenger.</p>
                            <p>2. Responsibility for Personal Belongings<br>Passengers are fully responsible for their
                                own personal belongings during any trip.</p>
                            <p>Lady Driver holds no liability for any lost, damaged, or forgotten items—at any time.</p>
                            <p>The company does not offer compensation, recovery, or legal responsibility for personal
                                items under any circumstances.</p>
                            <p>3. Forgotten Items in the Vehicle<br>If a passenger forgets any item in the car, she may
                                report it using the “Lost Item” feature within the app to contact the driver directly.
                            </p>
                            <p>Lady Driver does not guarantee the retrieval of lost items and holds no legal obligation
                                to do so.</p>
                            <p>Responsibility for communication and item return lies solely between the driver and the
                                passenger.</p>
                            <p>4. Driver Liability<br>Each driver using the platform is individually responsible for
                                complying with service terms and refraining from transporting any prohibited or illegal
                                materials.</p>
                            <p>The company is not legally responsible for any individual actions taken by drivers beyond
                                the scope of the platform’s terms and policies.</p>
                            <p>5. Prohibited Items<br>It is strictly forbidden to use the service for the transport of:
                            </p>
                            <p>Hazardous, flammable, or dangerous materials.</p>
                            <p>High-value items such as jewelry, cash, or electronics.</p>
                            <p>Any substances or goods that are illegal under Egyptian law or violate platform safety
                                policies.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="elementor-element elementor-element-3a565f0 e-flex e-con-boxed e-con e-parent e-lazyloaded"
                data-id="3a565f0" data-element_type="container">
                <div class="e-con-inner">
                    <div class="elementor-element elementor-element-dded707 elementor-widget elementor-widget-text-editor"
                        data-id="dded707" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                            <p>At Lady Driver, we don’t just provide a means of transportation—we create a travel
                                experience that offers you the independence, safety, and comfort you deserve. We believe
                                every ride holds precious moments, and that’s why we ensure every detail of our services
                                meets the highest standards of quality and professionalism.</p>
                            <p>We are here to be your partner every step of the way. For a smooth experience, you can
                                reach out to our support team via email or in-app chat, and stay updated through our
                                newsletter with the latest updates and exclusive offers.</p>
                            <p>Your trust fuels our vision, and your comfort is our ultimate goal. With Lady Driver,
                                you’re at the heart of our journey—because every road you take is a new beginning toward
                                greater freedom and limitless comfort.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        </div>';
        return $this->sendResponse($html, null, 200);

    }
}

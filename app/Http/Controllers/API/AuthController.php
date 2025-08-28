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
use App\Models\FeedBack;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AuthController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function register(Request $request)
    {
        $rules = [
            'name'           => 'required|string|max:255',
            'email'          => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
                    ->whereNull('deleted_at'),
            ],
            'password'       => 'required|string|min:8|confirmed',
            'mode'           => 'required|in:driver,client',
            'country_code'   => 'required|string|max:10',
            'phone'          => [
                'required',
                Rule::unique('users')
                    ->where(function ($query) use ($request) {
                        return $query->where('country_code', $request->country_code)
                            ->whereNull('deleted_at');
                    }),
            ],
            'city_id'        => 'required|exists:cities,id',
            'ID_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'ID_back_image'  => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'national_ID'    => 'required|digits:14',
        ];

// Conditional rules for driver
        if ($request->input('mode') === 'driver') {
            $rules['image']      = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['birth_date'] = [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
            ];
            $rules['driver_type'] = 'required|in:scooter,car';
            $rules['year']        = 'required|integer|min:2000|max:' . date('Y');
        }

// Conditional rules for client
        if ($request->input('mode') === 'client') {
            $rules['gendor'] = 'required|in:Male,Female';
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
            'national_id'     => $request->national_id,
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

        // Send OTP via Email (or SMS)
        Mail::to($request->email)->send(new SendOTP($otpCode, $request->name));

        return $this->sendResponse(null, 'OTP sent to your email address.', 200);

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
            return $this->sendResponse($user, 'this account not verified', 200);
        }
        // Generate OTP
        // $otpCode = generateOTP();
        // $user->OTP= $otpCode ;
        // $user->save();
        $user->device_token = $request->device_token;
        $user->is_online    = '1';
        $user->save();
        $user->token = $user->createToken('api')->plainTextToken;
        $user->image = getFirstMediaUrl($user, $user->avatarCollection);
        $user->hasVehicle = $user->car()->exists() || $user->scooter()->exists();
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
        }if ($request->file('ID_back_image')) {
            $image3 = getFirstMediaUrl($user, $user->IDbackImageCollection);
            if ($image3 != null) {
                deleteMedia($user, $user->IDbackImageCollection);
                uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
            } else {
                uploadMedia($request->ID_back_image, $user->IDbackImageCollection, $user);
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
        $FAQs = FAQ::where('is_active', 1)->get();
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

        $response['description'] = AboutUs::where('key', 'description')->first()->value;
        $response['phone1']      = AboutUs::where('key', 'phone1')->first()->value;
        $response['email1']      = AboutUs::where('key', 'email1')->first()->value;
        $response['phone2']      = AboutUs::where('key', 'phone2')->first()->value;
        $response['email2']      = AboutUs::where('key', 'email2')->first()->value;
        $response['phone3']      = AboutUs::where('key', 'phone3')->first()->value;
        $response['email3']      = AboutUs::where('key', 'email3')->first()->value;
        $response['phone4']      = AboutUs::where('key', 'phone4')->first()->value;
        $response['email4']      = AboutUs::where('key', 'email4')->first()->value;
        $response['facebook']    = AboutUs::where('key', 'facebook')->first()->value;
        $response['instagram']   = AboutUs::where('key', 'instagram')->first()->value;
        $response['twitter']     = AboutUs::where('key', 'twitter')->first()->value;
        $response['tiktok']      = AboutUs::where('key', 'tiktok')->first()->value;
        $response['linked-in']   = AboutUs::where('key', 'linked-in')->first()->value;
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

}

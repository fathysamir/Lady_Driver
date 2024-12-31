<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\FAQ;
use App\Models\Trip;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use App\Mail\SendOTP;
use App\Models\FeedBack;
use App\Models\Car;
use App\Models\AboutUs;
use App\Models\Notification;
use App\Models\ContactUs;
use App\Services\FirebaseService;

use Illuminate\Support\Facades\Validator;

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
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:8|confirmed',
            'mode' => 'required',
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ]
            
        ];
        
        // Add a conditional rule for 'image' based on the 'mode' field
        if ($request->input('mode') === 'driver') {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:3072'; // Adjust as needed
        }
        if ($request->input('mode') === 'client') {
            $rules['gendor'] = 'required|in:Male,Female'; // Adjust as needed
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return $this->sendError(null, $validator->errors(), 400);
        }
        
        
        $otpCode = generateOTP();
        do {
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        } while (User::where('invitation_code', $invitation_code)->exists());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'mode' => $request->mode,
            'OTP' => $otpCode,
            'invitation_code' => $invitation_code,
            'gendor'=>$request->gendor,
            'birth_date' => $request->birth_date
        ]);
        if($request->mode=='client'){
            $user->status='confirmed';
            $user->save();
        }
        $role = Role::where('name','Client')->first();
            
        $user->assignRole([$role->id]);
        // Generate OTP
        
        if($request->file('image')){
            uploadMedia($request->image,$user->avatarCollection,$user);
        }
        
        // Send OTP via Email (or SMS)
        Mail::to($request->email)->send(new SendOTP($otpCode));

       
        return $this->sendResponse(null,'OTP sent to your email address.',200);

    }

    public function login(Request $request)
    {
      
        $validator  =   Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string|min:8',
            'device_token'=>'required'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $login = $request->email;
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        // Find the user based on email or phone
        $user = User::where($fieldType, $login)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendError(null, 'Invalid credentials', 401);
        }
        if($user->status=='blocked'){
            return $this->sendError(null, 'this account is blocked', 401);
        }
        // Generate OTP
        // $otpCode = generateOTP();
        // $user->OTP= $otpCode ;
        // $user->save();
        $user->device_token=$request->device_token;
        $user->save();
        $user->token=$user->createToken('api')->plainTextToken;
        $user->image=getFirstMediaUrl($user,$user->avatarCollection);
    
        // Send OTP via Email (or SMS)
        //Mail::to($request->email)->send(new SendOTP($otpCode));

        //return $this->sendResponse(null,'OTP sent to your email address.',200);
        return $this->sendResponse($user,null,200);

    }

    public function device_tocken(Request $request){
        $validator  =   Validator::make($request->all(), [
            'device_token'=>'required'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }

        $user=auth()->user();
        $user->device_token=$request->device_token;
        $user->save();
        return $this->sendResponse(null,'FCM-Tocken saved successfully.',200);

    }

    public function verifyOTP(Request $request)
    {
        $validator  =   Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string',
            'device_token'=>'required',
            'invitation_code'=>'nullable|string'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $user = User::where('email', $request->email)
                ->where('otp', $request->otp)
                ->first();

        if (!$user) {
            return $this->sendError(null,'Invalid or expired OTP',401);

        }
        $user->device_token=$request->device_token;
        $user->save();
        if($request->invitation_code){
            $invitation_exchange=floatval(Setting::where('key','invitation_exchange')->where('category','Users')->where('type','number')->first()->value);
            $invitation_code_owner=User::where('invitation_code', $request->invitation_code)->first();
            $invitation_code_owner->wallet=$invitation_code_owner->wallet+floatval($invitation_exchange);
            $invitation_code_owner->save();
        }
        $user->token=$user->createToken('api')->plainTextToken;
        $user->image=getFirstMediaUrl($user,$user->avatarCollection);
       
        

        // Here you can either log the user in or confirm their registration

        return $this->sendResponse($user,'OTP verified successfully.',200);

    }
    
    public function resend_otp(Request $request){
        $validator  =   Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $otpCode = generateOTP();
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError(null,'There is no account with this email',401);
        }
        $user->OTP= $otpCode ;
        $user->save();
        Mail::to($request->email)->send(new SendOTP($otpCode));
        return $this->sendResponse(null,'OTP sent to your email address.',200);
    }

    public function logout(Request $request){
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        // Revoke the token of the current device
        $user->is_online='0';
        $user->save();
        $currentToken->delete();
       
        return $this->sendResponse(null,'logout successfuly',200);
        
    }

    public function profile($id){
        $user=User::find($id);
        $user->image=getFirstMediaUrl($user,$user->avatarCollection);
        if($user->mode=='client'){
            $user->rate=Trip::where('user_id',auth()->user()->id)->where('status','completed')->where('driver_stare_rate','>',0)->avg('driver_stare_rate')?? 0.00;
        }elseif($user->mode=='driver'){
            $user->rate=Trip::whereHas('car', function ($query)use($user) {
                $query->where('user_id', $user->id);
            })->where('status','completed')->where('client_stare_rate','>',0)->avg('client_stare_rate')?? 0.00;
        }
        return $this->sendResponse($user,null,200);
    }

    public function edit_personal_info(Request $request){
        $validator  =   Validator::make($request->all(), [
            
             'name' => 'required|string|max:255',
             'email' => 'required|string|email|max:255|unique:users,email,'.auth()->user()->id,
             'phone' =>'required|unique:users,phone,'.auth()->user()->id,
             'birth_date' => 'nullable|date',
             'address' => 'nullable', 
             'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072', 
             'lat' => 'nullable',
             'lng' => 'nullable',
            
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }

         User::where('id',auth()->user()->id)->update([ 'name' => $request->name,
                                                        'email' => $request->email,
                                                        'phone' => $request->phone,
                                                        'birth_date' => $request->birth_date,
                                                        'address'=>$request->address,
                                                        'lat'=>floatval($request->lat),
                                                        'lng'=>floatval($request->lng)
                                                        ]);
        $user=auth()->user();
        if($request->file('image')){
            $image=getFirstMediaUrl($user,$user->avatarCollection);
            if($image!= null){
                deleteMedia($user,$user->avatarCollection);
                uploadMedia($request->image,$user->avatarCollection,$user);
            }else{
                uploadMedia($request->image,$user->avatarCollection,$user);
            }
        }
        $user=User::find(auth()->user()->id);
        $user->image=getFirstMediaUrl($user,$user->avatarCollection);
        return $this->sendResponse($user,'Account Updated Successfuly',200);

    }

    public function reset_password(Request $request){
        $validator  =   Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $user = User::where('email', $request->email)
                        ->where('otp', $request->otp)
                        ->first();

        if (!$user) {
            return $this->sendError(null,'Invalid or expired OTP',401);

        }
        $user->password = Hash::make($request->password);
        $user->save();
        return $this->sendResponse(null,'Password updated successfully, You can login with new password.',200);
    }

    public function FAQs(){
        $FAQs=FAQ::where('is_active',1)->get();
        return $this->sendResponse($FAQs,null,200);
    }

    public function update_password(Request $request){
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);
    
        if ($validator->fails()) {
            
            return $this->sendError(null,$validator->errors(),401);

        }
    
        // Check if the old password matches the user's current password
        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return $this->sendError(null,'The old password is incorrect.',400);

        }
    
        // Update the user's password
        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->save();
    
        return $this->sendResponse(null,'Password updated successfully.',200);

    }

    public function save_contact_us(Request $request){
       
        $validator  =   Validator::make($request->all(), [
               
            'subject' => ['required', 'string','max:191'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'max:191','email'],
            'phone' => ['required', 'numeric'],
            'message' => ['required','string'],
            'country_code' =>['required']
        ]);
            // dd($request->all());
        if ($validator->fails()) {
            return $this->sendError(null,$validator->errors(),400);
        }
        ContactUs::create(['subject'=>$request->subject,'name'=>$request->name,'email'=>$request->email,'message'=>$request->message,'phone'=>$request->country_code . $request->phone]);
        return $this->sendResponse(null,'Your request has been sent and we will respond to you later.',200);
    }

    public function about_us(){
       
        $response['description']=AboutUs::where('key','description')->first()->value;
        $response['phone']=AboutUs::where('key','phone')->first()->value;
        $response['email']=AboutUs::where('key','email')->first()->value;
        $response['facebook']=AboutUs::where('key','facebook')->first()->value;
        $response['instagram']=AboutUs::where('key','instagram')->first()->value;
        $response['twitter']=AboutUs::where('key','twitter')->first()->value;
        return $this->sendResponse($response,null,200);

    }

    public function remove_account(Request $request){

        $user = $request->user(); 
        if ($user) {
            $tokens = $user->tokens;
            foreach ($tokens as $token) {
                $token->delete();
            }
            Car::where('user_id',$user->id)->delete();
            $user->delete();
            return $this->sendResponse(null,'Account Removed successfuly',200);
        } else {
            // Handle the case when the user is not authenticated
            return $this->sendError(null,"This Account doesn't existed",400);
        }
    }
    
    public function add_feed_back(Request $request){
        $validator  =   Validator::make($request->all(), [
            'feed_back' =>['required']
        ]);
            // dd($request->all());
        if ($validator->fails()) {
            return $this->sendError(null,$validator->errors(),400);
        }
        FeedBack::create(['user_id'=>auth()->user()->id,'feed_back'=>$request->feed_back]);
        if(auth()->user()->device_token){
            $this->firebaseService->sendNotification(auth()->user()->device_token,'Lady Driver - Feed Back',"Thank you for your feed back",["screen"=>"Feed Back"]);
            $data=[
                "title"=>"Lady Driver - Feed Back",
                "message"=>"Thank you for your feed back",
                "screen"=>"Feed Back",
            ];
            Notification::create(['user_id'=>auth()->user()->id,'data'=>json_encode($data)]);
              
        }
            
        return $this->sendResponse(null,'Your Feed Back has been sent and we will respond to you later.',200);

    }
    public function user_notification(){
        $notifications=Notification::where('user_id',auth()->user()->id)->orderBy('id', 'desc')->get()->map(function($notification){
              $notification->data=json_decode($notification->data);
              return $notification;
        });
        return $this->sendResponse($notifications,null,200);
    }

    public function seen_notification(Request $request){
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id',
        ]);
    
        if ($validator->fails()) {
            
            return $this->sendError(null,$validator->errors(),401);

        }
        $notification=Notification::findOrFail($request->notification_id);
        $notification->seen='1';
        $notification->save();
        return $this->sendResponse(null,'Notification seen successfully',200);
    }
}
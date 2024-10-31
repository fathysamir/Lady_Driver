<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\FAQ;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use App\Models\AboutUs;
use App\Models\ContactUs;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
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
            ],
        ];
        
        // Add a conditional rule for 'image' based on the 'mode' field
        if ($request->input('mode') === 'driver') {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:3072'; // Adjust as needed
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return $this->sendError(null, $validator->errors(), 400);
        }
        
        if ($validator->fails()) {
            return $this->sendError(null, $validator->errors(), 400);
        }
        $otpCode = generateOTP();
        $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'mode' => $request->mode,
            'OTP' => $otpCode,
            'invitation_code' => $invitation_code,
            
            'birth_date' => $request->birth_date
        ]);
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

    public function verifyOTP(Request $request)
    {
        $validator  =   Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string',
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
            $user->delete();
            return $this->sendResponse(null,'Account Removed successfuly',200);
        } else {
            // Handle the case when the user is not authenticated
            return $this->sendError(null,"This Account doesn't existed",400);
        }
    }

}
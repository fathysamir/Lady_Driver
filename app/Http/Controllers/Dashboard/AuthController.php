<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ContactUs;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\FirebaseService;
class AuthController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
///////////////////////////////////////////  Login  ///////////////////////////////////////////
    public function login_view(){
        return view('dashboard.login');
    }

    public function login(Request $request)
    {   
        $validator  =   Validator::make($request->all(), [
               
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
               
        ]);
            // dd($request->all());
        if ($validator->fails()) {
           
            return Redirect::back()->withErrors($validator)->withInput($request->all());
        }
        if (Auth::attempt(['email' => request('email'),'password' => request('password')])){

            return redirect('/admin-dashboard/home');
        }else{

            return back()->withErrors(['msg' => 'There is something wrong']);
        }
       
    }


///////////////////////////////////////////  Logout  ///////////////////////////////////////////

    public function logout(){
        Auth::logout();
       
       // auth()->guard('admin')->logout();
        return redirect('/admin-dashboard/login');
    }

    public function home(){
        return view('dashboard.home');
    }
    public function change_theme(Request $request){
        $user=auth()->user();
        $user->theme=$request->theme;
        $user->save();
        return $this->sendResponse(null,'success');


    }
    //////////////////////////////////////////////////////////////////////////////////////
    public function privacy_policy(){
        return view('dashboard.privacy_policy');
    }

    public function terms_conditions(){
        $this->firebaseService->sendNotification('cc_Z3kvBRReDBXLTcCHId3:APA91bHJCkxHIxkmGuhDE6s3t0kD97usxx4dKRXB_HcVB_aeHdBi_6HgZofusTIxHB-1q-rwuifPzbBY57ZUCgmulERkM4kkqqpG7fkEwDAj1DzFaJyrMG0',
                                                 'Lady Driver',"شكرا على مجهودك ي ابو حميد معايا",[]);
        return view('dashboard.terms_conditions');
    }

    public function contact_us(){
        return view('dashboard.contact_us');
    }

    public function save_contact_us(Request $request){
       
        $validator  =   Validator::make($request->all(), [
               
            'subject' => ['required', 'string','max:191'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'max:191','email'],
            'phone' => ['required', 'numeric'],
            'message' => ['required','string']
        ]);
            // dd($request->all());
        if ($validator->fails()) {
        
            return Redirect::back()->withErrors($validator)->withInput($request->all());
        }
        ContactUs::create(['subject'=>$request->subject,'name'=>$request->name,'email'=>$request->email,'message'=>$request->message,'phone'=>$request->country_code . $request->phone]);
        return redirect('/');
    }

    public function remove_account(){
        return view('dashboard.remove_account');
    }
}
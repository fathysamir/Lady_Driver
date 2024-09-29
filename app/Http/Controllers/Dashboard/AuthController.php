<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{

///////////////////////////////////////////  Login  ///////////////////////////////////////////
    public function login_view(){
        return view('dashboard.login');
    }

    public function login(Request $request)
    {   
        $validator  =   Validator::make($request->all(), [
               
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8',''],
               
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
}
<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PrivacyAndTerm;
use App\Models\ContactUs;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    ///////////////////////////////////////////  Login  ///////////////////////////////////////////
    public function login_view()
    {
        return view('dashboard.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'email'           => ['required', 'string', 'email'],
            'password'        => ['required', 'string', 'min:8'],
            'second_password' => ['required', 'string', 'min:8'],

        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return Redirect::back()->withErrors($validator)->withInput($request->all());
        }
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')], $request->has('remember'))) {
            $user = Auth::user();
            if (! Hash::check(request('second_password'), $user->password2)) {
                Auth::logout();
                return back()->withErrors(['msg' => 'Second password is incorrect.']);
            }
            $user            = auth()->user();
            $user->is_online = '1';
            $user->save();
            return redirect('/admin-dashboard/home');
        } else {

            return back()->withErrors(['msg' => 'There is something wrong']);
        }

    }

    ///////////////////////////////////////////  Logout  ///////////////////////////////////////////

    public function logout()
    {
        $user            = auth()->user();
        $user->is_online = '0';
        $user->save();
        Auth::logout();

        // auth()->guard('admin')->logout();
        return redirect('/admin-dashboard/login');
    }

    public function home()
    {
        return view('dashboard.home');
    }
    public function change_theme(Request $request)
    {
        $user        = auth()->user();
        $user->theme = $request->theme;
        $user->save();
        return $this->sendResponse(null, 'success');

    }
    //////////////////////////////////////////////////////////////////////////////////////
    public function privacypolicy($lang)
{
    $privacy = PrivacyAndTerm::where('type', 'privacy')
                ->where('lang', $lang)
                ->first();

    $title = $lang == 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy';
    $content = $privacy ? $privacy->value : ''; 

    return view('Website.privacy_policy', compact('privacy','title', 'content'));
}

public function terms_conditions($lang)
{
    $terms = PrivacyAndTerm::where('type', 'terms')
                ->where('lang', $lang)
                ->first();

    $title = $lang == 'ar' ? 'الشروط والأحكام' : 'Terms & Conditions';
    $content = $terms ? $terms->value : ''; 

    return view('Website.terms-conditions', compact('terms','title', 'content'));
}


 //////////////////////////////////////////////////////////////////////////////////////
    public function contact_us()
    {
        return view('dashboard.contact_us');
    }

    public function save_contact_us(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'subject' => ['required', 'string', 'max:191'],
            'name'    => ['required', 'string', 'max:191'],
            'email'   => ['required', 'string', 'max:191', 'email'],
            'phone'   => ['required', 'numeric'],
            'message' => ['required', 'string'],
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return Redirect::back()->withErrors($validator)->withInput($request->all());
        }
        ContactUs::create(['subject' => $request->subject, 'name' => $request->name, 'email' => $request->email, 'message' => $request->message, 'phone' => $request->country_code . $request->phone]);
        return redirect('/');
    }

    public function remove_account()
    {
        return view('dashboard.remove_account');
    }
}

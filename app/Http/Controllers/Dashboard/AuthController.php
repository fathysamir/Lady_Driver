<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            if (! Hash::check(request('second_password'), $user->password2)) {
                Auth::logout();
                return back()->withErrors(['second_password' => 'Second password is incorrect.']);
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
    public function privacy_policy($lang)
    {

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //   CURLOPT_URL => 'https://beta.hypersender.com/api/whatsapp/v1/9e2c5957-7063-4bff-840f-18e0b36473c8/send-text',
        //   CURLOPT_RETURNTRANSFER => true,
        //   CURLOPT_ENCODING => '',
        //   CURLOPT_MAXREDIRS => 10,
        //   CURLOPT_TIMEOUT => 0,
        //   CURLOPT_FOLLOWLOCATION => true,
        //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //   CURLOPT_CUSTOMREQUEST => 'POST',
        //   CURLOPT_POSTFIELDS => '{
        //                         "recipient": "+201125289475",
        //                         "textMessage": {
        //                             "text": "lady driver otp : 125964"
        //                         }
        //                         }',
        //   CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/json',
        //     'Accept: application/json',
        //     'Authorization: Bearer 218|7wDVG6OVhVRPDyGINE4IzWEqCbeL5pZfynDDu16l6d1f614c'
        //   ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);

        // dd($response);
        $supportedLanguages = ['en', 'ar', 'de', 'fr', 'es', 'tr', 'ru', 'zh'];
        if (! in_array($lang, $supportedLanguages)) {
            $lang = 'en';
        }

        // Set the application locale for this request
        App::setLocale($lang);

        // Retrieve the translations
        $title   = __('privacy_policy.title');
        $content = __('privacy_policy.content');

        return view('dashboard.privacy_policy', compact('title', 'content'));
    }

    public function terms_conditions($lang)
    {
        $supportedLanguages = ['en', 'ar', 'de', 'fr', 'es', 'tr', 'ru', 'zh'];
        if (! in_array($lang, $supportedLanguages)) {
            $lang = 'en';
        }

        // Set the application locale for this request
        App::setLocale($lang);

        // Retrieve the translations
        $title   = __('terms_conditions.title');
        $content = __('terms_conditions.content');

        return view('dashboard.terms_conditions', compact('title', 'content'));

    }

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

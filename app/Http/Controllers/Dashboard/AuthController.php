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
use App\Models\Trip;
use App\Models\User;

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

    if ($validator->fails()) {
        return Redirect::back()->withErrors($validator)->withInput($request->all());
    }

    // Attempt with first password only — do NOT pass remember yet
    if (! Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
        return back()->withErrors(['msg' => 'There is something wrong']);
    }

    $user = Auth::user();

    // Verify second password — if it fails, log back out immediately
    if (! Hash::check(request('second_password'), $user->password2)) {
        Auth::logout();
        return back()->withErrors(['msg' => 'Second password is incorrect.'])->withInput($request->only('email'));
    }

    // Both passwords passed — now regenerate session and apply remember-me
    $request->session()->regenerate();

    if ($request->has('remember')) {
        Auth::login($user, true); // sets the remember_token cookie
    }

    $user->is_online = '1';
    $user->save();

    return redirect('/admin-dashboard/home');
}

    ///////////////////////////////////////////  Logout  ///////////////////////////////////////////

    public function logout(Request $request)
{
    $user            = auth()->user();
    $user->is_online = '0';
    $user->save();

    Auth::logout();

    // Invalidate session and regenerate token to prevent session fixation
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/admin-dashboard/login');
}

public function home(Request $request)
{
    if ($request->has('lang')) {
        App::setLocale($request->lang);
        session(['locale' => $request->lang]);
    }

    // ── Total Users — matches ClientController@index exactly
    // mode='client', is_verified=1, no student_code, not soft-deleted
    $totalUsers = User::where('mode', 'client')
                      ->where('is_verified', '1')
                      ->whereNull('student_code')
                      ->whereNull('deleted_at')
                      ->count();

    // ── Total Drivers — matches DriverController@index (no type filter = all drivers)
    // mode='driver', is_verified=1, not soft-deleted
    $totalDrivers = User::where('mode', 'driver')
                        ->where('is_verified', '1')
                        ->whereNull('deleted_at')
                        ->count();

    // ── Total Trips — matches TripController@index (no time_filter = all statuses)
    $totalTrips = Trip::whereIn('status', [
                      'pending', 'scheduled', 'in_progress', 'completed', 'cancelled'
                  ])->count();

    // ── Revenue — completed trips only
    $totalRevenue = Trip::where('status', 'completed')->sum('total_price');

    // ── Recent 10 trips with relationships used in the trips blade
    $recentTrips = Trip::with(['user', 'car', 'car.owner', 'scooter', 'scooter.owner'])
                       ->latest()
                       ->take(10)
                       ->get();

    // ── Bar chart — last 7 days (all statuses)
    $chartLabels = [];
    $chartData   = [];
    for ($i = 6; $i >= 0; $i--) {
        $date          = now()->subDays($i);
        $chartLabels[] = $date->format('D');
        $chartData[]   = Trip::whereDate('created_at', $date)->count();
    }

    // ── Doughnut — matches TripController time_filter status groupings
    $statusData = [
        Trip::where('status', 'completed')->count(),                          // green
        Trip::where('status', 'cancelled')->count(),                          // red
        Trip::where('status', 'in_progress')->count(),                        // yellow
        Trip::whereIn('status', ['pending', 'scheduled'])->count(),           // other
    ];

    return view('dashboard.home', compact(
        'totalUsers', 'totalDrivers', 'totalTrips', 'totalRevenue',
        'recentTrips', 'chartLabels', 'chartData', 'statusData'
    ));
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

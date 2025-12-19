<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Image;

class ClientController extends Controller
{ //done
   
    public function index(Request $request)
{
    $query = User::where('mode', 'client')->with('city:id,name');

    $type = $request->query('type');

    if ($type === 'students') {
        $query->whereNotNull('student_code');
        $title = 'Students';
    } elseif ($type === 'clients') {
        $query->whereNull('student_code');
        $title = 'Clients';
    } else {
        $query->whereNull('student_code');
        $title = 'Clients';
    }

    
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('id', $search);
        });
    }

    if ($request->filled('status')) $query->where('status', $request->status);
    if ($request->filled('city'))   $query->where('city_id', $request->city);

    $query->orderBy('created_at', 'desc')
          ->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

    $all_users = $query->paginate(15)->withQueryString();
    $count = $all_users->total();

    // Transform collection For image adding
    $all_users->setCollection(
        $all_users->getCollection()->transform(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection) ?? 'default-avatar.png';
            return $user;
        })
    );
    $cities = City::all();

    $search = $request->search;
    $status = $request->status;
    $city   = $request->city;

    return view('dashboard.clients.index', compact('all_users',  'count',  'cities',   'search',  'status',  'city', 'title', 'type' ));
}



public function index_archives(Request $request)
{
    $type = $request->query('type');
    
    $query = User::withTrashed()->where('mode', 'client')->whereNotNull('deleted_at');

    if ($type === 'students') {
        $query->whereNotNull('student_code');
        $title = 'Deleted Students';
    } else {
        $query->whereNull('student_code');
        $title = 'Deleted Clients';
    }

    // Apply search filter if provided
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('id', $search);
        });
    }

    $query->orderBy('created_at', 'desc')
          ->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

    $all_users = $query->paginate(12)->withQueryString();
    $count = $all_users->total();

    // Transform collection For image adding
    $all_users->getCollection()->transform(function ($user) {
        $user->image = getFirstMediaUrl($user, $user->avatarCollection);
        return $user;
    });

    $search = $request->search;

    return view('dashboard.clients.index_archives', compact('all_users', 'count', 'search', 'type', 'title'));
}


    public function edit($id, Request $request)
    {
        $user       = User::where('id', $id)->first();
        $user->seen = '1';
        $user->save();
        $user->image       = getFirstMediaUrl($user, $user->avatarCollection);
        $user->rate        = round(Trip::where('user_id', $id)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate')) ?? 0.00;
        $user->trips_count = Trip::where('user_id', $id)->whereIn('status', ['pending', 'in_progress', 'completed'])->count();
        $queryString       = $request->query();
 $cities      = City::all();
        return view('dashboard.clients.edit', compact('user', 'queryString','cities'));
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'status'       => ['required'],
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'country_code' => 'required',
            'phone'        => [
                'required',
                Rule::unique('users')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                        ->whereNull('deleted_at');
                }),
            ],
            'birth_date'   => 'nullable|date',
             'city'         => [
                'nullable',
                'exists:cities,id',
            ],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        User::where('id', $id)->update(['status' => $request->status,
            'email'                                  => $request->email,
            'phone'                                  => $request->phone,
            'country_code'                           => $request->country_code,
            'birth_date'                             => $request->birth_date,
            'city_id'                                => $request->city,
        ]);
        $user=User::where('id',$id)->first();
        if( $request->status == 'blocked'){
             $user->tokens()->delete();
        }
        $queryParams = $request->except(['_token', '_method', 'status', 'email', 'phone', 'country_code', 'birth_date']);

        return redirect()->route('clients', $queryParams)->with('success', 'User updated successfully!');

        // return redirect($request->fullUrlWithQuery([
        //                                     'page' => $request->input('page', 1)
        //                                 ]));
        // return redirect('/admin-dashboard/clients');

    }

    public function delete($id,Request $request)
    {
        $user=User::where('id', $id)->first();
        $user->tokens()->delete();
        $user->delete();
        return redirect()->route('clients', $request->query())
        ->with('success', 'Client deleted successfully.');   
    }
    public function restore($id)
    {
        User::withTrashed()->where('id', $id)->update(['deleted_at' => null]);
        return redirect('/admin-dashboard/archived-clients');
    }
}

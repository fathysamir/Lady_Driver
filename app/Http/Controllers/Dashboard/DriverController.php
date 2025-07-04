<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\City;
use App\Models\DriverLicense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Image;

class DriverController extends Controller
{ //done
    public function index(Request $request)
    {
        $all_users = User::where('mode', 'driver')->orderBy('created_at', 'desc')->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

        if ($request->has('search') && $request->search != null) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status != null) {
            $all_users->where('status', $request->status);
        }
        if ($request->has('city') && $request->city != null) {
            $all_users->where('city_id', $request->city);
        }
        if ($request->has('level') && $request->level != null) {
            $all_users->where('level', $request->level);
        }
        $count     = $all_users->count();
        $all_users = $all_users->paginate(12);

        $all_users->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });
        $cities = City::all();
        $search = $request->search;
        $status = $request->status;
        $city   = $request->city;
        $level  = $request->level;
        return view('dashboard.drivers.index', compact('all_users', 'cities', 'status', 'count', 'city', 'search', 'level'));

    }

    public function index_archives(Request $request)
    {
        // Start with all users, including soft deleted ones
        $all_users = User::withTrashed()->where('mode', 'driver')->orderBy('created_at', 'desc')->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

        // Apply search filter if provided
        if ($request->has('search') && $request->search != null) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }
        // Only include soft deleted users
        $all_users->whereNotNull('deleted_at');
        $count = $all_users->count();
        // Paginate the results
        $all_users = $all_users->paginate(12);

        // Transform the user collection to add the 'image' key
        $all_users->getCollection()->transform(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });

        $search = $request->search;

        return view('dashboard.drivers.index_archives', compact('all_users', 'count', 'search'));
    }

    public function edit($id, Request $request)
    {
        $user       = User::where('id', $id)->first();
        $user->seen = '1';
        $user->save();
        $user->image           = getFirstMediaUrl($user, $user->avatarCollection);
        $user->IDfrontImage    = getFirstMediaUrl($user, $user->IDfrontImageCollection);
        $user->IDbackImage     = getFirstMediaUrl($user, $user->IDbackImageCollection);
        $user->rate            = round(Trip::where('user_id', $id)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate')) ?? 0.00;
        $user->trips_count     = Trip::where('user_id', $id)->whereIn('status', ['pending', 'in_progress', 'completed'])->count();
        $user->driving_license = DriverLicense::where('user_id', $user->id)->first();
        $user->car             = Car::where('user_id', $user->id)->first();
        if ($user->driving_license) {
            $user->driving_license->front_image = getFirstMediaUrl($user->driving_license, $user->driving_license->LicenseFrontImageCollection);
            $user->driving_license->back_image  = getFirstMediaUrl($user->driving_license, $user->driving_license->LicenseBackImageCollection);
        }
        $user->rate = round(Trip::whereHas('car', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate')) ?? 0.00;
        $user->trips_count = Trip::whereHas('car', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('status', ['pending', 'in_progress', 'completed'])->count();

        $queryString = $request->query();
        return view('dashboard.drivers.edit', compact('user', 'queryString'));
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
            'address'      => 'nullable',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        User::where('id', $id)->update(['status' => $request->status,
            'email'                                  => $request->email,
            'phone'                                  => $request->phone,
            'country_code'                           => $request->country_code,
            'birth_date'                             => $request->birth_date,
            'national_id'                            => $request->national_id,
        ]);
        $car = Car::where('user_id', $id)->first();
        if ($car) {
            $car->status = $request->status == 'banned' ? 'blocked' : $request->status;
            $car->save();
        }
        $queryParams = $request->except(['_token', '_method', 'status', 'email', 'phone', 'country_code', 'birth_date', 'national_id']);
        return redirect()->route('drivers', $queryParams)->with('success', 'Driver updated successfully!');

        //return redirect('/admin-dashboard/drivers');

    }

    public function delete($id,Request $request)
    {
        User::where('id', $id)->delete();
           return redirect()->route('drivers', $request->query())
        ->with('success', 'Driver deleted successfully.'); 
    }
    public function restore($id)
    {
        User::withTrashed()->where('id', $id)->update(['deleted_at' => null]);
        return redirect('/admin-dashboard/archived-drivers');
    }
}

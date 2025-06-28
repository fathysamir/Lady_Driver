<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
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
        $all_users = User::where('mode', 'client')->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci")
            ->orderBy('created_at', 'desc');

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
        $count     = $all_users->count();
        $all_users = $all_users->with('city:id,name')->get()->map(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });

        $search = $request->search;
        $status = $request->status;

        return view('dashboard.clients.index', compact('all_users', 'count', 'search','status'));

    }

    public function index_archives(Request $request)
    {
        // Start with all users, including soft deleted ones
        $all_users = User::withTrashed()->where('mode', 'client')->orderBy('id', 'desc');

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

        return view('dashboard.clients.index_archives', compact('all_users', 'count', 'search'));
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

        return view('dashboard.clients.edit', compact('user', 'queryString'));
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
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        User::where('id', $id)->update(['status' => $request->status,
            'email'                                  => $request->email,
            'phone'                                  => $request->phone,
            'country_code'                           => $request->country_code,
            'birth_date'                             => $request->birth_date,
        ]);
        $queryParams = $request->except(['_token', '_method', 'status', 'email', 'phone', 'country_code', 'birth_date']);

        return redirect()->route('clients', $queryParams)->with('success', 'User updated successfully!');

        // return redirect($request->fullUrlWithQuery([
        //                                     'page' => $request->input('page', 1)
        //                                 ]));
        // return redirect('/admin-dashboard/clients');

    }

    public function delete($id)
    {
        User::where('id', $id)->delete();
        return redirect('/admin-dashboard/clients');
    }
    public function restore($id)
    {
        User::withTrashed()->where('id', $id)->update(['deleted_at' => null]);
        return redirect('/admin-dashboard/archived-clients');
    }
}

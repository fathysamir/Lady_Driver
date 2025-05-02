<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Trip;
use App\Models\Car;
use App\Models\DriverLicense;
use Image;
use Str;
use File;

class ClientController extends Controller
{//done
    public function index(Request $request)
    {
        $all_users = User::where('mode','client')->orderBy('id', 'desc');

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
        
        $all_users = $all_users->paginate(2);

        $all_users->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });
        $search = $request->search;
        return view('dashboard.clients.index', compact('all_users', 'search'));

    }

    public function index_archives(Request $request)
    {
        // Start with all users, including soft deleted ones
        $all_users = User::withTrashed()->where('mode','client')->orderBy('id', 'desc');

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

        // Paginate the results
        $all_users = $all_users->paginate(12);

        // Transform the user collection to add the 'image' key
        $all_users->getCollection()->transform(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });

        $search = $request->search;
       
        return view('dashboard.clients.index_archives', compact('all_users', 'search'));
    }



    public function edit($id,Request $request)
    {
        $user = User::where('id', $id)->first();
        $user->seen = '1';
        $user->save();
        $user->image = getFirstMediaUrl($user, $user->avatarCollection);
        $user->rate = round(Trip::where('user_id', $id)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate')) ?? 0.00;
        $user->trips_count = Trip::where('user_id', $id)->whereIn('status', ['pending', 'in_progress','completed'])->count();
        $queryString = $request->query();
       
        return view('dashboard.clients.edit', compact('user','queryString'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'phone' => $request->country_code . $request->phone
        ]);
        $validator = Validator::make($request->all(), [
            'status' => ['required'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at')
            ],
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($id)->whereNull('deleted_at')
            ],
             'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        User::where('id', $id)->update([ 'status' => $request->status,
                                         'email'=>$request->email,
                                         'phone'=>$request->phone,
                                         'country_code'=>$request->country_code,
                                         'birth_date'=>$request->birth_date                              
                                        ]);
        $queryParams = $request->except(['_token', '_method','status','email','phone','country_code','birth_date']);
        
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
    public function restore($id){
        User::withTrashed()->where('id',$id)->update(['deleted_at'=>null]);
        return redirect('/admin-dashboard/archived-clients');
    }
}

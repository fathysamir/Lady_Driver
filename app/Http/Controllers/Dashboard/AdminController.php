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

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $all_admins = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Admin');
        })->orderBy('id', 'desc');

        if ($request->has('search') && $request->search != null) {
            $all_admins->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }
        $all_admins = $all_admins->paginate(12);

        $all_admins->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });
        $search = $request->search;
        return view('dashboard.admins.index', compact('all_admins', 'search'));

    }

    public function create()
    {
        return view('dashboard.admins.create');
    }

    public function store(Request $request)
    {

        $request->merge([
            'phone' => $request->country_code . $request->phone
        ]);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'min:8','confirmed'],
            'phone' => ['nullable', Rule::unique('users', 'phone')->whereNull('deleted_at')]
        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email ,
            'phone' => $request->phone,
            'password' =>  Hash::make($request->password),
            'status' => 'confirmed',
            'theme' => 'theme1',
            'gendor' => 'other',
            'mode' => 'admin'
        ]);
        $role = Role::where('Name', 'Admin')->first();

        $admin->assignRole([$role->id]);
        if ($request->file('image')) {
            uploadMedia($request->file('image'), $admin->avatarCollection, $admin);
        }
        return redirect('/admin-dashboard/admins');

    }

    public function edit($id)
    {
        $admin=User::findOrFail($id);
        return view('dashboard.admins.edit',compact('admin'));
    }

    public function update(Request $request,$id)
    {

        $request->merge([
            'phone' => $request->country_code . $request->phone
        ]);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id)->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'string', 'min:8','confirmed'],
            'phone' => ['nullable', Rule::unique('users', 'phone')->ignore($id)->whereNull('deleted_at')]
        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $admin = User::where('id',$id)->update([
            'name' => $request->name,
            'email' => $request->email ,
            'phone' => $request->phone,
        ]);
        $admin=User::findOrFail($id);
        if($request->password != null){
            $admin->password=Hash::make($request->password);
        }
        
        if ($request->file('image')) {
            uploadMedia($request->file('image'), $admin->avatarCollection, $admin);
        }
        return redirect('/admin-dashboard/admins');

    }


}

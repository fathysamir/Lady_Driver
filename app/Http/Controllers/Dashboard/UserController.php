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

class UserController extends Controller
{//done
    public function index(Request $request)
    {
        $all_users = User::orderBy('id', 'desc');

        if ($request->has('search') && $request->search!=null ) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                     ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->has('mode')&& $request->mode!=null) {
            $all_users->where('mode', $request->mode);
        }
    
        if ($request->has('status')&& $request->status!=null) {
            $all_users->where('status', $request->status);
        }
        if ($request->has('role') && $request->role != null) {
            $role = Role::where('name', $request->role)->first();
            
            if ($role) {
                $all_users->whereHas('roles', function ($query) use ($role) {
                    $query->where('roles.id', $role->id);
                });
            }
        }
        $all_users = $all_users->paginate(12);

        $all_users->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user,$user->avatarCollection);
            return $user;
        });
         $search=$request->search;
        return view('dashboard.users.index',compact('all_users','search'));

    }

    public function index_archives(Request $request){
          // Start with all users, including soft deleted ones
        $all_users = User::withTrashed()->orderBy('id', 'desc');

        // Apply search filter if provided
        if ($request->has('search') && $request->search != null) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Apply mode filter if provided
        if ($request->has('mode') && $request->mode != null) {
            $all_users->where('mode', $request->mode);
        }
        // Apply role filter if provided
        if ($request->has('role') && $request->role != null) {
            $role = Role::where('name', $request->role)->first();
            
            if ($role) {
                $all_users->whereHas('roles', function ($query) use ($role) {
                    $query->where('roles.id', $role->id);
                });
            }
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
dd($all_users->first());
        return view('dashboard.users.index_archives', compact('all_users', 'search'));
    }

    // public function create(){
    //     return view('dashboard.users.create');
    // }

    // public function store(Request $request){

    //         $validator = Validator::make($request->all(), [
    //             'first_name' => ['required', 'string', 'max:191'],
    //             'last_name' => ['required', 'string', 'max:191'],
    //             'email' => ['required', 'string', 'email', 'max:191', 'unique:users'],
    //             'password' => ['required', 'string', 'min:8','confirmed'],
    //             'salary' => ['required'],
    //             'manager' => ['nullable'],
    //             'department'=>['required' , Rule::in(Department::pluck('id'))],
    //             'image' => ['required'] ,
    //             'phone_number' => ['nullable', 'unique:users,phone', 'numeric'],
    //             'role'=>['required',Rule::in(Role::pluck('id'))]
                

    //         ]);

           
    //         if ($validator->fails()) {
    //             return Redirect::back()->withInput()->withErrors($validator);
    //         }
            
    //         $user = User::create([
    //             'first_name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'email'=> $request->email ,
    //             'phone'=>$request->phone_number,
    //             'salary'=> $request->salary,
    //             'password'=>  Hash::make($request->password),
    //             'manager_id'=>$request->manager?$request->manager:null,
    //             'department_id'=>$request->department,
    //             'theme'=>'theme1'
                
    //         ]);
    //         $role = Role::where('id',$request->role)->first();
            
    //         $user->assignRole([$role->id]);
    //         if($request->file('image')){
    //             uploadMedia($request->file('image'),$user->avatarCollection,$user);
    //         }
    //       return redirect('/users');

    // }
 

    public function edit($id){
        $user=User::where('id',$id)->first();
        $user->seen='1';
        $user->save();
        $user->image = getFirstMediaUrl($user,$user->avatarCollection);
        $user->driving_license=DriverLicense::where('user_id',$user->id)->first();
        $user->car=Car::where('user_id',$user->id)->first();
        if($user->driving_license){
            $user->driving_license->front_image=getFirstMediaUrl($user->driving_license,$user->driving_license->LicenseFrontImageCollection);
            $user->driving_license->back_image=getFirstMediaUrl($user->driving_license,$user->driving_license->LicenseBackImageCollection);
        }
        if($user->mode=='client'){
            $user->rate=round(Trip::where('user_id',$id)->where('status','completed')->where('driver_stare_rate','>',0)->avg('driver_stare_rate'))?? 0.00;
            $user->trips_count=Trip::where('user_id',$id)->whereIn('status',['pending', 'in_progress','completed'])->count();
        }elseif($user->mode=='driver'){
            $user->rate=round(Trip::whereHas('car', function ($query)use($user) {
                $query->where('user_id', $user->id);
            })->where('status','completed')->where('client_stare_rate','>',0)->avg('client_stare_rate'))?? 0.00;
            $user->trips_count=Trip::whereHas('car', function ($query)use($user) {
                $query->where('user_id', $user->id);
            })->whereIn('status',['pending', 'in_progress','completed'])->count();
        }
        return view('dashboard.users.edit',compact('user'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        
        User::where('id',$id)->update([ 'status' => $request->status]);
        return redirect('/admin-dashboard/users');

    }


   

     public function delete($id)
    {
        Car::where('user_id',$id)->delete();
        User::where('id', $id)->delete();
        return redirect('/admin-dashboard/users');
    }
}
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
use App\Models\DriverLicense;
use Image;
use Str;
use File;

class UserController extends Controller
{//done
    public function index(Request $request)
    {
        $all_users = User::orderBy('id', 'desc');

        if ($request->has('search')) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->has('mode')) {
            $all_users->where('mode', $request->mode);
        }
    
        if ($request->has('status')) {
            $all_users->where('status', $request->status);
        }
        $all_users = $all_users->paginate(12);

        $all_users->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user,$user->avatarCollection);
            return $user;
        });
         
        return view('dashboard.users.index',compact('all_users'));

    }

    public function create(){
        return view('dashboard.users.create');
    }

    public function store(Request $request){

            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:191'],
                'last_name' => ['required', 'string', 'max:191'],
                'email' => ['required', 'string', 'email', 'max:191', 'unique:users'],
                'password' => ['required', 'string', 'min:8','confirmed'],
                'salary' => ['required'],
                'manager' => ['nullable'],
                'department'=>['required' , Rule::in(Department::pluck('id'))],
                'image' => ['required'] ,
                'phone_number' => ['nullable', 'unique:users,phone', 'numeric'],
                'role'=>['required',Rule::in(Role::pluck('id'))]
                

            ]);

           
            if ($validator->fails()) {
                return Redirect::back()->withInput()->withErrors($validator);
            }
            
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email'=> $request->email ,
                'phone'=>$request->phone_number,
                'salary'=> $request->salary,
                'password'=>  Hash::make($request->password),
                'manager_id'=>$request->manager?$request->manager:null,
                'department_id'=>$request->department,
                'theme'=>'theme1'
                
            ]);
            $role = Role::where('id',$request->role)->first();
            
            $user->assignRole([$role->id]);
            if($request->file('image')){
                uploadMedia($request->file('image'),$user->avatarCollection,$user);
            }
          return redirect('/users');

    }
 

    public function edit($id){
        $user=User::where('id',$id)->first();
        $user->image = getFirstMediaUrl($user,$user->avatarCollection);
        $user->driving_license=DriverLicense::where('user_id',$user->id)->first();
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
        User::where('id', $id)->delete();
        return redirect('/admin-dashboard/users');
    }
}
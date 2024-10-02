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
use App\Models\Car;
use App\Models\CarMark;
use App\Models\Trip;
use App\Models\CarModel;
use Image;
use Str;
use File;

class TripController extends Controller
{//done
    public function index(Request $request)
    {
        $all_trips = Trip::orderBy('id', 'desc');

        if ($request->has('search') && $request->search!=null ) {
            $all_trips->where(function ($query) use ($request) {
                $query->where('car_plate', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('color', 'LIKE', '%' . $request->search . '%');
                   
            });
        }
        // if ($request->has('user')&& $request->user!=null) {
        //     $all_cars->where('user_id', $request->user);
        // }
    
        // if ($request->has('mark')&& $request->mark!=null) {
        //     $all_cars->where('car_mark_id', $request->mark);
        // }
        // if ($request->has('model')&& $request->model!=null) {
        //     $all_cars->where('car_model_id', $request->model);
        // }
        // if ($request->has('year')&& $request->year!=null) {
        //     $all_cars->where('year', $request->year);
        // }
        // if ($request->has('status')&& $request->status!=null) {
        //     $all_cars->where('status', $request->status);
        // }
        // if ($request->has('air_conditioned')&& $request->air_conditioned!=null) {
        //     $all_cars->where('air_conditioned', 1);
        // }
        
        // $all_cars = $all_cars->paginate(12);
        // $users=User::whereHas('roles', function ($query) {
        //     $query->where('roles.name', 'Client');
        // })->where('mode','driver')->get();
        // $car_marks=CarMark::all();
        // return view('dashboard.cars.index',compact('all_cars','users','car_marks'));

    }
    public function getModels(Request $request)
    {
        $markId = $request->input('markId');
        $models = CarModel::where('car_mark_id', $markId)->get();
    
        return response()->json($models);
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
        $car=Car::where('id',$id)->first();
        $car->image = getFirstMediaUrl($car,$car->avatarCollection);
        $car->plate_image = getFirstMediaUrl($car,$car->PlateImageCollection);
        $car->license_front_image = getFirstMediaUrl($car,$car->LicenseFrontImageCollection);
        $car->license_back_image = getFirstMediaUrl($car,$car->LicenseBackImageCollection);
        return view('dashboard.cars.edit',compact('car'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        
        Car::where('id',$id)->update([ 'status' => $request->status]);
        return redirect('/admin-dashboard/cars');

    }


   

     public function delete($id)
    {
        Car::where('id', $id)->delete();
        return redirect('/admin-dashboard/cars');
    }
}
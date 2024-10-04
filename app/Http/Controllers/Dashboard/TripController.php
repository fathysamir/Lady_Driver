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
        //dd($request->all());
        $all_trips = Trip::orderBy('id', 'desc')->whereIn('status',['pending','in_progress','completed','cancelled']);

        if($request->has('search') && $request->search!=null ) {
            $all_trips->where(function ($query) use ($request) {
                $query->where('code', 'LIKE', '%' . $request->search . '%');
            });
        }
        if($request->has('user')&& $request->user!=null) {
            $all_trips->where('user_id', $request->user);
        }
        // if ($request->has('car')&& $request->car!=null) {
        //     $all_trips->where('car_id', $request->user);
        // }
    
        if($request->has('created_date')&& $request->created_date!=null) {
            $all_trips->whereDate('created_at', $request->created_date);
        }
        if($request->has('type')&& $request->type!=null) {
            $all_trips->where('type', $request->type);
        }
        if($request->has('payment_status')&& $request->payment_status!=null) {
            $all_trips->where('payment_status', $request->payment_status);
        }
        if ($request->has('status')&& $request->status!=null) {
            $all_trips->where('status', $request->status);
        }
        if($request->has('air_conditioned')&& $request->air_conditioned!=null) {
            $all_trips->where('air_conditioned', '1');
        }
        if($request->has('driver')&& $request->driver!=null) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('user_id', $request->driver);
            });
        }
        if($request->has('mark')&& $request->mark!=null) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_mark_id', $request->mark);
            });
        }
        if($request->has('model')&& $request->model!=null) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_model_id', $request->model);
            });
        }
        $all_trips = $all_trips->paginate(12);
         $drivers=User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->where('mode','driver')->get();
        $users=User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->get();
        $car_marks=CarMark::all();

        // $car_marks=CarMark::all();
        return view('dashboard.trips.index',compact('all_trips','drivers','users','car_marks'));

    }


    public function view($id){
        $trip=Trip::where('id',$id)->first();
        $trip->user->image=getFirstMediaUrl($trip->user,$trip->user->avatarCollection);
        $trip->car->image=getFirstMediaUrl($trip->car,$trip->car->avatarCollection);
        $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
        return view('dashboard.trips.view',compact('trip'));
    }

    
}
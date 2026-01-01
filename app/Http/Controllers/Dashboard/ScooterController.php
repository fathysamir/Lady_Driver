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
use App\Models\Scooter;
use App\Models\MotorcycleModel;
use App\Models\MotorcycleMark;
use Image;
use Str;
use File;

class ScooterController extends Controller
{//done
   
    public function getModels(Request $request)
    {
        $markId = $request->input('markId');
        $models = MotorcycleModel::where('motorcycle_mark_id', $markId)->get();

        return response()->json($models);
    }
    


    public function edit($id)
    {
        $scooter = Scooter::where('id', $id)->first();
        $scooter->image = getFirstMediaUrl($scooter, $scooter->avatarCollection);
        $scooter->plate_image = getFirstMediaUrl($scooter, $scooter->PlateImageCollection);
        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);
        return view('dashboard.scooter.edit', compact('scooter'));
    }

    // public function update(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'status' => ['required'],
    //     ]);

    //     if ($validator->fails()) {
    //         return Redirect::back()->withInput()->withErrors($validator);
    //     }

    //     Scooter::where('id', $id)->update([ 'status' => $request->status]);
    //     return redirect('/admin-dashboard/cars');

    // }




    
    public function getLocation($id)
    {
        $scooter = Scooter::find($id);

        return response()->json([
            'lat' => $scooter->lat,
            'lng' => $scooter->lng,
        ]);
    }
}

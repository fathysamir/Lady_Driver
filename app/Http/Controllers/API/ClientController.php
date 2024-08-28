<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\Car;
use App\Models\DriverLicense;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ClientController extends ApiController
{
    public function create_trip(Request $request){
        $validator  =   Validator::make($request->all(), [
            'car_id' => [
                 'required',
                 Rule::exists('cars', 'id') 
             ],
             'start_lat' => 'required',
             'start_lng' => 'required',
             'end_lat' => 'required',
             'end_lng' => 'required',
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }
         $distance=calculate_distance($request->start_lat,$request->start_lng,$request->end_lat,$request->end_lng);
         $kilometer_price=floatval(Setting::where('key','kilometer_price')->where('category','Trips')->where('type','number')->first()->value);
         $Air_conditioning_service_price=floatval(Setting::where('key','Air_conditioning_service_price')->where('category','Trips')->where('type','number')->first()->value);
         $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
         $driver_rate=round(($distance*$kilometer_price)+$Air_conditioning_service_price ,2);
         $app_rate=round(($distance*$kilometer_price*$app_ratio)/100 ,2);
         $total_price=$driver_rate+$app_rate;

         //dd($distance);

    }
}
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\Car;
use App\Models\DriverLicense;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Offer;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ClientController extends ApiController
{
    public function create_trip(Request $request){
        $validator  =   Validator::make($request->all(), [
             'start_lat' => 'required',
             'start_lng' => 'required',
             'end_lat' => 'required',
             'end_lng' => 'required',
             'type' => 'required',
             'air_conditioned' => 'nullable|boolean',
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }
         $distance=calculate_distance($request->start_lat,$request->start_lng,$request->end_lat,$request->end_lng);
         $kilometer_price=floatval(Setting::where('key','kilometer_price')->where('category','Trips')->where('type','number')->first()->value);
         $Air_conditioning_service_price=floatval(Setting::where('key','Air_conditioning_service_price')->where('category','Trips')->where('type','number')->first()->value);
         $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
         if($request->air_conditioned==1){
            $driver_rate=round(($distance*$kilometer_price)+$Air_conditioning_service_price ,2);
        }else{
            $driver_rate=round($distance*$kilometer_price ,2);
        }
         $app_rate=round(($distance*$kilometer_price*$app_ratio)/100 ,2);
         $total_price=$driver_rate+$app_rate;
         
         $trip=Trip::create(['user_id'=>auth()->user()->id,
                             'car_id'=>$request->car_id,
                             'start_lat'=>floatval($request->start_lat),
                             'start_lng'=>floatval($request->start_lng),
                             'end_lat'=>floatval($request->end_lat),
                             'end_lng'=>floatval($request->end_lng),
                             'total_price'=>$total_price,
                             'app_rate'=>$app_rate,
                             'driver_rate'=>$driver_rate,
                             'distance'=>$distance,
                             'type'=>$request->type
        ]);
        if($request->air_conditioned=='1'){
            $trip->air_conditioned='1';
        }else{
            $trip->air_conditioned='0';
        }
        $trip->save();
        return $this->sendResponse($trip,'Trip Created Successfuly.',200);
         //dd($distance);

    }

    public function expire_trip($id){
        $trip=Trip::find($id);
        $trip->status='expired';
        $trip->save();
        Offer::where('trip_id',$trip->id)->update(['status'=>'expired']);
        return $this->sendResponse(null,'Trip is expired',200);
    }
}
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
                             'address1' => $request->address1,
                             'address2' => $request->address2,
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
    
    public function current_trip(){
        $trip=Trip::where('user_id',auth()->user()->id)->whereIn('status',['created','pending', 'in_progress'])->with(['car' => function($query) {
            $query->with(['mark:id,name','model:id,name','owner']);
        }])->first();
        
        if($trip){
            if($trip->status=='pending' || $trip->status=='in_progress'){
                $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
            }
            if($trip->status=='created'){
                $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
                $pendingOffers = $trip->offers()->where('status', 'pending')->get()->map(function ($offer) use ($trip,$app_ratio) {
                        $offer->offer=round(($offer->offer-$trip->driver_rate)+(($offer->offer-$trip->driver_rate)*$app_ratio/100)+$trip->total_price , 2);
                        $offer->user->image=getFirstMediaUrl($offer->user,$offer->user->avatarCollection);
                        return $offer;
                    
                });
                $trip->offers=$pendingOffers;
            }
            return $this->sendResponse($trip,null,200);
        }else{
            return $this->sendError(null,'no current trip existed',400);

        }
        
    }

    public function accept_offer(Request $request){
        $validator  =   Validator::make($request->all(), [
            'offer_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $offer = Offer::where('id', $value)->where('status', 'pending')->first();
                    if (!$offer) {
                        $fail('The selected offer is not pending.');
                    }
                }
            ],
            'status' => 'required'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $offer=Offer::find($request->offer_id);
        $trip=$offer->trip;
        $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
        $trip->status='pending';
        $trip->total_price=round(($offer->offer-$trip->driver_rate)+(($offer->offer-$trip->driver_rate)*$app_ratio/100)+$trip->total_price , 2);
        $trip->driver_rate=$offer->offer;
        $trip->app_rate=round(($offer->offer-$trip->driver_rate)+(($offer->offer-$trip->driver_rate)*$app_ratio/100)+$trip->total_price , 2)-$offer->offer;
        $trip->car_id=$offer->car_id;
        $trip->save();
        $offer->status='accepted';
        $offer->save();
        Offer::where('id','!=',$request->offer_id)->where('trip_id',$trip->id)->update(['status'=>'expired']);
        
        return $this->sendResponse(null,'offer accepted successfuly',200);

    }

    public function pay_trip(Request $request){
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')
            ],
            'status' => 'required'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $trip=Trip::find($request->trip_id);
        $trip->payment_status=$request->status;
        $trip->save();
        return $this->sendResponse(null,'trip is paid',200);
    }

    public function completed_trips(){
        $completed_trips=Trip::where('user_id',auth()->user()->id)->where('status','completed')->with(['car' => function($query) {
            $query->with(['mark:id,name','model:id,name','owner']);
        }])->get()->map(function ($trip) {
            $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
            return $trip;
        
        });

        return $this->sendResponse($completed_trips,null,200);
    }

    public function cancelled_trips(){
        $cancelled_trips=Trip::where('user_id',auth()->user()->id)->where('status','cancelled')->with(['car' => function($query) {
            $query->with(['mark:id,name','model:id,name','owner']);
        },'cancelled_by'])->get()->map(function ($trip) {
            $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
            return $trip;
        
        });

        return $this->sendResponse($cancelled_trips,null,200);
    }

    public function rate_trip(Request $request){
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')
            ],
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $trip=Trip::find($request->trip_id);
        if(auth()->user()->mode=='client'){
            $trip->client_stare_rate=floatval($request->rating);
            $trip->client_comment=$request->comment;
        }elseif(auth()->user()->mode=='driver'){
            $trip->driver_stare_rate=floatval($request->rating);
            $trip->driver_comment=$request->comment;
        }
        $trip->save();
        return $this->sendResponse(null,'trip rating saved successfuly',200);
    }

    public function cancell_trip(Request $request){
        
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')
            ],
           
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $trip=Trip::find($request->trip_id);
        $trip->status='cancelled';
        $trip->cancelled_by_id=auth()->user()->id;
        $trip->save();
        return $this->sendResponse(null,'trip cancelled successfuly',200);

    }
}
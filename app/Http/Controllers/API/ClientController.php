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
use App\Models\Suggestion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use App\Models\Notification;
use App\Models\Complaint;
use App\Models\TripCancellingReason;
use Illuminate\Validation\Rule;
use App\Services\FirebaseService;

use Illuminate\Support\Facades\Validator;

class ClientController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function create_trip(Request $request){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
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
         $response=calculate_distance($request->start_lat,$request->start_lng,$request->end_lat,$request->end_lng);
         $distance=$response['distance_in_km'];
         $duration=$response['duration_in_M'];
         
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

         $lastTrip = Trip::orderBy('id', 'desc')->first();

            if ($lastTrip) {
                $lastCode = $lastTrip->code;
                $code = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $code = 'TRP-000001';
            }
         $trip=Trip::create(['user_id'=>auth()->user()->id,
                             'code'=>$code,
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
        if($request->animal=='1'){
            $trip->animals='1';
        }else{
            $trip->animals='0';
        }

           
        $trip->save();

        $trip->duration=$duration;

        $radius = 6371;
        $decimalPlaces = 2;

        $eligibleCars = Car::where('status', 'confirmed')
                ->whereHas('owner', function ($query) {
                    $query->where('is_online', '1');
                })
        ->where(function ($query) use ($trip) {
            if ($trip->air_conditioned == '1') {
                $query->where('air_conditioned', '1');
            }
            if ($trip->animals == '1') {
                $query->where('animals', '1');
            }
            if($trip->user->gendor=='Male'){
                $query->where('passenger_type','male_female');
            }
        })
        ->select('*')
        ->selectRaw("ROUND(( $radius * acos( cos( radians($trip->start_lat) ) * cos( radians(lat) ) * cos( radians(lng) - radians($trip->start_lng) ) + sin( radians($trip->start_lat) ) * sin( radians(lat) ) ) ), $decimalPlaces) AS distance")
        ->having('distance', '<=', 3)
        ->get()->map(function ($car) use ($trip) {
            $response=calculate_distance($car->lat,$car->lng,$trip->start_lat,$trip->start_lng);
            $distance=$response['distance_in_km'];
            if($distance <= 3){
                
                return $car;
            }
        });

        foreach ($eligibleCars as $car) {
            if($car->owner->device_token){
                $this->firebaseService->sendNotification($car->owner->device_token,'Lady Driver - New Trip',"There is a new trip created in your current area",["screen"=>"New Trip","ID"=>$trip->id]);
                $data=[
                    "title"=>"Lady Driver - New Trip",
                    "message"=>"There is a new trip created in your current area",
                    "screen"=>"New Trip",
                    "ID"=>$trip->id
                ];
                Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
            }
        }
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
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $trip=Trip::where('user_id',auth()->user()->id)->whereIn('status',['created','pending', 'in_progress'])->with(['car' => function($query) {
            $query->with(['mark','model','owner']);
        }])->first();
        
        if($trip){
            $response=calculate_distance($trip->start_lat,$trip->start_lng,$trip->end_lat,$trip->end_lng);
            $trip_distance=$response['distance_in_km'];
            $trip_duration=$response['duration_in_M'];
            $trip->duration=$trip_duration;
            if($trip->status=='pending' || $trip->status=='in_progress'){
                $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
                $trip->car->image=getFirstMediaUrl($trip->car,$trip->car->avatarCollection);
            }
            if($trip->status=='pending'){
                $response=calculate_distance($trip->car->lat,$trip->car->lng,$trip->start_lat,$trip->start_lng);
                $distance=$response['distance_in_km'];
                $duration=$response['duration_in_M'];
                $trip->client_location_distance=$distance;
                $trip->client_location_duration=$duration;
            }
            if($trip->status=='created'){
                $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
                $pendingOffers = $trip->offers()->where('status', 'pending')->get()->map(function ($offer) use ($trip,$app_ratio) {
                        $offer->offer=round(($offer->offer-$trip->driver_rate)+(($offer->offer-$trip->driver_rate)*$app_ratio/100)+$trip->total_price , 2);
                        $offer->user->image=getFirstMediaUrl($offer->user,$offer->user->avatarCollection);
                        $offer->car->mark;
                        $offer->car->model;
                        $response=calculate_distance($offer->car->lat,$offer->car->lng,$trip->start_lat,$trip->start_lng);
                        $distance=$response['distance_in_km'];
                        $duration=$response['duration_in_M'];
                        $offer->client_location_distance=$distance;
                        $offer->client_location_duration=$duration;
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
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
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
            $query->with(['mark','model','owner']);
        }])->get()->map(function ($trip) {
            $trip->car->owner->image=getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection);
            return $trip;
        
        });

        return $this->sendResponse($completed_trips,null,200);
    }

    public function cancelled_trips(){
        $cancelled_trips=Trip::where('user_id',auth()->user()->id)->where('status','cancelled')->with(['car' => function($query) {
            $query->with(['mark','model','owner']);
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
            'comment' => 'nullable',
            'complaint' =>'nullable',
            'suggestion' => 'nullable'
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
        if($request->complaint!=null){
            Complaint::create(['user_id'=>auth()->user()->id,'trip_id'=>$trip->id,'complaint'=>$request->complaint]);
        }
        if($request->suggestion!=null){
            Suggestion::create(['user_id'=>auth()->user()->id,'suggestion'=>$request->suggestion]);
        }
        return $this->sendResponse(null,'trip rating saved successfuly',200);
    }

    public function cancell_trip(Request $request){
        
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')
            ],

            'reason_id'=>[
                'required',Rule::exists('trip_cancelling_reasons', 'id')
            ]
           
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $trip=Trip::find($request->trip_id);
        $trip->status='cancelled';
        $trip->cancelled_by_id=auth()->user()->id;
        $trip->trip_cancelling_reason_id=$request->reason_id;
        $trip->save();
        return $this->sendResponse(null,'trip cancelled successfuly',200);

    }

    public function cancellation_reasons(Request $request){
        $validator  =   Validator::make($request->all(), [
            'category' => [
                'required'
            ]
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $reasons=TripCancellingReason::where('type',$request->category)->get();
        return $this->sendResponse($reasons,null,200);
    }
}
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\Car;
use App\Models\DriverLicense;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Offer;
use App\Models\CarMark;
use App\Models\CarModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class DriverController extends ApiController
{   
    public function marks(Request $request){
        

        $carMarks = CarMark::all();
        return $this->sendResponse($carMarks,null,200);

    }

    public function models(Request $request){
        $validator  =   Validator::make($request->all(), [
            'car_mark_id' => [
                 'required',
                 Rule::exists('car_marks', 'id') 
             ]
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }
         
        $models=CarModel::where('car_mark_id',$request->car_mark_id)->get();
        return $this->sendResponse($models,null,200);

    }
    
    public function create_car(Request $request){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $validator  =   Validator::make($request->all(), [
           'car_mark_id' => [
                'required',
                Rule::exists('car_marks', 'id') 
            ],
            'car_model_id' => [
                'required',
                Rule::exists('car_models', 'id') 
            ],
            'color' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'car_plate' => 'required|string|max:255',
            'air_conditioned' => 'nullable|boolean',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', 
            'plate_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', 
            'license_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', 
            'license_back_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072',
            'lat' => 'nullable',
            'lng' => 'nullable',
            'license_expire_date'=>'required|date',
            'passenger_type'=>'required|in:female,male_female'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $lastCar = Car::orderBy('id', 'desc')->first();

            if ($lastCar) {
                $lastCode = $lastCar->code;
                $code = 'CAR-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $code = 'CAR-000001';
            }
        $car=Car::create(['user_id'=>auth()->user()->id,
                          'car_mark_id'=>$request->car_mark_id,
                          'code'=>$code,
                          'car_model_id'=>$request->car_model_id,
                          'color'=>$request->color,
                          'year'=>$request->year,
                          'car_plate'=>$request->car_plate,
                          'lat'=>floatval($request->lat),
                          'lng'=>floatval($request->lng),
                          'passenger_type'=>$request->passenger_type,
                          'license_expire_date'=>$request->license_expire_date
                        ]);
        if($request->air_conditioned){
            $car->air_conditioned='1';
        }else{
            $car->air_conditioned='0';
        }

        if($request->animal=='1'){
            $car->animals='1';
        }else{
            $car->animals='0';
        }

        $car->save();
        uploadMedia($request->image,$car->avatarCollection,$car);
        uploadMedia($request->plate_image,$car->PlateImageCollection,$car);
        uploadMedia($request->license_front_image,$car->LicenseFrontImageCollection,$car);
        uploadMedia($request->license_back_image,$car->LicenseBackImageCollection,$car);
        $car->image=getFirstMediaUrl($car,$car->avatarCollection);
        $car->plate_image=getFirstMediaUrl($car,$car->PlateImageCollection);
        $car->license_front_image=getFirstMediaUrl($car,$car->LicenseFrontImageCollection);
        $car->license_back_image=getFirstMediaUrl($car,$car->LicenseBackImageCollection);
        return $this->sendResponse($car,'Car Created Successfuly.',200);
    }

    public function edit_car(Request $request){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $validator  =   Validator::make($request->all(), [
            'car_id' => [
                'required',
                Rule::exists('cars', 'id') 
            ],
            'car_mark_id' => [
                 'required',
                 Rule::exists('car_marks', 'id') 
             ],
             'car_model_id' => [
                 'required',
                 Rule::exists('car_models', 'id') 
             ],
             'color' => 'required|string|max:255',
             'year' => 'required|integer|min:1900|max:' . date('Y'),
             'car_plate' => 'required|string|max:255',
             'air_conditioned' => 'nullable|boolean',
             'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072', 
             'plate_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072', 
             'license_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072', 
             'license_back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
             'lat' => 'nullable',
             'lng' => 'nullable',
             'license_expire_date'=>'required|date'
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }

         Car::where('id',$request->car_id)->update([
                          'car_mark_id'=>$request->car_mark_id,
                          'car_model_id'=>$request->car_model_id,
                          'color'=>$request->color,
                          'year'=>$request->year,
                          'car_plate'=>$request->car_plate,
                          'lat'=>floatval($request->lat),
                          'lng'=>floatval($request->lng),
                          'status' => 'pending',
                          'license_expire_date'=>$request->license_expire_date
                        ]);
        $car=Car::find($request->car_id);
                        
        if($request->air_conditioned=='1'){
            $car->air_conditioned='1';
        }else{
            $car->air_conditioned='0';
        }
        if($request->animal=='1'){
            $car->animals='1';
        }else{
            $car->animals='0';
        }
        $car->save();
        
        if($request->file('image')){
            $image=getFirstMediaUrl($car,$car->avatarCollection);
            if($image!= null){
                deleteMedia($car,$car->avatarCollection);
                uploadMedia($request->image,$car->avatarCollection,$car);
            }else{
                uploadMedia($request->image,$car->avatarCollection,$car);
            }
        }

        if($request->file('plate_image')){
            $plate_image=getFirstMediaUrl($car,$car->PlateImageCollection);
            if($plate_image!= null){
                deleteMedia($car,$car->PlateImageCollection);
                uploadMedia($request->plate_image,$car->PlateImageCollection,$car);
            }else{
                uploadMedia($request->plate_image,$car->PlateImageCollection,$car);
            }
        }

        if($request->file('license_front_image')){
            $license_front_image=getFirstMediaUrl($car,$car->LicenseFrontImageCollection);
            if($license_front_image!= null){
                deleteMedia($car,$car->LicenseFrontImageCollection);
                uploadMedia($request->license_front_image,$car->LicenseFrontImageCollection,$car);
            }else{
                uploadMedia($request->license_front_image,$car->LicenseFrontImageCollection,$car);
            }
        }

        if($request->file('license_back_image')){
            $license_back_image=getFirstMediaUrl($car,$car->LicenseBackImageCollection);
            if($license_back_image!= null){
                deleteMedia($car,$car->LicenseBackImageCollection);
                uploadMedia($request->license_back_image,$car->LicenseBackImageCollection,$car);
            }else{
                uploadMedia($request->license_back_image,$car->LicenseBackImageCollection,$car);
            }
        }
        $car=Car::find($request->car_id);
        $car->image=getFirstMediaUrl($car,$car->avatarCollection);
        $car->plate_image=getFirstMediaUrl($car,$car->PlateImageCollection);
        $car->license_front_image=getFirstMediaUrl($car,$car->LicenseFrontImageCollection);
        $car->license_back_image=getFirstMediaUrl($car,$car->LicenseBackImageCollection);
        return $this->sendResponse($car,'Car Updated Successfuly.',200);
    }

    public function car(Request $request){
        $acceptedLanguage = $request->header('Accept-Language');
        $car=Car::where('user_id',auth()->user()->id)->with(['owner:id,name','mark','model'])->first();
        if(!$car){
            return $this->sendError(null,"You don't create your cat yet",400);
        }
        
      
        $car->image=getFirstMediaUrl($car,$car->avatarCollection);
        $car->plate_image=getFirstMediaUrl($car,$car->PlateImageCollection);
        $car->license_front_image=getFirstMediaUrl($car,$car->LicenseFrontImageCollection);
        $car->license_back_image=getFirstMediaUrl($car,$car->LicenseBackImageCollection);

        return $this->sendResponse($car,null,200);
    }

    public function add_driving_license(Request $request){
        $validator  =   Validator::make($request->all(), [
            
             'license_number' => 'required',
             'license_expire_date'=>'required|date',
             'license_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', 
             'license_back_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072',
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }
         $existed_driving_license=DriverLicense::where('user_id',auth()->user()->id)->first();
         if(!$existed_driving_license){
            $license=DriverLicense::create(['user_id'=>auth()->user()->id,
                                                    'license_num'=>$request->license_number,
                                                    'expire_date'=>$request->license_expire_date]);
            uploadMedia($request->license_front_image,$license->LicenseFrontImageCollection,$license);
            uploadMedia($request->license_back_image,$license->LicenseBackImageCollection,$license);
         }else{
            $existed_driving_license->update(['license_num'=>$request->license_number,
                                              'expire_date'=>$request->license_expire_date]);
            if($request->file('license_front_image')){
                $license_front_image=getFirstMediaUrl($existed_driving_license,$existed_driving_license->LicenseFrontImageCollection);
                if($license_front_image!= null){
                    deleteMedia($existed_driving_license,$existed_driving_license->LicenseFrontImageCollection);
                    uploadMedia($request->license_front_image,$existed_driving_license->LicenseFrontImageCollection,$existed_driving_license);
                }else{
                    uploadMedia($request->license_front_image,$existed_driving_license->LicenseFrontImageCollection,$existed_driving_license);
                }
            }

            if($request->file('license_back_image')){
                $license_back_image=getFirstMediaUrl($existed_driving_license,$existed_driving_license->LicenseBackImageCollection);
                if($license_back_image!= null){
                    deleteMedia($existed_driving_license,$existed_driving_license->LicenseBackImageCollection);
                    uploadMedia($request->license_back_image,$existed_driving_license->LicenseBackImageCollection,$existed_driving_license);
                }else{
                    uploadMedia($request->license_back_image,$existed_driving_license->LicenseBackImageCollection,$existed_driving_license);
                }
            }
         }
         $driving_license=DriverLicense::where('user_id',auth()->user()->id)->first();
         $driving_license->license_front_image=getFirstMediaUrl($driving_license,$driving_license->LicenseFrontImageCollection);
         $driving_license->license_back_image=getFirstMediaUrl($driving_license,$driving_license->LicenseBackImageCollection);
         return $this->sendResponse($driving_license,'License Driving is Created Successfuly',200);

    }

    public function driving_license(){
        $driving_license=DriverLicense::where('user_id',auth()->user()->id)->first();
        if(!$driving_license){
            return $this->sendError(null,"You don't add information of your driving license yet",400);
        }
        $driving_license->license_front_image=getFirstMediaUrl($driving_license,$driving_license->LicenseFrontImageCollection);
        $driving_license->license_back_image=getFirstMediaUrl($driving_license,$driving_license->LicenseBackImageCollection);
        return $this->sendResponse($driving_license,null,200);
    }

    public function created_trips(){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $driver_car=Car::where('user_id',auth()->user()->id)->first();
        if($driver_car->status=='confirmed'){
            if(auth()->user()->is_online=='1'){
                $radius = 6371;
                $decimalPlaces = 2;
                $tripsWithin3Km = Trip::select('*')
                                    ->where('status','created')->with('user:id,name');
                if( $driver_car->air_conditioned=='0'){
                    $tripsWithin3Km->where('air_conditioned','0');
                }
                if( $driver_car->animals=='0'){
                    $tripsWithin3Km->where('animals','0');
                }
                if( $driver_car->passenger_type=='female'){
                    $tripsWithin3Km->whereHas('user', function ($query) {
                        $query->where('gendor', 'Female');
                    });
                }
                $tripsWithin3Km = $tripsWithin3Km->selectRaw("ROUND(( $radius * acos( cos( radians($driver_car->lat) ) * cos( radians( start_lat ) ) * cos( radians( start_lng ) - radians($driver_car->lng) ) + sin( radians($driver_car->lat) ) * sin( radians( start_lat ) ) ) ), $decimalPlaces) AS client_location_away")
                                    ->having('client_location_away', '<=', 3) // Filter cars within 3 km
                                    ->get()->map(function ($trip) use ($driver_car) {
                                        $response=calculate_distance($driver_car->lat,$driver_car->lng,$trip->start_lat,$trip->start_lng);
                                        $distance=$response['distance_in_km'];
                                        $duration=$response['duration_in_M'];
                                        if($distance <= 3){
                                            $trip->client_location_distance=$distance;
                                            $trip->client_location_duration=$duration;
                                            $trip->user->image=getFirstMediaUrl($trip->user,$trip->user->avatarCollection);
                                            $trip->current_offer=Offer::where('user_id',auth()->user()->id)->where('trip_id',$trip->id)->where('status','pending')->first();

                                            return $trip;
                                        }
                                    });
                return $this->sendResponse($tripsWithin3Km,null,200);
            }else{
                return $this->sendError(null,"your are offline",400);
            }
            
        }else{
            return $this->sendError(null,"Sorry your car isn't confirmed on application",400);
        }
    }

    public function activation(){
        $user=auth()->user();
        if($user->is_online=='1'){
            $user->is_online='0';
            $user->save();
            return $this->sendResponse(null,'you are Offline',200);
        }else{
            $user->is_online='1';
            $user->save();
            return $this->sendResponse(null,'you are online',200);

        }

    }

    public function create_offer(Request $request){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                 'required',
                 Rule::exists('trips', 'id') 
             ],
             'offer' => 'required',
             
         ]);
         // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $driver_car=Car::where('user_id',auth()->user()->id)->first();
        $lastOffer = Offer::orderBy('id', 'desc')->first();

            if ($lastOffer) {
                $lastCode = $lastOffer->code;
                $code = 'OFR-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $code = 'OFR-000001';
            }
        $offer=Offer::create(['user_id'=>auth()->user()->id,
                                'code'=>$code,
                               'car_id'=>$driver_car->id,
                               'trip_id'=>intval($request->trip_id),
                               'offer'=>floatval($request->offer)]);
        return $this->sendResponse($offer,null,200);

    }

    public function expire_offer($id){
        $offer=Offer::find($id);
        $offer->status='expired';
        $offer->save();
        return $this->sendResponse(null,'Offer is expired',200);
    }
    
    public function driver_current_trip(){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $lastAcceptedOffer = Offer::where('user_id',auth()->user()->id)
                                ->where('status', 'accepted')
                                ->orderBy('id', 'desc') // Or 'id' if you prefer
                                ->first();
        if(!$lastAcceptedOffer){
            return $this->sendError(null,'no current trip existed',400);
        }
        $trip=Trip::where('id',$lastAcceptedOffer->trip_id)->with(['car' => function($query) {
            $query->with(['mark','model']);
        },'user'])->first();
        $response=calculate_distance($lastAcceptedOffer->car->lat,$lastAcceptedOffer->car->lng,$trip->start_lat,$trip->start_lng);
        $distance=$response['distance_in_km'];
        $duration=$response['duration_in_M'];
        $trip->client_location_distance=$distance;
        $trip->client_location_duration=$duration;
        if($trip->status=='completed' || $trip->status=='cancelled'){
            return $this->sendError(null,'no current trip existed',400);
        }
        $trip->user->image=getFirstMediaUrl($trip->user,$trip->user->avatarCollection);
        return $this->sendResponse($trip,null,200);
    }

    public function start_trip(Request $request){
        $check_account=$this->check_banned();
        if($check_account!= true){
            return $this->sendError(null,$check_account,400);
        }
        $validator  =   Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')
            ]
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $trip=Trip::find($request->trip_id);
        if($trip->status=='pending'){
            $trip->status='in_progress';
            $trip->start_date=date('Y-m-d');
            $trip->start_time=date('H:i:s');
            $trip->save();
            return $this->sendResponse(null,'trip started now',200);
        }elseif($trip->status=='in_progress'){
            $trip->status='completed';
            $trip->end_date=date('Y-m-d');
            $trip->end_time=date('H:i:s');
            $trip->save();
            return $this->sendResponse(null,'trip ended now',200);
        }
    }

    public function update_location_car(Request $request){
        $validator  =   Validator::make($request->all(), [
            
             'lat' => 'required',
             'lng' => 'required',
             
         ]);
         // dd($request->all());
         if ($validator->fails()) {
 
             return $this->sendError(null,$validator->errors(),400);
         }
         $car=Car::where('user_id',auth()->user()->id)->first();
        if(!$car){
            return $this->sendError(null,"You don't create your cat yet",400);
        }
        if(auth()->user()->is_online=='0'){
            return $this->sendError(null,"You are Offline, You should be online first",400);
        }
        $car->lat=floatval($request->lat);
        $car->lng=floatval($request->lng);
        $car->save();
        return $this->sendResponse(null,'car location updated successfuly',200);
    }

    public function driver_completed_trips(){
        $car=Car::where('user_id',auth()->user()->id)->first();
        $completed_trips=Trip::where('car_id',$car->id)->where('status','completed')->with(['car' => function($query) {
            $query->with(['mark','model','owner']);
        },'user'])->get()->map(function ($trip) {
            
            $trip->user->image=getFirstMediaUrl($trip->user,$trip->user->avatarCollection);
            return $trip;
        
        });

        return $this->sendResponse($completed_trips,null,200);
    }

    public function driver_cancelled_trips(){
        $car=Car::where('user_id',auth()->user()->id)->first();
        $cancelled_trips=Trip::where('car_id',$car->id)->where('status','cancelled')->with(['car' => function($query) {
            $query->with(['mark','model','owner']);
        },'user','cancelled_by'])->get()->map(function ($trip) {
            
            $trip->user->image=getFirstMediaUrl($trip->user,$trip->user->avatarCollection);
            return $trip;
        
        });

        return $this->sendResponse($cancelled_trips,null,200);
    }
}
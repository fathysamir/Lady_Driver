<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Models\Car;
use App\Models\DriverLicense;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class DriverController extends ApiController
{
    public function create_car(Request $request){
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
            'license_expire_date'=>'required|date'
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            return $this->sendError(null,$validator->errors(),400);
        }
        $car=Car::create(['user_id'=>auth()->user()->id,
                          'car_mark_id'=>$request->car_mark_id,
                          'car_model_id'=>$request->car_model_id,
                          'color'=>$request->color,
                          'year'=>$request->year,
                          'car_plate'=>$request->car_plate,
                          'lat'=>floatval($request->lat),
                          'lng'=>floatval($request->lng),
                          'license_expire_date'=>$request->license_expire_date
                        ]);
        if($request->air_conditioned){
            $car->air_conditioned='1';
        }
        $car->save();
        uploadMedia($request->image,$car->avatarCollection,$car);
        uploadMedia($request->plate_image,$car->PlateImageCollection,$car);
        uploadMedia($request->license_front_image,$car->LicenseFrontImageCollection,$car);
        uploadMedia($request->license_back_image,$car->LicenseBackImageCollection,$car);

        return $this->sendResponse($car,'Car Created Successfuly.',200);
    }

    public function edit_car(Request $request){
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
                        
        if($request->air_conditioned==1){
            $car->air_conditioned=1;
        }else{
            $car->air_conditioned=0;
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

        return $this->sendResponse($car,'Car Updated Successfuly.',200);
    }

    public function car($id){
        $car=Car::where('id',$id)->with(['mark:id,name','model:id,name','owner:id,name'])->first();
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
         $driving_license=DriverLicense::create(['user_id'=>auth()->user()->id,
                                                 'license_num'=>$request->license_number,
                                                 'expire_date'=>$request->license_expire_date]);
         uploadMedia($request->license_front_image,$driving_license->LicenseFrontImageCollection,$driving_license);
         uploadMedia($request->license_back_image,$driving_license->LicenseBackImageCollection,$driving_license);

         return $this->sendResponse($driving_license,'License Driving is Created Successfuly',200);

    }

    public function driving_license(){
        $driving_license=DriverLicense::where('user_id',auth()->user()->id)->first();
        $driving_license->license_front_image=getFirstMediaUrl($driving_license,$driving_license->LicenseFrontImageCollection);
        $driving_license->license_back_image=getFirstMediaUrl($driving_license,$driving_license->LicenseBackImageCollection);
        return $this->sendResponse($driving_license,null,200);
    }

    
}
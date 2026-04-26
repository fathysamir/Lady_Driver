<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\Car;
use App\Models\User;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\DriverLicense;
use App\Models\MotorcycleMark;
use App\Models\MotorcycleModel;
use App\Models\Offer;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Trip;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Events\TripStarted;
use App\Events\TripEnded;
use App\Events\TrackCar;





class DriverController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function marks(Request $request)
    {

        $carMarks = CarMark::all();
        return $this->sendResponse($carMarks, null, 200);

    }

    public function models(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_mark_id' => [
                'required',
                Rule::exists('car_marks', 'id'),
            ],
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $models = CarModel::where('car_mark_id', $request->car_mark_id)->get();
        return $this->sendResponse($models, null, 200);

    }

    public function scooter_marks(Request $request)
    {
        $scooterMarks = MotorcycleMark::all();
        return $this->sendResponse($scooterMarks, null, 200);
    }

    public function scooter_models(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scooter_mark_id' => [
                'required',
                Rule::exists('motorcycle_marks', 'id'),
            ],
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        $models = MotorcycleModel::where('motorcycle_mark_id', $request->scooter_mark_id)->get();
        return $this->sendResponse($models, null, 200);

    }

    public function create_car(Request $request)
    {
        // $check_account = $this->check_banned();
        // if ($check_account != true) {
        //     return $this->sendError(null, $check_account, 400);
        // }
        $validator = Validator::make($request->all(), [
            'car_mark_id'         => [
                'required',
                Rule::exists('car_marks', 'id'),
            ],
            'car_model_id'        => [
                'required',
                Rule::exists('car_models', 'id'),
            ],
            'color'               => 'required|string|max:255',
            'year'                => 'required|integer|min:1900|max:' . date('Y'),
            'car_plate'           => 'required|string|max:255',
            'air_conditioned'     => 'nullable|boolean',
            'image'               => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'plate_image'         => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_back_image'  => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'inspection_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'lat'                 => 'nullable',
            'lng'                 => 'nullable',
            'license_expire_date' => 'required|date',
            'passenger_type'      => 'required|in:female,male_female',
            'car_inspection_date' => 'nullable|date',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $lastCar = Car::orderBy('id', 'desc')->first();

        if ($lastCar) {
            $lastCode = $lastCar->code;
            $code     = 'CAR-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
        } else {
            $code = 'CAR-000000001';
        }
        $car = Car::create(['user_id' => auth()->user()->id,
            'car_mark_id'                 => $request->car_mark_id,
            'code'                        => $code,
            'car_model_id'                => $request->car_model_id,
            'color'                       => $request->color,
            'year'                        => $request->year,
            'car_plate'                   => $request->car_plate,
            'lat'                         => floatval($request->lat),
            'lng'                         => floatval($request->lng),
            'passenger_type'              => $request->passenger_type,
            'license_expire_date'         => $request->license_expire_date,
            'car_inspection_date'         => $request->car_inspection_date,
        ]);
        if ($request->air_conditioned) {
            $car->air_conditioned = '1';
        } else {
            $car->air_conditioned = '0';
        }

        if ($request->animal == '1') {
            $car->animals = '1';
        } else {
            $car->animals = '0';
        }

        $comfort_year = Setting::where('key', 'comfort_car_start_from_year')->where('category', 'General')->where('type', 'number')->first()->value;

        if (intval($request->year) >= intval($comfort_year)) {
            $car->is_comfort = '1';
        } else {
            $car->is_comfort = '0';
        }

        $car->save();
        uploadMedia($request->image, $car->avatarCollection, $car);
        uploadMedia($request->plate_image, $car->PlateImageCollection, $car);
        uploadMedia($request->license_front_image, $car->LicenseFrontImageCollection, $car);
        uploadMedia($request->license_back_image, $car->LicenseBackImageCollection, $car);
        if ($request->inspection_image) {
            uploadMedia($request->inspection_image, $car->CarInspectionImageCollection, $car);
        }
        $car->image               = getFirstMediaUrl($car, $car->avatarCollection);
        $car->plate_image         = getFirstMediaUrl($car, $car->PlateImageCollection);
        $car->license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
        $car->license_back_image  = getFirstMediaUrl($car, $car->LicenseBackImageCollection);
        $car->inspection_image    = getFirstMediaUrl($car, $car->CarInspectionImageCollection);
        return $this->sendResponse($car, 'Car Created Successfully.', 200);
    }

    public function edit_car(Request $request)
    {
        // $check_account = $this->check_banned();
        // if ($check_account != true) {
        //     return $this->sendError(null, $check_account, 400);
        // }
        $validator = Validator::make($request->all(), [
            'car_id'          => [
                'required',
                Rule::exists('cars', 'id'),
            ],
            'car_mark_id'         => [
                'required',
                Rule::exists('car_marks', 'id'),
             ],
            'car_model_id'        => [
               'required',
               Rule::exists('car_models', 'id'),
             ],
            'color'           => 'required|string|max:255',
            // 'year'                => 'required|integer|min:1900|max:' . date('Y'),
            // 'car_plate'           => 'required|string|max:255',
            'air_conditioned' => 'nullable|boolean',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            // 'plate_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'passenger_type'  => 'required|in:female,male_female',

        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        Car::where('id', $request->car_id)->update([
             'car_mark_id'         => $request->car_mark_id,
             'car_model_id'        => $request->car_model_id,
            'color'          => $request->color,
            // 'year'                => $request->year,
            // 'car_plate'           => $request->car_plate,
            'status'         => 'pending',
            'passenger_type' => $request->passenger_type,
        ]);
        $car = Car::find($request->car_id);

        if ($request->air_conditioned == '1') {
            $car->air_conditioned = '1';
        } else {
            $car->air_conditioned = '0';
        }
        if ($request->animal == '1') {
            $car->animals = '1';
        } else {
            $car->animals = '0';
        }

        // $comfort_year = Setting::where('key', 'comfort_car_start_from_year')->where('category', 'General')->where('type', 'number')->first()->value;

        // if (intval($request->year) >= intval($comfort_year)) {
        //     $car->is_comfort = '1';
        // } else {
        //     $car->is_comfort = '0';
        // }
        $car->save();

        if ($request->file('image')) {
            $image = getFirstMediaUrl($car, $car->avatarCollection);
            if ($image != null) {
                deleteMedia($car, $car->avatarCollection);
                uploadMedia($request->image, $car->avatarCollection, $car);
            } else {
                uploadMedia($request->image, $car->avatarCollection, $car);
            }
        }

        // if ($request->file('plate_image')) {
        //     $plate_image = getFirstMediaUrl($car, $car->PlateImageCollection);
        //     if ($plate_image != null) {
        //         deleteMedia($car, $car->PlateImageCollection);
        //         uploadMedia($request->plate_image, $car->PlateImageCollection, $car);
        //     } else {
        //         uploadMedia($request->plate_image, $car->PlateImageCollection, $car);
        //     }
        // }

        // if ($request->file('inspection_image')) {
        //     $inspection_image = getFirstMediaUrl($car, $car->CarInspectionImageCollection);
        //     if ($inspection_image != null) {
        //         deleteMedia($car, $car->CarInspectionImageCollection);
        //         uploadMedia($request->inspection_image, $car->CarInspectionImageCollection, $car);
        //     } else {
        //         uploadMedia($request->inspection_image, $car->CarInspectionImageCollection, $car);
        //     }
        // }

        $car                      = Car::find($request->car_id);
        $car->image               = getFirstMediaUrl($car, $car->avatarCollection);
        $car->plate_image         = getFirstMediaUrl($car, $car->PlateImageCollection);
        $car->license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
        $car->license_back_image  = getFirstMediaUrl($car, $car->LicenseBackImageCollection);
        $car->inspection_image    = getFirstMediaUrl($car, $car->CarInspectionImageCollection);

        $driver         = auth()->user();
        $driver->status = 'pending';
        $driver->save();
        return $this->sendResponse($car, 'Car Updated Successfully.', 200);
    }

    public function car(Request $request)
    {
        $acceptedLanguage = $request->header('Accept-Language');
        $car              = Car::where('user_id', auth()->user()->id)->with(['owner:id,name', 'mark', 'model'])->first();
        if (! $car) {
            return $this->sendError(null, "You don't create your car yet", 400);
        }

        $car->image               = getFirstMediaUrl($car, $car->avatarCollection);
        $car->plate_image         = getFirstMediaUrl($car, $car->PlateImageCollection);
        $car->license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
        $car->license_back_image  = getFirstMediaUrl($car, $car->LicenseBackImageCollection);
        $car->inspection_image    = getFirstMediaUrl($car, $car->CarInspectionImageCollection);

        return $this->sendResponse($car, null, 200);
    }

    public function create_scooter(Request $request)
    {
        // $check_account = $this->check_banned();
        // if ($check_account != true) {
        //     return $this->sendError(null, $check_account, 400);
        // }
        $validator = Validator::make($request->all(), [
            'scooter_mark_id'     => [
                'required',
                Rule::exists('motorcycle_marks', 'id'),
            ],
            'scooter_model_id'    => [
                'required',
                Rule::exists('motorcycle_models', 'id'),
            ],
            'color'               => 'required|string|max:255',
            'year'                => 'required|integer|min:1900|max:' . date('Y'),
            'scooter_plate'       => 'required|string|max:255',

            'image'               => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'plate_image'         => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_back_image'  => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'lat'                 => 'nullable',
            'lng'                 => 'nullable',
            'license_expire_date' => 'required|date',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $lastScooter = Scooter::orderBy('id', 'desc')->first();

        if ($lastScooter) {
            $lastCode = $lastScooter->code;
            $code     = 'SCO-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
        } else {
            $code = 'SCT-000000001';
        }
        $scooter = Scooter::create(['user_id' => auth()->user()->id,
            'motorcycle_mark_id'                  => $request->scooter_mark_id,
            'code'                                => $code,
            'motorcycle_model_id'                 => $request->scooter_model_id,
            'color'                               => $request->color,
            'year'                                => $request->year,
            'scooter_plate'                       => $request->scooter_plate,
            'lat'                                 => floatval($request->lat),
            'lng'                                 => floatval($request->lng),
            'license_expire_date'                 => $request->license_expire_date,
        ]);

        $scooter->save();
        uploadMedia($request->image, $scooter->avatarCollection, $scooter);
        uploadMedia($request->plate_image, $scooter->PlateImageCollection, $scooter);
        uploadMedia($request->license_front_image, $scooter->LicenseFrontImageCollection, $scooter);
        uploadMedia($request->license_back_image, $scooter->LicenseBackImageCollection, $scooter);

        $scooter->image               = getFirstMediaUrl($scooter, $scooter->avatarCollection);
        $scooter->plate_image         = getFirstMediaUrl($scooter, $scooter->PlateImageCollection);
        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image  = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);
        $scooter->inspection_image    = getFirstMediaUrl($scooter, $scooter->CarInspectionImageCollection);
        return $this->sendResponse($scooter, 'Scooter Created Successfully.', 200);
    }

    public function edit_scooter(Request $request)
    {
        // $check_account = $this->check_banned();
        // if ($check_account != true) {
        //     return $this->sendError(null, $check_account, 400);
        // }
        $validator = Validator::make($request->all(), [
            'scooter_id' => [
                'required',
                Rule::exists('scooters', 'id'),
            ],
            // 'scooter_mark_id'     => [
            //     'required',
            //     Rule::exists('motorcycle_marks', 'id'),
            // ],
            // 'scooter_model_id'    => [
            //     'required',
            //     Rule::exists('motorcycle_models', 'id'),
            // ],
            'color'      => 'required|string|max:255',
            // 'year'                => 'required|integer|min:1900|max:' . date('Y'),
            // 'scooter_plate'       => 'required|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            // 'plate_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        Car::where('id', $request->scooter_id)->update([
            //'scooter_mark_id'  => $request->scooter_mark_id,
          //  'scooter_model_id' => $request->scooter_model_id,
            'color'  => $request->color,
            // 'year'                => $request->year,
            // 'scooter_plate'       => $request->scooter_plate,
            // 'lat'                 => floatval($request->lat),
            // 'lng'                 => floatval($request->lng),
            'status' => 'pending',

        ]);
        $scooter = Car::find($request->scooter_id);
        if ($request->file('image')) {
            $image = getFirstMediaUrl($scooter, $scooter->avatarCollection);
            if ($image != null) {
                deleteMedia($scooter, $scooter->avatarCollection);
            }
            uploadMedia($request->image, $scooter->avatarCollection, $scooter);

        }
        $scooter                      = Scooter::find($request->scooter_id);
        $scooter->image               = getFirstMediaUrl($scooter, $scooter->avatarCollection);
        $scooter->plate_image         = getFirstMediaUrl($scooter, $scooter->PlateImageCollection);
        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image  = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);

        return $this->sendResponse($scooter, 'Scooter Updated Successfully.', 200);
    }

    public function scooter(Request $request)
    {
        $acceptedLanguage = $request->header('Accept-Language');
        $scooter          = Scooter::where('user_id', auth()->user()->id)->with(['owner:id,name', 'mark', 'model'])->first();
        if (! $scooter) {
            return $this->sendError(null, "You don't create your scooter yet", 400);
        }

        $scooter->image               = getFirstMediaUrl($scooter, $scooter->avatarCollection);
        $scooter->plate_image         = getFirstMediaUrl($scooter, $scooter->PlateImageCollection);
        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image  = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);

        return $this->sendResponse($scooter, null, 200);
    }
    public function add_car_license(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_back_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'car_id'              => 'required|exists:cars,id',

            'license_expire_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'car_plate_number' => 'required|string|max:100',
        ]);
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $car = Car::findOrFail($request->car_id);

        $car->update([
            'status'              => 'pending',
            'license_expire_date' => $request->license_expire_date,
        ]);

        if ($request->file('license_front_image')) {
            $license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
            if ($license_front_image != null) {
                deleteMedia($car, $car->LicenseFrontImageCollection);
            }
            uploadMedia($request->license_front_image, $car->LicenseFrontImageCollection, $car);
        }

        if ($request->file('license_back_image')) {
            $license_back_image = getFirstMediaUrl($car, $car->LicenseBackImageCollection);
            if ($license_back_image != null) {
                deleteMedia($car, $car->LicenseBackImageCollection);
            }
            uploadMedia($request->license_back_image, $car->LicenseBackImageCollection, $car);
        }
        $car = Car::findOrFail($request->car_id);

        $car->license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
        $car->license_back_image  = getFirstMediaUrl($car, $car->LicenseBackImageCollection);

        $driver         = auth()->user();

        Car::updateOrCreate(
            ['user_id' => $driver->id],
            [
                'car_plate' => $request->car_plate_number,
            ]
        );

        $driver->status = 'pending';
        $driver->save();
        return $this->sendResponse($car->fresh(), 'Car Updated Successfully.', 200);
    }
    public function add_scooter_license(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_back_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'scooter_id'          => 'required|exists:scooters,id',

            'license_expire_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'scooter_plate_number' => 'required|string|max:100',

        ]);
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $scooter = Scooter::findOrFail($request->scooter_id);

        $scooter->update([
            'status'              => 'pending',
            'license_expire_date' => $request->license_expire_date,
        ]);

        if ($request->file('license_front_image')) {
            $license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
            if ($license_front_image != null) {
                deleteMedia($scooter, $scooter->LicenseFrontImageCollection);
            }
            uploadMedia($request->license_front_image, $scooter->LicenseFrontImageCollection, $scooter);
        }

        if ($request->file('license_back_image')) {
            $license_back_image = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);
            if ($license_back_image != null) {
                deleteMedia($scooter, $scooter->LicenseBackImageCollection);
            }
            uploadMedia($request->license_back_image, $scooter->LicenseBackImageCollection, $scooter);
        }
        $scooter = Scooter::findOrFail($request->scooter_id);

        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image  = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);

        $driver         = auth()->user();
        Scooter::updateOrCreate(
            ['user_id' => $driver->id], //condition to check if a record exists for the user
            [
                'scooter_plate' => $request->scooter_plate_number, //value to update or create
            ]
        );
        $driver->status = 'pending';
        $driver->save();
        return $this->sendResponse($scooter->fresh(), 'Scooter Updated Successfully.', 200);
    }

    public function add_driving_license(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'license_number'      => 'required',
            'license_expire_date' => 'required|date',
            'license_front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'license_back_image'  => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',

        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $existed_driving_license = DriverLicense::where('user_id', auth()->user()->id)->first();
        $user                    = auth()->user();
        $user->status            = 'pending';
        $user->save();
        if (! $existed_driving_license) {
            $license = DriverLicense::create(['user_id' => auth()->user()->id,
                'license_num'                               => $request->license_number,
                'expire_date'                               => $request->license_expire_date]);

            uploadMedia($request->license_front_image, $license->LicenseFrontImageCollection, $license);
            uploadMedia($request->license_back_image, $license->LicenseBackImageCollection, $license);

        } else {
            $existed_driving_license->update(['license_num' => $request->license_number,
                'expire_date'                                   => $request->license_expire_date]);
            if ($request->file('license_front_image')) {
                $license_front_image = getFirstMediaUrl($existed_driving_license, $existed_driving_license->LicenseFrontImageCollection);
                if ($license_front_image != null) {
                    deleteMedia($existed_driving_license, $existed_driving_license->LicenseFrontImageCollection);
                }
                uploadMedia($request->license_front_image, $existed_driving_license->LicenseFrontImageCollection, $existed_driving_license);
            }

            if ($request->file('license_back_image')) {
                $license_back_image = getFirstMediaUrl($existed_driving_license, $existed_driving_license->LicenseBackImageCollection);
                if ($license_back_image != null) {
                    deleteMedia($existed_driving_license, $existed_driving_license->LicenseBackImageCollection);
                }
                uploadMedia($request->license_back_image, $existed_driving_license->LicenseBackImageCollection, $existed_driving_license);
            }

        }
        $driving_license                      = DriverLicense::where('user_id', auth()->user()->id)->first();
        $driving_license->license_front_image = getFirstMediaUrl($driving_license, $driving_license->LicenseFrontImageCollection);
        $driving_license->license_back_image  = getFirstMediaUrl($driving_license, $driving_license->LicenseBackImageCollection);

        return $this->sendResponse($driving_license, 'License Driving is Created Successfully', 200);

    }

    public function add_car_inspection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Car_inspection_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'inspection_date'      => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $car = Car::where('user_id', auth()->user()->id)->first();

        if (!$car) {
            return $this->sendError(null, 'Make sure the car is registered', 400);
        }

        $car->car_inspection_date = date('Y-m-d', strtotime($request->inspection_date));

        if ($request->hasFile('Car_inspection_image')) {
            $Car_inspection_image = getFirstMediaUrl($car, $car->CarInspectionImageCollection);
            if ($Car_inspection_image != null) {
                deleteMedia($car, $car->CarInspectionImageCollection);
            }
            uploadMedia($request->Car_inspection_image, $car->CarInspectionImageCollection, $car);
            $car->status  = 'pending';
            $car->save();

            $user         = auth()->user();
            $user->status = 'pending';
            $user->save();
        } else {
            $car->save();
        }

        return $this->sendResponse([
            'inspection_date'      => $car->car_inspection_date,
            'Car_inspection_image' => getFirstMediaUrl($car, $car->CarInspectionImageCollection),
            'status'               => $car->status,
        ], 'Car inspection saved successfully', 200);
    }

    public function medical_examination(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medical_examination_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'medical_examination_date'  => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $user = auth()->user();

        $user->medical_examination_date = date('Y-m-d', strtotime($request->medical_examination_date));

        if ($request->hasFile('medical_examination_image')) {
            $medical_examination_image = getFirstMediaUrl($user, $user->medicalExaminationImageCollection);
            if ($medical_examination_image != null) {
                deleteMedia($user, $user->medicalExaminationImageCollection);
            }
            uploadMedia($request->medical_examination_image, $user->medicalExaminationImageCollection, $user);
            $user->status = 'pending';
        }

        $user->save();

        return $this->sendResponse([
            'medical_examination_date'  => $user->medical_examination_date,
            'medical_examination_image' => getFirstMediaUrl($user, $user->medicalExaminationImageCollection),
            'status'                    => $user->status,
        ], 'Medical examination saved successfully', 200);
    }
    public function criminal_record(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'criminal_record_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'criminal_record_date'  => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $user = auth()->user();

        $user->criminal_record_date = date('Y-m-d', strtotime($request->criminal_record_date));

        if ($request->hasFile('criminal_record_image')) {
            $criminal_record_image = getFirstMediaUrl($user, $user->criminalRecordImageCollection);
            if ($criminal_record_image != null) {
                deleteMedia($user, $user->criminalRecordImageCollection);
            }
            uploadMedia($request->criminal_record_image, $user->criminalRecordImageCollection, $user);
            $user->status = 'pending';
        }

        $user->save();

        return $this->sendResponse([
            'criminal_record_date'  => $user->criminal_record_date,
            'criminal_record_image' => getFirstMediaUrl($user, $user->criminalRecordImageCollection),
            'status'                => $user->status,
        ], 'Criminal record saved successfully!!', 200);
    }
    public function driving_license()
    {
        $driving_license = DriverLicense::where('user_id', auth()->user()->id)->first();
        if (! $driving_license) {
            return $this->sendError(null, "You don't add information of your driving license yet", 400);
        }
        $user                                 = auth()->user();
        $driving_license->license_front_image = getFirstMediaUrl($driving_license, $driving_license->LicenseFrontImageCollection);
        $driving_license->license_back_image  = getFirstMediaUrl($driving_license, $driving_license->LicenseBackImageCollection);

        return $this->sendResponse($driving_license, null, 200);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////

    public function created_trips(Request $request)
    {
        $check_account = $this->check_banned();

        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }

        $user = auth()->user();

        if (!$user->is_online) {
            return $this->sendError(null, "You are offline", 400);
        }

        $vehicle = $user->driver_type == 'scooter'
            ? Scooter::where('user_id', $user->id)->first()
            : Car::where('user_id', $user->id)->first();

        if (!$vehicle) {
            return $this->sendError(null, "No vehicle found", 400);
        }

        if ($vehicle->status != 'confirmed') {
            return $this->sendError(null, "Account under review", 400);
        }

        $radius = 6371;

        $lat = $vehicle->lat;
        $lng = $vehicle->lng;

        $trips = Trip::query()
            ->whereIn('status', ['created', 'scheduled', 'in_progress'])
            ->where('type', $user->driver_type)
            ->where(function ($q) {
                $q->where('status', 'scheduled')
                  ->orWhere('created_at', '>=', now()->subMinutes(5));
            })

            // IMPORTANT: avoid selectRaw("*")
            ->select('trips.*')

            ->with([
                'car.mark',
                'car.model',
                'car.owner:id,name,country_code,phone',
                'scooter.motorcycleMark',
                'scooter.motorcycleModel',
                'scooter.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'finalDestination:id,trip_id,lat,lng,address',
            ])

            // safe computed distance
            ->addSelect(\DB::raw("
                ROUND(
                    (
                        $radius * acos(
                            cos(radians($lat)) *
                            cos(radians(start_lat)) *
                            cos(radians(start_lng) - radians($lng)) +
                            sin(radians($lat)) *
                            sin(radians(start_lat))
                        )
                    ), 2
                ) as client_location_away
            "))

            ->having('client_location_away', '<=', 3)
            ->latest()
            ->get();

        $trips->transform(function ($trip) use ($vehicle) {

            // distance + duration
            $response = calculate_distance(
                $vehicle->lat,
                $vehicle->lng,
                $trip->start_lat,
                $trip->start_lng
            );

            $trip->client_location_distance = round($response['distance_in_km'], 2);
            $trip->client_location_duration = (int) $response['duration_in_M'];

            // barcode
            $trip->barcode = url(barcodeImage($trip->id));
            if ($trip->car) {
                $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
            }
            // Clean car plate
if ($trip->car) {
    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
}

// Clean scooter plate
if ($trip->scooter) {
    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
}

            // driver arrived
            $trip->is_driver_arrived = !is_null($trip->driver_arrived);

            // user data
            if ($trip->user) {
                $trip->user->image = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);

                $trip->user->rate = round(
                    Trip::where('user_id', $trip->user->id)
                        ->where('status', 'completed')
                        ->where('driver_stare_rate', '>', 0)
                        ->avg('driver_stare_rate') ?? 5,
                    1
                );
            }

            // car owner
            if ($trip->car && $trip->car->owner) {
                $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);

                $trip->car->owner->rate = round(
                    Trip::whereHas('car', function ($q) use ($trip) {
                        $q->where('user_id', $trip->car->owner->id);
                    })
                    ->where('status', 'completed')
                    ->where('client_stare_rate', '>', 0)
                    ->avg('client_stare_rate') ?? 5,
                    1
                );
            }

            // scooter owner
            if ($trip->scooter && $trip->scooter->owner) {
                $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);

                $trip->scooter->owner->rate = round(
                    Trip::whereHas('scooter', function ($q) use ($trip) {
                        $q->where('user_id', $trip->scooter->owner->id);
                    })
                    ->where('status', 'completed')
                    ->where('client_stare_rate', '>', 0)
                    ->avg('client_stare_rate') ?? 5,
                    1
                );
            }

            // rename
            $trip->final_destination = $trip->finalDestination;
            unset($trip->finalDestination);

            return $trip;
        });

        return $this->sendResponse($trips->values(), null, 200);
    }
    public function driver_current_trip()
    {
        $check_account = $this->check_banned();

        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }

        $user = auth()->user();

        $lastAcceptedOffer = Offer::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereHas('trip', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            })
            ->latest()
            ->first();

        if (!$lastAcceptedOffer) {
            return $this->sendError(null, 'no current trip existed', 400);
        }

        // FIX: use clean select (avoid hidden/partial hydration issues)
        $trip = Trip::query()
            ->where('id', $lastAcceptedOffer->trip_id)
            ->select('trips.*')
            ->with([
                'car.mark',
                'car.model',
                'car.owner:id,name,country_code,phone',
                'scooter.motorcycleMark',
                'scooter.motorcycleModel',
                'scooter.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'finalDestination:id,trip_id,lat,lng,address',
            ])
            ->first();

        if (!$trip || in_array($trip->status, ['completed', 'cancelled'])) {
            return $this->sendError(null, 'no current trip existed', 400);
        }

        // Distance from vehicle to pickup
        $vehicle = $lastAcceptedOffer->car ?? $lastAcceptedOffer->scooter;

        if ($vehicle) {
            $response = calculate_distance(
                $vehicle->lat,
                $vehicle->lng,
                $trip->start_lat,
                $trip->start_lng
            );

            $trip->client_location_distance = round($response['distance_in_km'], 2);
            $trip->client_location_duration  = (int) $response['duration_in_M'];
        }

        // Barcode
        $trip->barcode = url(barcodeImage($trip->id));
        // Clean car plate
if ($trip->car) {
    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
}

// Clean scooter plate
if ($trip->scooter) {
    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
}

        // Driver arrived
        $trip->is_driver_arrived = !is_null($trip->driver_arrived);

        // ================= USER =================
        if ($trip->user) {
            $trip->user->image          = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
            $trip->user->id_front_image = getFirstMediaUrl($trip->user, $trip->user->IDfrontImageCollection);
            $trip->user->id_back_image  = getFirstMediaUrl($trip->user, $trip->user->IDbackImageCollection);
            $trip->user->passport_image = getFirstMediaUrl($trip->user, $trip->user->passportImageCollection);

            $trip->user->rate = round(
                Trip::where('user_id', $trip->user->id)
                    ->where('status', 'completed')
                    ->where('driver_stare_rate', '>', 0)
                    ->avg('driver_stare_rate') ?? 5,
                1
            );
        }

        // ================= CAR OWNER =================
        if ($trip->car && $trip->car->owner) {
            $owner = $trip->car->owner;

            $owner->image          = getFirstMediaUrl($owner, $owner->avatarCollection);
            $owner->id_front_image = getFirstMediaUrl($owner, $owner->IDfrontImageCollection);
            $owner->id_back_image  = getFirstMediaUrl($owner, $owner->IDbackImageCollection);
            $owner->passport_image = getFirstMediaUrl($owner, $owner->passportImageCollection);

            $owner->rate = round(
                Trip::whereHas('car', function ($q) use ($owner) {
                    $q->where('user_id', $owner->id);
                })
                ->where('status', 'completed')
                ->where('client_stare_rate', '>', 0)
                ->avg('client_stare_rate') ?? 5,
                1
            );
        }

        // ================= SCOOTER OWNER =================
        if ($trip->scooter && $trip->scooter->owner) {
            $owner = $trip->scooter->owner;

            $owner->image          = getFirstMediaUrl($owner, $owner->avatarCollection);
            $owner->id_front_image = getFirstMediaUrl($owner, $owner->IDfrontImageCollection);
            $owner->id_back_image  = getFirstMediaUrl($owner, $owner->IDbackImageCollection);
            $owner->passport_image = getFirstMediaUrl($owner, $owner->passportImageCollection);

            $owner->rate = round(
                Trip::whereHas('scooter', function ($q) use ($owner) {
                    $q->where('user_id', $owner->id);
                })
                ->where('status', 'completed')
                ->where('client_stare_rate', '>', 0)
                ->avg('client_stare_rate') ?? 5,
                1
            );
        }

        // Rename final destination
        $trip->final_destination = $trip->finalDestination;
        unset($trip->finalDestination);

        return $this->sendResponse($trip, null, 200);
    }

    public function start_end_trip(Request $request)
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }

        $validator = Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id'),
            ],
        ]);

        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }

        $trip = Trip::find($request->trip_id);

        if ($trip->status == 'pending') {
            $trip->status     = 'in_progress';
            $trip->start_date = date('Y-m-d');
            $trip->start_time = date('H:i:s');
            $trip->save();

            // Notify passenger
            event(new \App\Events\TripStarted($trip, $trip->user_id));

            // Notify driver
            $driverId = $trip->car_id ? $trip->car->user_id : $trip->scooter->user_id;
            event(new \App\Events\TripStarted($trip, $driverId));

            return $this->sendResponse(null, 'trip started now', 200);

        } elseif ($trip->status == 'in_progress') {
            $trip->status   = 'completed';
            $trip->end_date = date('Y-m-d');
            $trip->end_time = date('H:i:s');
            $trip->save();

            // Notify passenger
            event(new \App\Events\TripEnded($trip, $trip->user_id));

            // Notify driver
            $driverId = $trip->car_id ? $trip->car->user_id : $trip->scooter->user_id;
            event(new \App\Events\TripEnded($trip, $driverId));

            return $this->sendResponse(null, 'trip ended now', 200);
        }
    }



    public function update_location_car(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lat'     => 'required',
        'lng'     => 'required',
        'heading' => 'nullable|numeric',
        'speed'   => 'nullable|numeric',
    ]);

    if ($validator->fails()) {
        $errors = implode(" / ", $validator->errors()->all());
        return $this->sendError(null, $errors, 400);
    }

    $user = auth()->user();

    $user->update([
        'lat' => floatval($request->lat),
        'lng' => floatval($request->lng),
        'heading' => $request->heading ?? $user->heading,
        'speed'   => $request->speed ?? $user->speed,
    ]);

    // ================= CAR =================
    if (in_array($user->driver_type, ['car', 'comfort_car'])) {

        $car = Car::where('user_id', $user->id)->first();

        if (!$car) {
            return $this->sendError(null, "You don't create your car yet", 400);
        }

        if ($user->is_online == '0') {
            return $this->sendError(null, "You are Offline, You should be online first", 400);
        }

        $car->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'heading' => $request->heading,
            'speed' => $request->speed,
        ]);

        $trip = Trip::where('car_id', $car->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($trip) {

            $tracker = app(\App\Services\TripTrackingService::class);
            $result = $tracker->calculate($request->lat, $request->lng, $trip);

            if ($result) {

                foreach ([$trip->user_id, $car->user_id] as $id) {
                    event(new \App\Events\TrackCar(
                        $request->lat,
                        $request->lng,
                        $request->heading ?? 0,
                        $request->speed ?? 0,
                        $result['distance'],
                        $result['duration'],
                        $result['eta'],
                        $result['message'],
                        $result['status'],
                        $id
                    ));
                }
            }
        }

        return $this->sendResponse(null, 'car location updated successfully', 200);
    }

    // ================= SCOOTER =================
    elseif ($user->driver_type == 'scooter') {

        $scooter = Scooter::where('user_id', $user->id)->first();

        if (!$scooter) {
            return $this->sendError(null, "You don't create your scooter yet", 400);
        }

        if ($user->is_online == '0') {
            return $this->sendError(null, "You are Offline, You should be online first", 400);
        }

        $scooter->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'heading' => $request->heading,
            'speed' => $request->speed,
        ]);

        $trip = Trip::where('scooter_id', $scooter->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($trip) {

            $tracker = app(\App\Services\TripTrackingService::class);
            $result = $tracker->calculate($request->lat, $request->lng, $trip);

            if ($result) {

                foreach ([$trip->user_id, $scooter->user_id] as $id) {
                    event(new \App\Events\TrackCar(
                        $request->lat,
                        $request->lng,
                        $request->heading ?? 0,
                        $request->speed ?? 0,
                        $result['distance'],
                        $result['duration'],
                        $result['eta'],
                        $result['message'],
                        $result['status'],
                        $id
                    ));
                }
            }
        }

        return $this->sendResponse(null, 'scooter location updated successfully', 200);
    }
}
public function driver_completed_trips()
{
    if (in_array(auth()->user()->driver_type, ['car', 'comfort_car'])) {
        $car             = Car::where('user_id', auth()->user()->id)->first();
        $completed_trips = Trip::where('car_id', $car->id)
            ->where('status', 'completed')
            ->with([
                'car.mark',
                'car.model',
                'car.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'finalDestination:id,trip_id,lat,lng,address',
            ])
            ->get()
            ->map(function ($trip) {

                // Distance
                $vehicle = $trip->car;
                if ($vehicle) {
                    $response = calculate_distance(
                        $vehicle->lat, $vehicle->lng,
                        $trip->start_lat, $trip->start_lng
                    );
                    $trip->client_location_distance = round($response['distance_in_km'], 2);
                    $trip->client_location_duration = intval($response['duration_in_M']);
                }

                // Barcode
                $trip->barcode = url(barcodeImage($trip->id));

                // is_driver_arrived
                $trip->is_driver_arrived = !is_null($trip->driver_arrived);

                // Clean car plate
                if ($trip->car) {
                    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
                }

                // User image + rate
                if ($trip->user) {
                    $trip->user->image          = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
                    $trip->user->id_front_image = getFirstMediaUrl($trip->user, $trip->user->IDfrontImageCollection);
                    $trip->user->id_back_image  = getFirstMediaUrl($trip->user, $trip->user->IDbackImageCollection);
                    $trip->user->passport_image = getFirstMediaUrl($trip->user, $trip->user->passportImageCollection);
                    $trip->user->rate           = round(
                        Trip::where('user_id', $trip->user->id)
                            ->where('status', 'completed')
                            ->where('driver_stare_rate', '>', 0)
                            ->avg('driver_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Car owner image + rate
                if ($trip->car && $trip->car->owner) {
                    $trip->car->owner->image          = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
                    $trip->car->owner->id_front_image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->IDfrontImageCollection);
                    $trip->car->owner->id_back_image  = getFirstMediaUrl($trip->car->owner, $trip->car->owner->IDbackImageCollection);
                    $trip->car->owner->passport_image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->passportImageCollection);
                    $trip->car->owner->rate           = round(
                        Trip::whereHas('car', function ($q) use ($trip) {
                            $q->where('user_id', $trip->car->owner->id);
                        })->where('status', 'completed')
                          ->where('client_stare_rate', '>', 0)
                          ->avg('client_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Rename finalDestination
                $trip->final_destination = $trip->finalDestination;
                unset($trip->finalDestination);

                return $trip;
            });

    } elseif (auth()->user()->driver_type == 'scooter') {
        $scooter         = Scooter::where('user_id', auth()->user()->id)->first();
        $completed_trips = Trip::where('scooter_id', $scooter->id)
            ->where('status', 'completed')
            ->with([
                'scooter.motorcycleMark',
                'scooter.motorcycleModel',
                'scooter.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'finalDestination:id,trip_id,lat,lng,address',
            ])
            ->get()
            ->map(function ($trip) {

                // Distance
                $vehicle = $trip->scooter;
                if ($vehicle) {
                    $response = calculate_distance(
                        $vehicle->lat, $vehicle->lng,
                        $trip->start_lat, $trip->start_lng
                    );
                    $trip->client_location_distance = round($response['distance_in_km'], 2);
                    $trip->client_location_duration = intval($response['duration_in_M']);
                }

                // Barcode
                $trip->barcode = url(barcodeImage($trip->id));

                // is_driver_arrived
                $trip->is_driver_arrived = !is_null($trip->driver_arrived);

                // Clean scooter plate
                if ($trip->scooter) {
                    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
                }

                // User image + rate
                if ($trip->user) {
                    $trip->user->image          = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
                    $trip->user->id_front_image = getFirstMediaUrl($trip->user, $trip->user->IDfrontImageCollection);
                    $trip->user->id_back_image  = getFirstMediaUrl($trip->user, $trip->user->IDbackImageCollection);
                    $trip->user->passport_image = getFirstMediaUrl($trip->user, $trip->user->passportImageCollection);
                    $trip->user->rate           = round(
                        Trip::where('user_id', $trip->user->id)
                            ->where('status', 'completed')
                            ->where('driver_stare_rate', '>', 0)
                            ->avg('driver_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Scooter owner image + rate
                if ($trip->scooter && $trip->scooter->owner) {
                    $trip->scooter->owner->image          = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
                    $trip->scooter->owner->id_front_image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->IDfrontImageCollection);
                    $trip->scooter->owner->id_back_image  = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->IDbackImageCollection);
                    $trip->scooter->owner->passport_image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->passportImageCollection);
                    $trip->scooter->owner->rate           = round(
                        Trip::whereHas('scooter', function ($q) use ($trip) {
                            $q->where('user_id', $trip->scooter->owner->id);
                        })->where('status', 'completed')
                          ->where('client_stare_rate', '>', 0)
                          ->avg('client_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Rename finalDestination
                $trip->final_destination = $trip->finalDestination;
                unset($trip->finalDestination);

                return $trip;
            });
    }

    return $this->sendResponse($completed_trips, null, 200);
}

public function driver_cancelled_trips()
{
    if (in_array(auth()->user()->driver_type, ['car', 'comfort_car'])) {
        $car             = Car::where('user_id', auth()->user()->id)->first();
        $cancelled_trips = Trip::where('car_id', $car->id)
            ->where('status', 'cancelled')
            ->with([
                'car.mark',
                'car.model',
                'car.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'cancelled_by',
                'finalDestination:id,trip_id,lat,lng,address',
            ])
            ->get()
            ->map(function ($trip) {

                // Distance
                $vehicle = $trip->car;
                if ($vehicle) {
                    $response = calculate_distance(
                        $vehicle->lat, $vehicle->lng,
                        $trip->start_lat, $trip->start_lng
                    );
                    $trip->client_location_distance = round($response['distance_in_km'], 2);
                    $trip->client_location_duration = intval($response['duration_in_M']);
                }

                // Barcode
                $trip->barcode = url(barcodeImage($trip->id));

                // is_driver_arrived
                $trip->is_driver_arrived = !is_null($trip->driver_arrived);

                // Clean car plate
                if ($trip->car) {
                    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
                }

                // User image + rate
                if ($trip->user) {
                    $trip->user->image          = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
                    $trip->user->id_front_image = getFirstMediaUrl($trip->user, $trip->user->IDfrontImageCollection);
                    $trip->user->id_back_image  = getFirstMediaUrl($trip->user, $trip->user->IDbackImageCollection);
                    $trip->user->passport_image = getFirstMediaUrl($trip->user, $trip->user->passportImageCollection);
                    $trip->user->rate           = round(
                        Trip::where('user_id', $trip->user->id)
                            ->where('status', 'completed')
                            ->where('driver_stare_rate', '>', 0)
                            ->avg('driver_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Car owner image + rate
                if ($trip->car && $trip->car->owner) {
                    $trip->car->owner->image          = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
                    $trip->car->owner->id_front_image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->IDfrontImageCollection);
                    $trip->car->owner->id_back_image  = getFirstMediaUrl($trip->car->owner, $trip->car->owner->IDbackImageCollection);
                    $trip->car->owner->passport_image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->passportImageCollection);
                    $trip->car->owner->rate           = round(
                        Trip::whereHas('car', function ($q) use ($trip) {
                            $q->where('user_id', $trip->car->owner->id);
                        })->where('status', 'completed')
                          ->where('client_stare_rate', '>', 0)
                          ->avg('client_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Rename finalDestination
                $trip->final_destination = $trip->finalDestination;
                unset($trip->finalDestination);

                return $trip;
            });

    } elseif (auth()->user()->driver_type == 'scooter') {
        $scooter         = Scooter::where('user_id', auth()->user()->id)->first();
        $cancelled_trips = Trip::where('scooter_id', $scooter->id)
            ->where('status', 'cancelled')
            ->with([
                'scooter.motorcycleMark',
                'scooter.motorcycleModel',
                'scooter.owner:id,name,country_code,phone',
                'user:id,name,country_code,phone',
                'cancelled_by',
                'finalDestination:id,trip_id,lat,lng,address',
            ])
            ->get()
            ->map(function ($trip) {

                // Distance
                $vehicle = $trip->scooter;
                if ($vehicle) {
                    $response = calculate_distance(
                        $vehicle->lat, $vehicle->lng,
                        $trip->start_lat, $trip->start_lng
                    );
                    $trip->client_location_distance = round($response['distance_in_km'], 2);
                    $trip->client_location_duration = intval($response['duration_in_M']);
                }

                // Barcode
                $trip->barcode = url(barcodeImage($trip->id));

                // is_driver_arrived
                $trip->is_driver_arrived = !is_null($trip->driver_arrived);

                // Clean scooter plate
                if ($trip->scooter) {
                    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
                }

                // User image + rate
                if ($trip->user) {
                    $trip->user->image          = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
                    $trip->user->id_front_image = getFirstMediaUrl($trip->user, $trip->user->IDfrontImageCollection);
                    $trip->user->id_back_image  = getFirstMediaUrl($trip->user, $trip->user->IDbackImageCollection);
                    $trip->user->passport_image = getFirstMediaUrl($trip->user, $trip->user->passportImageCollection);
                    $trip->user->rate           = round(
                        Trip::where('user_id', $trip->user->id)
                            ->where('status', 'completed')
                            ->where('driver_stare_rate', '>', 0)
                            ->avg('driver_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Scooter owner image + rate
                if ($trip->scooter && $trip->scooter->owner) {
                    $trip->scooter->owner->image          = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
                    $trip->scooter->owner->id_front_image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->IDfrontImageCollection);
                    $trip->scooter->owner->id_back_image  = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->IDbackImageCollection);
                    $trip->scooter->owner->passport_image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->passportImageCollection);
                    $trip->scooter->owner->rate           = round(
                        Trip::whereHas('scooter', function ($q) use ($trip) {
                            $q->where('user_id', $trip->scooter->owner->id);
                        })->where('status', 'completed')
                          ->where('client_stare_rate', '>', 0)
                          ->avg('client_stare_rate') ?? 5.00,
                        1
                    );
                }

                // Rename finalDestination
                $trip->final_destination = $trip->finalDestination;
                unset($trip->finalDestination);

                return $trip;
            });
    }

    return $this->sendResponse($cancelled_trips, null, 200);
}

    public function driver_arriving(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat'     => 'required|numeric|between:-90,90',
            'lng'     => 'required|numeric|between:-180,180',
            'trip_id' => 'required|exists:trips,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $trip = Trip::with(['car', 'scooter'])->find($request->trip_id);

        if (!$trip) {
            return $this->sendError(null, 'Trip not found', 404);
        }

        $driverLat = $request->lat;
        $driverLng = $request->lng;
        $startLat  = $trip->start_lat;
        $startLng  = $trip->start_lng;
        $distance  = $this->calculateDistance($driverLat, $driverLng, $startLat, $startLng);

        if ($distance <= 40) {

            // Only save first time of arriving
            if (!$trip->driver_arrived) {
                $trip->driver_arrived = now();
                $trip->save();
            }

            $data = [
                'trip_id'    => $trip->id,
                'message'    => 'Driver arrived on pickup point',
                'distance'   => $distance,
                'arrived_at' => $trip->driver_arrived,
            ];

            // Notify passenger
            event(new \App\Events\DriverArriving($data, $trip->user_id));

            // Safely resolve driver user_id and notify driver
            $driverId = null;
            if ($trip->car_id && $trip->car) {
                $driverId = $trip->car->user_id;
            } elseif ($trip->scooter_id && $trip->scooter) {
                $driverId = $trip->scooter->user_id;
            }

            if ($driverId && $driverId !== $trip->user_id) {
                event(new \App\Events\DriverArriving($data, $driverId));
            }

            return $this->sendResponse([
                'distance'   => $distance,
                'arrived_at' => $trip->driver_arrived,
            ], 'You are close to pickup point', 200);
        }

        return $this->sendError([
            'distance' => $distance,
        ], 'You are not close enough', 404);
    }
public function driver_reached(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lat'     => 'required|numeric|between:-90,90',
        'lng'     => 'required|numeric|between:-180,180',
        'trip_id' => 'required|exists:trips,id',
    ]);

    if ($validator->fails()) {
        $errors = implode(" / ", $validator->errors()->all());
        return $this->sendError(null, $errors, 400);
    }

    $trip = Trip::find($request->trip_id);

    if (!$trip) {
        return $this->sendError(null, 'Trip not found', 404);
    }

    // Trip must be in progress
    if ($trip->status != 'in_progress') {
        return $this->sendError(null, 'Trip is not in progress', 400);
    }

    // Get final destination
    $destination = $trip->finalDestination()->orderBy('id', 'desc')->first();


    if (!$destination) {
        return $this->sendError(null, 'No destination found', 400);
    }

    $distance = $this->calculateDistance(
        $request->lat,
        $request->lng,
        $destination->lat,
        $destination->lng
    );

    if ($distance <= 100) {

        $trip->load(['user', 'car.mark', 'car.model', 'finalDestination','scooter.mark','scooter.model']);

        $data = [
            'trip_id'  => $trip->id,
            'message'  => 'Driver reached destination',
            'distance' => $distance,
            'trip'     => $trip,
        ];

        // Notify passenger
        event(new \App\Events\DriverReached($data, $trip->user_id));

        // Notify driver
        $driverId = $trip->car_id ? $trip->car->user_id : $trip->scooter->user_id;
        event(new \App\Events\DriverReached($data, $driverId));

        return $this->sendResponse([
            'distance' => $distance,
        ], 'You have reached the destination', 200);
    }

    return $this->sendError([
        'distance' => $distance,
    ], 'You are not close enough to destination', 400);
}
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // distance in meters
    }
    public function track_vehicle(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lat'     => 'required|numeric|between:-90,90',
        'lng'     => 'required|numeric|between:-180,180',
        'trip_id' => 'required|exists:trips,id',
    ]);

    if ($validator->fails()) {
        return $this->sendError(
            null,
            implode(" / ", $validator->errors()->all()),
            422
        );
    }

    $trip = Trip::find($request->trip_id);

    if (!$trip) {
        return $this->sendError(null, 'Trip not found', 404);
    }

    if ($trip->status !== 'pending') {
        return $this->sendError(
            null,
            'Tracking not available',
            403
        );
    }

    $response = calculate_distance(
        $request->lat,
        $request->lng,
        $trip->start_lat,
        $trip->start_lng
    );

    if (!$response) {
        return $this->sendError(null, 'Distance calculation failed', 500);
    }

    $distance = round($response['distance_in_km'], 2);
    $duration = intval($response['duration_in_M']);
    $eta      = now()->addMinutes($duration)->format('h:i A');

    event(new \App\Events\TrackVehicle(
        $distance,
        $duration,
        $eta,
        $trip->user_id
    ));

    return $this->sendResponse([
        'distance' => $distance,
        'duration' => $duration,
        'eta'      => $eta,
        'status'   => 'pending'
    ], 'Location tracked successfully', 200);
}
    // public function create_offer(Request $request)
    // {
    //     $check_account = $this->check_banned();
    //     if ($check_account != true) {
    //         return $this->sendError(null, $check_account, 400);
    //     }
    //     $validator = Validator::make($request->all(), [
    //         'trip_id' => [
    //             'required',
    //             Rule::exists('trips', 'id'),
    //         ],
    //         'offer'   => 'required',

    //     ]);
    //     // dd($request->all());
    //     if ($validator->fails()) {

    //         $errors = implode(" / ", $validator->errors()->all());

    //         return $this->sendError(null, $errors, 400);
    //     }
    //     $driver_car = Car::where('user_id', auth()->user()->id)->first();
    //     $lastOffer  = Offer::orderBy('id', 'desc')->first();

    //     if ($lastOffer) {
    //         $lastCode = $lastOffer->code;
    //         $code     = 'OFR-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
    //     } else {
    //         $code = 'OFR-000001';
    //     }
    //     $offer = Offer::create(['user_id' => auth()->user()->id,
    //         'code'                            => $code,
    //         'car_id'                          => $driver_car->id,
    //         'trip_id'                         => intval($request->trip_id),
    //         'offer'                           => floatval($request->offer)]);
    //     $trip = Trip::findOrFail($request->trip_id);
    //     if ($trip->user->device_token) {
    //         $this->firebaseService->sendNotification($trip->user->device_token, 'Lady Driver - New Offer', "Offer No. (" . $offer->code . ") was created on your trip by Captain (" . auth()->user()->name . ").", ["screen" => "Current Trip", "ID" => $trip->id]);
    //         $data = [
    //             "title"   => "Lady Driver - New Offer",
    //             "message" => "Offer No. (" . $offer->code . ") was created on your trip by Captain (" . auth()->user()->name . ").",
    //             "screen"  => "Current Trip",
    //             "ID"      => $trip->id,
    //         ];
    //         Notification::create(['user_id' => $trip->user_id, 'data' => json_encode($data)]);
    //     }
    //     return $this->sendResponse($offer, null, 200);

    // }

    // public function expire_offer($id)
    // {
    //     $offer         = Offer::find($id);
    //     $offer->status = 'expired';
    //     $offer->save();
    //     return $this->sendResponse(null, 'Offer is expired', 200);
    // }
}

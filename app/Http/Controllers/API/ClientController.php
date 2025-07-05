<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\Car;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\Suggestion;
use App\Models\Trip;
use App\Models\TripCancellingReason;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function create_temporary_trip(Request $request)
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }

        $validator = Validator::make($request->all(), [
            'start_date'      => 'nullable|date|date_format:Y-m-d',
            'start_time'      => 'nullable|date_format:H:i', // Optional time format validation (24-hour)
            'start_lat'       => 'required|numeric|between:-90,90',
            'start_lng'       => 'required|numeric|between:-180,180',
            'end_lat'         => 'required|numeric|between:-90,90',
            'end_lng'         => 'required|numeric|between:-180,180',
            'type'            => 'required|in:car,comfort_car,scooter',
            'air_conditioned' => 'nullable|boolean',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $type = $request->type;
        switch ($type) {
            case 'car':
                $Air_conditioning_service_price = floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $kilometer_price_short_trip     = floatval(Setting::where('key', 'kilometer_price_car_short_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $kilometer_price_long_trip      = floatval(Setting::where('key', 'kilometer_price_car_long_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $kilometer_price_medium_trip    = floatval(Setting::where('key', 'kilometer_price_car_medium_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $maximum_distance_long_trip     = floatval(Setting::where('key', 'maximum_distance_car_long_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $maximum_distance_medium_trip   = floatval(Setting::where('key', 'maximum_distance_car_medium_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $maximum_distance_short_trip    = floatval(Setting::where('key', 'maximum_distance_car_short_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $increase_rate_peak_time_trip   = floatval(Setting::where('key', 'increase_rate_peak_time_car_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                $less_cost_for_trip             = floatval(Setting::where('key', 'less_cost_for_car_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
                break;

            case 'comfort_car':
                $Air_conditioning_service_price = 0;
                $kilometer_price_short_trip     = floatval(Setting::where('key', 'kilometer_price_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $kilometer_price_long_trip      = floatval(Setting::where('key', 'kilometer_price_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $kilometer_price_medium_trip    = floatval(Setting::where('key', 'kilometer_price_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $maximum_distance_long_trip     = floatval(Setting::where('key', 'maximum_distance_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $maximum_distance_medium_trip   = floatval(Setting::where('key', 'maximum_distance_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $maximum_distance_short_trip    = floatval(Setting::where('key', 'maximum_distance_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $increase_rate_peak_time_trip   = floatval(Setting::where('key', 'increase_rate_peak_time_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
                $less_cost_for_trip             = floatval(Setting::where('key', 'less_cost_for_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);

                break;

            case 'scooter':
                $Air_conditioning_service_price = 0;
                $kilometer_price_short_trip   = floatval(Setting::where('key', 'kilometer_price_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $kilometer_price_long_trip    = floatval(Setting::where('key', 'kilometer_price_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $kilometer_price_medium_trip  = floatval(Setting::where('key', 'kilometer_price_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_long_trip   = floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_medium_trip = floatval(Setting::where('key', 'maximum_distance_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_short_trip  = floatval(Setting::where('key', 'maximum_distance_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $increase_rate_peak_time_trip = floatval(Setting::where('key', 'increase_rate_peak_time_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $less_cost_for_trip           = floatval(Setting::where('key', 'less_cost_for_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                break;

            default:
                $Air_conditioning_service_price = 0;
                $kilometer_price_short_trip   = 0;
                $kilometer_price_long_trip    = 0;
                $kilometer_price_medium_trip  = 0;
                $maximum_distance_long_trip   = 0;
                $maximum_distance_medium_trip = 0;
                $maximum_distance_short_trip  = 0;
                $increase_rate_peak_time_trip = 0;
                $less_cost_for_trip           = 0;
                break;
        }

        $response = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat, $request->end_lng);
        $distance = $response['distance_in_km'];
        $duration = $response['duration_in_M'];
        dd($response);
    }
    public function create_trip(Request $request)
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }
        $validator = Validator::make($request->all(), [
            'start_lat'       => 'required',
            'start_lng'       => 'required',
            'end_lat'         => 'required',
            'end_lng'         => 'required',
            'type'            => 'required',
            'air_conditioned' => 'nullable|boolean',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $response = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat, $request->end_lng);
        $distance = $response['distance_in_km'];
        $duration = $response['duration_in_M'];

        $kilometer_price                = floatval(Setting::where('key', 'kilometer_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
        $Air_conditioning_service_price = floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
        $app_ratio                      = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
        if ($request->air_conditioned == 1) {
            $driver_rate = round(($distance * $kilometer_price) + $Air_conditioning_service_price, 2);
        } else {
            $driver_rate = round($distance * $kilometer_price, 2);
        }
        $app_rate    = round(($distance * $kilometer_price * $app_ratio) / 100, 2);
        $total_price = $driver_rate + $app_rate;

        $lastTrip = Trip::orderBy('id', 'desc')->first();

        if ($lastTrip) {
            $lastCode = $lastTrip->code;
            $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $code = 'TRP-000001';
        }
        $trip = Trip::create(['user_id' => auth()->user()->id,
            'code'                          => $code,

            'start_lat'                     => floatval($request->start_lat),
            'start_lng'                     => floatval($request->start_lng),
            'end_lat'                       => floatval($request->end_lat),
            'end_lng'                       => floatval($request->end_lng),
            'address1'                      => $request->address1,
            'address2'                      => $request->address2,
            'total_price'                   => $total_price,
            'app_rate'                      => $app_rate,
            'driver_rate'                   => $driver_rate,
            'distance'                      => $distance,
            'type'                          => $request->type,
        ]);
        if ($request->air_conditioned == '1') {
            $trip->air_conditioned = '1';
        } else {
            $trip->air_conditioned = '0';
        }
        if ($request->animal == '1') {
            $trip->animals = '1';
        } else {
            $trip->animals = '0';
        }

        $trip->save();

        $trip->duration = $duration;

        $radius        = 6371;
        $decimalPlaces = 2;

        $eligibleCars = Car::where('status', 'confirmed')
            ->whereHas('owner', function ($query) {
                $query->where('is_online', '1')
                    ->where('status', 'confirmed');
            })
            ->where(function ($query) use ($trip) {
                if ($trip->air_conditioned == '1') {
                    $query->where('air_conditioned', '1');
                }
                if ($trip->animals == '1') {
                    $query->where('animals', '1');
                }
                if ($trip->user->gendor == 'Male') {
                    $query->where('passenger_type', 'male_female');
                }
            })
            ->select('*')
            ->selectRaw("ROUND(( $radius * acos( cos( radians($trip->start_lat) ) * cos( radians(lat) ) * cos( radians(lng) - radians($trip->start_lng) ) + sin( radians($trip->start_lat) ) * sin( radians(lat) ) ) ), $decimalPlaces) AS distance")
            ->having('distance', '<=', 3)
            ->get()->map(function ($car) use ($trip) {
            $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
            $distance = $response['distance_in_km'];
            if ($distance <= 3) {

                return $car;
            }
        });
        $eligibleDriverIds = [];
        foreach ($eligibleCars as $car) {
            $eligibleDriverIds[] = $car->user_id;
            if ($car->owner->device_token) {
                $this->firebaseService->sendNotification($car->owner->device_token, 'Lady Driver - New Trip', "There is a new trip created in your current area", ["screen" => "New Trip", "ID" => $trip->id]);
                $data = [
                    "title"   => "Lady Driver - New Trip",
                    "message" => "There is a new trip created in your current area",
                    "screen"  => "New Trip",
                    "ID"      => $trip->id,
                ];
                Notification::create(['user_id' => $car->user_id, 'data' => json_encode($data)]);
            }
        }

        return $this->sendResponse($trip, 'Trip Created Successfuly.', 200);
        //dd($distance);

    }

    public function expire_trip($id)
    {
        $trip         = Trip::find($id);
        $trip->status = 'expired';
        $trip->save();
        Offer::where('trip_id', $trip->id)->update(['status' => 'expired']);
        return $this->sendResponse(null, 'Trip is expired', 200);
    }

    public function current_trip()
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }
        $trip = Trip::where('user_id', auth()->user()->id)->whereIn('status', ['created', 'pending', 'in_progress'])->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }])->first();

        if ($trip) {
            $response       = calculate_distance($trip->start_lat, $trip->start_lng, $trip->end_lat, $trip->end_lng);
            $trip_distance  = $response['distance_in_km'];
            $trip_duration  = $response['duration_in_M'];
            $trip->duration = $trip_duration;
            if ($trip->status == 'pending' || $trip->status == 'in_progress') {
                $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
                $driver_                 = $trip->car->owner;
                $trip->car->owner->rate  = Trip::whereHas('car', function ($query) use ($driver_) {
                    $query->where('user_id', $driver_->id);
                })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 0.00;
                $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
            }
            if ($trip->status == 'pending') {
                $response                       = calculate_distance($trip->car->lat, $trip->car->lng, $trip->start_lat, $trip->start_lng);
                $distance                       = $response['distance_in_km'];
                $duration                       = $response['duration_in_M'];
                $trip->client_location_distance = $distance;
                $trip->client_location_duration = $duration;
            }
            if ($trip->status == 'created') {
                $app_ratio     = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
                $pendingOffers = $trip->offers()->where('status', 'pending')->get()->map(function ($offer) use ($trip, $app_ratio) {
                    $response = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng);
                    $distance = $response['distance_in_km'];
                    $duration = $response['duration_in_M'];

                    $offer_result['id']                       = $offer->id;
                    $offer_result['user_id']                  = $offer->user()->first()->id;
                    $offer_result['car_id']                   = $offer->car()->first()->id;
                    $offer_result['trip_id']                  = $trip->id;
                    $offer_result['client_location_distance'] = $distance;
                    $offer_result['client_location_duration'] = $duration;
                    $offer_result['offer']                    = round(($offer->offer - $trip->driver_rate) + (($offer->offer - $trip->driver_rate) * $app_ratio / 100) + $trip->total_price, 2);
                    $offer_result['user']['id']               = $offer->user()->first()->id;
                    $offer_result['user']['name']             = $offer->user()->first()->name;
                    $offer_result['user']['image']            = getFirstMediaUrl($offer->user()->first(), $offer->user()->first()->avatarCollection);
                    $driver_                                  = $offer->user()->first();
                    $offer_result['user']['rate']             = Trip::whereHas('car', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 0.00;
                    $offer_result['car']['id']            = $offer->car()->first()->id;
                    $offer_result['car']['image']         = getFirstMediaUrl($offer->car()->first(), $offer->car()->first()->avatarCollection);
                    $offer_result['car']['year']          = $offer->car()->first()->year;
                    $offer_result['car']['car_mark_id']   = $offer->car()->first()->car_mark_id;
                    $offer_result['car']['car_model_id']  = $offer->car()->first()->car_model_id;
                    $offer_result['car']['mark']['id']    = $offer->car()->first()->mark()->first()->id;
                    $offer_result['car']['mark']['name']  = $offer->car()->first()->mark()->first()->name;
                    $offer_result['car']['model']['id']   = $offer->car()->first()->model()->first()->id;
                    $offer_result['car']['model']['name'] = $offer->car()->first()->model()->first()->name;
                    $offer_result['created_at']           = $offer->created_at;
                    return $offer_result;

                });
                $trip->offers = $pendingOffers;
            }
            return $this->sendResponse($trip, null, 200);
        } else {
            return $this->sendError(null, 'no current trip existed', 400);

        }

    }

    public function accept_offer(Request $request)
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }
        $validator = Validator::make($request->all(), [
            'offer_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $offer = Offer::where('id', $value)->where('status', 'pending')->first();
                    if (! $offer) {
                        $fail('The selected offer is not pending.');
                    }
                },
            ],
            'status'   => 'required',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $offer = Offer::find($request->offer_id);
        if ($request->status == 'accepted') {
            $trip              = $offer->trip;
            $app_ratio         = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
            $trip->status      = 'pending';
            $trip->total_price = round(($offer->offer - $trip->driver_rate) + (($offer->offer - $trip->driver_rate) * $app_ratio / 100) + $trip->total_price, 2);
            $trip->driver_rate = $offer->offer;
            $trip->app_rate    = round(($offer->offer - $trip->driver_rate) + (($offer->offer - $trip->driver_rate) * $app_ratio / 100) + $trip->total_price, 2) - $offer->offer;
            $trip->car_id      = $offer->car_id;
            $trip->save();
            $offer->status = 'accepted';
            $offer->save();
            Offer::where('id', '!=', $request->offer_id)->where('trip_id', $trip->id)->update(['status' => 'expired']);
            if ($offer->user->device_token) {
                $this->firebaseService->sendNotification($offer->user->device_token, 'Lady Driver - Accept Offer', "Your offer for trip No. (" . $trip->code . ") has been approved.", ["screen" => "Current Trip", "ID" => $trip->id]);
                $data = [
                    "title"   => "Lady Driver - Accept Offer",
                    "message" => "Your offer for trip No. (" . $trip->code . ") has been approved.",
                    "screen"  => "Current Trip",
                    "ID"      => $trip->id,
                ];
                Notification::create(['user_id' => $offer->user_id, 'data' => json_encode($data)]);
            }
            return $this->sendResponse(null, 'offer accepted successfuly', 200);

        } else {
            $offer->status = 'expired';
            $offer->save();
            return $this->sendResponse(null, 'offer expired successfuly', 200);
        }

    }

    public function pay_trip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id'),
            ],
            'status'  => 'required',
        ]);

        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $trip                 = Trip::find($request->trip_id);
        $trip->payment_status = $request->status;
        $trip->save();
        $trip = Trip::find($request->trip_id);
        if ($trip->user->device_token) {
            $this->firebaseService->sendNotification($trip->user->device_token, 'Lady Driver - Trip Payment', "trip No. (" . $trip->code . ") has been paid.", ["screen" => "Current Trip", "ID" => $trip->id]);
            $data = [
                "title"   => "Lady Driver - Trip Payment",
                "message" => "trip No. (" . $trip->code . ") has been paid.",
                "screen"  => "Current Trip",
                "ID"      => $trip->id,
            ];
            Notification::create(['user_id' => $trip->user_id, 'data' => json_encode($data)]);
        }
        if ($trip->car->owner->device_token) {
            $this->firebaseService->sendNotification($trip->car->owner->device_token, 'Lady Driver - Trip Payment', "trip No. (" . $trip->code . ") has been paid.", ["screen" => "Current Trip", "ID" => $trip->id]);
            $data = [
                "title"   => "Lady Driver - Trip Payment",
                "message" => "trip No. (" . $trip->code . ") has been paid.",
                "screen"  => "Current Trip",
                "ID"      => $trip->id,
            ];
            Notification::create(['user_id' => $trip->car->user_id, 'data' => json_encode($data)]);
        }
        return $this->sendResponse(null, 'trip is paid', 200);
    }

    public function completed_trips()
    {
        $completed_trips = Trip::where('user_id', auth()->user()->id)->where('status', 'completed')->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }])->get()->map(function ($trip) {
            $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
            return $trip;

        });

        return $this->sendResponse($completed_trips, null, 200);
    }

    public function cancelled_trips()
    {
        $cancelled_trips = Trip::where('user_id', auth()->user()->id)->where('status', 'cancelled')->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'cancelled_by'])->get()->map(function ($trip) {
            $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
            return $trip;

        });

        return $this->sendResponse($cancelled_trips, null, 200);
    }

    public function rate_trip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'    => [
                'required',
                Rule::exists('trips', 'id'),
            ],
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable',
            'complaint'  => 'nullable',
            'suggestion' => 'nullable',
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $trip = Trip::find($request->trip_id);
        if (auth()->user()->mode == 'client') {
            $trip->client_stare_rate = floatval($request->rating);
            $trip->client_comment    = $request->comment;
        } elseif (auth()->user()->mode == 'driver') {
            $trip->driver_stare_rate = floatval($request->rating);
            $trip->driver_comment    = $request->comment;
        }
        $trip->save();
        if ($request->complaint != null) {
            Complaint::create(['user_id' => auth()->user()->id, 'trip_id' => $trip->id, 'complaint' => $request->complaint]);
        }
        if ($request->suggestion != null) {
            Suggestion::create(['user_id' => auth()->user()->id, 'suggestion' => $request->suggestion]);
        }
        return $this->sendResponse(null, 'trip rating saved successfuly', 200);
    }

    public function cancell_trip(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'trip_id'   => [
                'required',
                Rule::exists('trips', 'id'),
            ],

            'reason_id' => [
                'required', Rule::exists('trip_cancelling_reasons', 'id'),
            ],

        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $trip                            = Trip::find($request->trip_id);
        $trip->status                    = 'cancelled';
        $trip->cancelled_by_id           = auth()->user()->id;
        $trip->trip_cancelling_reason_id = $request->reason_id;
        $trip->save();
        return $this->sendResponse(null, 'trip cancelled successfuly', 200);

    }

    public function cancellation_reasons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => [
                'required',
            ],
        ]);
        // dd($request->all());
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $reasons = TripCancellingReason::where('type', $request->category)->get();
        return $this->sendResponse($reasons, null, 200);
    }
}

<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\Car;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Trip;
use App\Models\TripCancellingReason;
use App\Models\TripChat;
use App\Models\UserAddress;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function create_temporary_trip(Request $request)
    {
        if ($request->mock) {
            $response = [
                'start_date'      => '2025-12-08',
                'start_time'      => '18:06',
                'start_lat'       => 29.2154558,
                'start_lng'       => 31.2154875,
                'end_lat_1'       => 29.2154558,
                'end_lng_1'       => 30.3333333,
                'end_lat_2'       => 29.2154558,
                'end_lng_2'       => 30.3333333,
                'end_lat_3'       => null,
                'end_lng_3'       => null,
                'air_conditioned' => true,
                'distance'        => 100.21,
                'duration'        => 50,
                'car'             => ['discount' => 0,
                    'total_cost'                     => 125.50,
                ],
                'comfort'         => ['discount' => 0,
                    'total_cost'                     => 125.50,
                ],
                'scooter'         => ['discount' => 0,
                    'total_cost'                     => 125.50,
                ],
            ];
            return $this->sendResponse($response, null, 200);
        }
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }
        $validator = Validator::make($request->all(), [
            'start_date'      => 'nullable|date|date_format:Y-m-d',
            'start_time'      => 'nullable|date_format:H:i', // Optional time format validation (24-hour)
            'start_lat'       => 'required|numeric|between:-90,90',
            'start_lng'       => 'required|numeric|between:-180,180',
            'end_lat_1'       => 'required|numeric|between:-90,90',
            'end_lng_1'       => 'required|numeric|between:-180,180',
            'end_lat_2'       => 'nullable|numeric|between:-90,90',
            'end_lng_2'       => 'nullable|numeric|between:-180,180',
            'end_lat_3'       => 'nullable|numeric|between:-90,90',
            'end_lng_3'       => 'nullable|numeric|between:-180,180',
            // 'type'            => 'required|in:car,comfort_car,scooter',
            'air_conditioned' => 'nullable|boolean',
        ]);
        // dd($request->all());
        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $peakJson  = Setting::where('key', 'peak_times')->where('category', 'Trips')->where('type', 'options')->first()->value;
        $peakTimes = json_decode($peakJson, true);
        if ($request->start_date == null || $request->start_time == null) {
            $start_date = now()->toDateString();
            $start_time = now()->format('H:i');
        } elseif ($request->start_date != null && $request->start_time != null) {
            $start_date = date('Y-m-d', strtotime($request->start_date));
            $start_time = date('H:i', strtotime($request->start_time));
        }
        $day = date('l', strtotime($start_date));

        $isPeak = false;

        if (isset($peakTimes[$day])) {
            foreach ($peakTimes[$day] as $period) {
                if ($start_time >= $period['from'] && $start_time <= $period['to']) {
                    $isPeak = true;
                    break;
                }
            }
        }
        $response['start_date'] = $start_date;
        $response['start_time'] = $start_time;
        $response['start_lat']  = (float) $request->start_lat;
        $response['start_lng']  = (float) $request->start_lng;
        $response_x             = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat_1, $request->end_lng_1);
        $distance               = $response_x['distance_in_km'];
        $duration               = $response_x['duration_in_M'];
        $response['end_lat_1']  = (float) $request->end_lat_1;
        $response['end_lng_1']  = (float) $request->end_lng_1;
        if ($request->end_lat_2 != null && $request->end_lng_2 != null) {
            $response_x            = calculate_distance($request->end_lat_1, $request->end_lng_1, $request->end_lat_2, $request->end_lng_2);
            $distance              = $distance + $response_x['distance_in_km'];
            $duration              = $duration + $response_x['duration_in_M'];
            $response['end_lat_2'] = (float) $request->end_lat_2;
            $response['end_lng_2'] = (float) $request->end_lng_2;
        } else {
            $response['end_lat_2'] = null;
            $response['end_lng_2'] = null;
        }
        if ($request->end_lat_3 != null && $request->end_lng_3 != null) {
            $response_x            = calculate_distance($request->end_lat_2, $request->end_lng_2, $request->end_lat_3, $request->end_lng_3);
            $distance              = $distance + $response_x['distance_in_km'];
            $duration              = $duration + $response_x['duration_in_M'];
            $response['end_lat_3'] = (float) $request->end_lat_3;
            $response['end_lng_3'] = (float) $request->end_lng_3;
        } else {
            $response['end_lat_3'] = null;
            $response['end_lng_3'] = null;
        }

        $response['air_conditioned'] = $request->boolean('air_conditioned');
        $response['distance']        = $distance;
        $response['duration']        = $duration;
        $student                     = Student::where('user_id', auth()->user()->id)->where('status', 'confirmed')->where('student_discount_service', '1')->first();
        $student_trips_count         = Trip::where('user_id', auth()->user()->id)->where('student_trip', '1')->where('status', 'completed')->where('start_date', now()->toDateString())->count();

        $Air_conditioning_service_price = floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $kilometer_price_short_trip     = floatval(Setting::where('key', 'kilometer_price_car_short_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $kilometer_price_long_trip      = floatval(Setting::where('key', 'kilometer_price_car_long_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $kilometer_price_medium_trip    = floatval(Setting::where('key', 'kilometer_price_car_medium_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $maximum_distance_long_trip     = floatval(Setting::where('key', 'maximum_distance_car_long_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $maximum_distance_medium_trip   = floatval(Setting::where('key', 'maximum_distance_car_medium_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $maximum_distance_short_trip    = floatval(Setting::where('key', 'maximum_distance_car_short_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $increase_rate_peak_time_trip   = floatval(Setting::where('key', 'increase_rate_peak_time_car_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $less_cost_for_trip             = floatval(Setting::where('key', 'less_cost_for_car_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $student_discount               = floatval(Setting::where('key', 'student_discount')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
        $total_cost1                    = 0;
        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km).", 400);
        }
        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance < $maximum_distance_medium_trip && $distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance < $maximum_distance_long_trip && $distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }
        if ($Air_conditioning_service_price > 0 && $request->air_conditioned == '1') {
            $air_conditioning_cost = round($total_cost1 * ($Air_conditioning_service_price / 100), 4);
        } else {
            $air_conditioning_cost = 0;
        }
        if ($isPeak) {
            $peakTimeCost = round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4);
        } else {
            $peakTimeCost = 0;
        }
        $total_cost = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);
        if ($student) {
            if ($student_trips_count < 3) {
                $response['car']['discount'] = $total_cost * ($student_discount / 100);
                $total_cost                  = $total_cost - ($total_cost * ($student_discount / 100));
            } else {
                $response['car']['discount'] = 0;
            }
        } else {
            $response['car']['discount'] = 0;
        }
        if ($total_cost < $less_cost_for_trip) {
            $total_cost = $less_cost_for_trip;
        }
        $response['car']['total_cost'] = $total_cost;

        $kilometer_price_short_trip   = floatval(Setting::where('key', 'kilometer_price_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $kilometer_price_long_trip    = floatval(Setting::where('key', 'kilometer_price_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $kilometer_price_medium_trip  = floatval(Setting::where('key', 'kilometer_price_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_long_trip   = floatval(Setting::where('key', 'maximum_distance_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_medium_trip = floatval(Setting::where('key', 'maximum_distance_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_short_trip  = floatval(Setting::where('key', 'maximum_distance_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $increase_rate_peak_time_trip = floatval(Setting::where('key', 'increase_rate_peak_time_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $less_cost_for_trip           = floatval(Setting::where('key', 'less_cost_for_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $student_discount             = floatval(Setting::where('key', 'student_discount')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $total_cost1                  = 0;
        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km).", 400);
        }
        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance < $maximum_distance_medium_trip && $distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance < $maximum_distance_long_trip && $distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }
        if ($isPeak) {
            $peakTimeCost = round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4);
        } else {
            $peakTimeCost = 0;
        }
        $total_cost = ceil($total_cost1 + $peakTimeCost);
        if ($student) {
            if ($student_trips_count < 3) {
                $response['comfort']['discount'] = $total_cost * ($student_discount / 100);
                $total_cost                      = $total_cost - ($total_cost * ($student_discount / 100));
            } else {
                $response['comfort']['discount'] = 0;
            }
        } else {
            $response['comfort']['discount'] = 0;
        }
        if ($total_cost < $less_cost_for_trip) {
            $total_cost = $less_cost_for_trip;
        }
        $response['comfort']['total_cost'] = $total_cost;

        $kilometer_price_short_trip   = floatval(Setting::where('key', 'kilometer_price_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $kilometer_price_long_trip    = floatval(Setting::where('key', 'kilometer_price_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $kilometer_price_medium_trip  = floatval(Setting::where('key', 'kilometer_price_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_long_trip   = floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_medium_trip = floatval(Setting::where('key', 'maximum_distance_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_short_trip  = floatval(Setting::where('key', 'maximum_distance_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $increase_rate_peak_time_trip = floatval(Setting::where('key', 'increase_rate_peak_time_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $less_cost_for_trip           = floatval(Setting::where('key', 'less_cost_for_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $student_discount             = floatval(Setting::where('key', 'student_discount')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $total_cost1                  = 0;
        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km).", 400);
        }
        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance < $maximum_distance_medium_trip && $distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance < $maximum_distance_long_trip && $distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }
        if ($Air_conditioning_service_price > 0 && $request->air_conditioned == '1') {
            $air_conditioning_cost = round($total_cost1 * ($Air_conditioning_service_price / 100), 4);
        } else {
            $air_conditioning_cost = 0;
        }
        if ($isPeak) {
            $peakTimeCost = round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4);
        } else {
            $peakTimeCost = 0;
        }
        $total_cost = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);
        if ($student) {
            if ($student_trips_count < 3) {
                $response['scooter']['discount'] = $total_cost * ($student_discount / 100);
                $total_cost                      = $total_cost - ($total_cost * ($student_discount / 100));
            } else {
                $response['scooter']['discount'] = 0;
            }
        } else {
            $response['scooter']['discount'] = 0;
        }
        if ($total_cost < $less_cost_for_trip) {
            $total_cost = $less_cost_for_trip;
        }
        $response['scooter']['total_cost'] = $total_cost;

        return $this->sendResponse($response, null, 200);

    }

    public function current_trip()
    {
        $check_account = $this->check_banned();
        if ($check_account != true) {
            return $this->sendError(null, $check_account, 400);
        }
        $trip = Trip::where('user_id', auth()->user()->id)->whereIn('status', ['created', 'pending', 'in_progress'])->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'scooter' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'finalDestination' => function ($xx) {
            $xx->orderBy('id');
        }])->first();

        if ($trip) {
            $totalDistance = 0;
            $totalDuration = 0;

            // Start from trip starting point
            $prevLat = $trip->start_lat;
            $prevLng = $trip->start_lng;

            foreach ($trip->finalDestination as $destination) {
                $response = calculate_distance($prevLat, $prevLng, $destination->lat, $destination->lng);

                $totalDistance += $response['distance_in_km'];
                $totalDuration += $response['duration_in_M'];

                // update previous point
                $prevLat = $destination->lat;
                $prevLng = $destination->lng;
            }

            $trip_distance  = $totalDistance;
            $trip_duration  = $totalDuration;
            $trip->duration = $trip_duration;
            $barcode_image  = url(barcodeImage($trip->id));
            $trip->barcode  = $barcode_image;
            if ($trip->status == 'pending' || $trip->status == 'in_progress') {
                if (in_array($trip->type, ['car', 'comfort_car'])) {
                    $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
                    $driver_                 = $trip->car->owner;
                    $trip->car->owner->rate  = Trip::whereHas('car', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                    $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
                } elseif ($trip->type == 'scooter') {
                    $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
                    $driver_                     = $trip->scooter->owner;
                    $trip->scooter->owner->rate  = Trip::whereHas('scooter', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                    $trip->scooter->image = getFirstMediaUrl($trip->scooter, $trip->scooter->avatarCollection);
                }

            }
            if ($trip->status == 'pending') {
                if (in_array($trip->type, ['car', 'comfort_car'])) {
                    $response = calculate_distance($trip->car->lat, $trip->car->lng, $trip->start_lat, $trip->start_lng);
                } elseif ($trip->type == 'scooter') {
                    $response = calculate_distance($trip->scooter->lat, $trip->scooter->lng, $trip->start_lat, $trip->start_lng);
                }
                $distance                       = $response['distance_in_km'];
                $duration                       = $response['duration_in_M'];
                $trip->client_location_distance = $distance;
                $trip->client_location_duration = $duration;
            }
            if ($trip->status == 'created') {
                $pendingOffers = $trip->offers()->where('status', 'pending')->get()->map(function ($offer) use ($trip) {
                    if (in_array($trip->type, ['car', 'comfort_car'])) {
                        $response = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng);
                    } elseif ($trip->type == 'scooter') {
                        $response = calculate_distance($offer->scooter->lat, $offer->scooter->lng, $trip->start_lat, $trip->start_lng);

                    }
                    $distance = $response['distance_in_km'];
                    $duration = $response['duration_in_M'];

                    $offer_result['id']      = $offer->id;
                    $offer_result['user_id'] = $offer->user()->first()->id;
                    if (in_array($trip->type, ['car', 'comfort_car'])) {
                        $offer_result['car_id'] = $offer->car()->first()->id;
                    } elseif ($trip->type == 'scooter') {
                        $offer_result['scooter_id'] = $offer->scooter()->first()->id;
                    }

                    $offer_result['trip_id']                  = $trip->id;
                    $offer_result['client_location_distance'] = $distance;
                    $offer_result['client_location_duration'] = $duration;
                    $offer_result['offer']                    = $offer->offer;
                    $offer_result['user']['id']               = $offer->user()->first()->id;
                    $offer_result['user']['name']             = $offer->user()->first()->name;
                    $offer_result['user']['image']            = getFirstMediaUrl($offer->user()->first(), $offer->user()->first()->avatarCollection);
                    $driver_                                  = $offer->user()->first();
                    if (in_array($trip->type, ['car', 'comfort_car'])) {
                        $offer_result['user']['rate'] = Trip::whereHas('car', function ($query) use ($driver_) {
                            $query->where('user_id', $driver_->id);
                        })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                        $offer_result['car']['id']            = $offer->car()->first()->id;
                        $offer_result['car']['image']         = getFirstMediaUrl($offer->car()->first(), $offer->car()->first()->avatarCollection);
                        $offer_result['car']['year']          = $offer->car()->first()->year;
                        $offer_result['car']['car_mark_id']   = $offer->car()->first()->car_mark_id;
                        $offer_result['car']['car_model_id']  = $offer->car()->first()->car_model_id;
                        $offer_result['car']['mark']['id']    = $offer->car()->first()->mark()->first()->id;
                        $offer_result['car']['mark']['name']  = $offer->car()->first()->mark()->first()->name;
                        $offer_result['car']['model']['id']   = $offer->car()->first()->model()->first()->id;
                        $offer_result['car']['model']['name'] = $offer->car()->first()->model()->first()->name;
                    } elseif ($trip->type == 'scooter') {
                        $offer_result['user']['rate'] = Trip::whereHas('car', function ($query) use ($driver_) {
                            $query->where('user_id', $driver_->id);
                        })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                        $offer_result['scooter']['id']            = $offer->scooter()->first()->id;
                        $offer_result['scooter']['image']         = getFirstMediaUrl($offer->scooter()->first(), $offer->scooter()->first()->avatarCollection);
                        $offer_result['scooter']['year']          = $offer->scooter()->first()->year;
                        $offer_result['scooter']['car_mark_id']   = $offer->scooter()->first()->motorcycle_mark_id;
                        $offer_result['scooter']['car_model_id']  = $offer->scooter()->first()->motorcycle_model_id;
                        $offer_result['scooter']['mark']['id']    = $offer->scooter()->first()->mark()->first()->id;
                        $offer_result['scooter']['mark']['name']  = $offer->scooter()->first()->mark()->first()->name;
                        $offer_result['scooter']['model']['id']   = $offer->scooter()->first()->model()->first()->id;
                        $offer_result['scooter']['model']['name'] = $offer->scooter()->first()->model()->first()->name;
                    }
                    $offer_result['created_at'] = $offer->created_at;
                    return $offer_result;

                });
                $trip->offers = $pendingOffers;
            }
            return $this->sendResponse($trip, null, 200);
        } else {
            return $this->sendError(null, 'no current trip existed', 400);

        }

    }

    // public function pay_trip(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'trip_id' => [
    //             'required',
    //             Rule::exists('trips', 'id'),
    //         ],
    //         'status'  => 'required',
    //     ]);

    //     // dd($request->all());
    //     if ($validator->fails()) {

    //         $errors = implode(" / ", $validator->errors()->all());

    //         return $this->sendError(null, $errors, 400);
    //     }
    //     $trip                 = Trip::find($request->trip_id);
    //     $trip->payment_status = $request->status;
    //     $trip->save();
    //     $trip = Trip::find($request->trip_id);
    //     if ($trip->user->device_token) {
    //         $this->firebaseService->sendNotification($trip->user->device_token, 'Lady Driver - Trip Payment', "trip No. (" . $trip->code . ") has been paid.", ["screen" => "Current Trip", "ID" => $trip->id]);
    //         $data = [
    //             "title"   => "Lady Driver - Trip Payment",
    //             "message" => "trip No. (" . $trip->code . ") has been paid.",
    //             "screen"  => "Current Trip",
    //             "ID"      => $trip->id,
    //         ];
    //         Notification::create(['user_id' => $trip->user_id, 'data' => json_encode($data)]);
    //     }
    //     if ($trip->car->owner->device_token) {
    //         $this->firebaseService->sendNotification($trip->car->owner->device_token, 'Lady Driver - Trip Payment', "trip No. (" . $trip->code . ") has been paid.", ["screen" => "Current Trip", "ID" => $trip->id]);
    //         $data = [
    //             "title"   => "Lady Driver - Trip Payment",
    //             "message" => "trip No. (" . $trip->code . ") has been paid.",
    //             "screen"  => "Current Trip",
    //             "ID"      => $trip->id,
    //         ];
    //         Notification::create(['user_id' => $trip->car->user_id, 'data' => json_encode($data)]);
    //     }
    //     return $this->sendResponse(null, 'trip is paid', 200);
    // }

    public function completed_trips()
    {
        $completed_trips = Trip::where('user_id', auth()->user()->id)->where('status', 'completed')->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'scooter' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }])->get()->map(function ($trip) {
            if (in_array($trip->type, ['car', 'comfort_car'])) {
                $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
            } elseif ($trip->type == 'scooter') {
                $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
            }
            return $trip;

        });

        return $this->sendResponse($completed_trips, null, 200);
    }

    public function cancelled_trips()
    {
        $cancelled_trips = Trip::where('user_id', auth()->user()->id)->where('status', 'cancelled')->with(['car' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'scooter' => function ($query) {
            $query->with(['mark', 'model', 'owner']);
        }, 'cancelled_by', 'cancelling_reason'])->get()->map(function ($trip) {
            if (in_array($trip->type, ['car', 'comfort_car'])) {
                $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
            } elseif ($trip->type == 'scooter') {
                $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
            }
            return $trip;

        });

        return $this->sendResponse($cancelled_trips, null, 200);
    }

    // public function rate_trip(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'trip_id'    => [
    //             'required',
    //             Rule::exists('trips', 'id'),
    //         ],
    //         'rating'     => 'required|integer|min:1|max:5',
    //         'comment'    => 'nullable',
    //         'complaint'  => 'nullable',
    //         'suggestion' => 'nullable',
    //     ]);
    //     // dd($request->all());
    //     if ($validator->fails()) {

    //         $errors = implode(" / ", $validator->errors()->all());

    //         return $this->sendError(null, $errors, 400);
    //     }
    //     $trip = Trip::find($request->trip_id);
    //     if (auth()->user()->mode == 'client') {
    //         $trip->client_stare_rate = floatval($request->rating);
    //         $trip->client_comment    = $request->comment;
    //     } elseif (auth()->user()->mode == 'driver') {
    //         $trip->driver_stare_rate = floatval($request->rating);
    //         $trip->driver_comment    = $request->comment;
    //     }
    //     $trip->save();
    //     if ($request->complaint != null) {
    //         Complaint::create(['user_id' => auth()->user()->id, 'trip_id' => $trip->id, 'complaint' => $request->complaint]);
    //     }
    //     if ($request->suggestion != null) {
    //         Suggestion::create(['user_id' => auth()->user()->id, 'suggestion' => $request->suggestion]);
    //     }
    //     return $this->sendResponse(null, 'trip rating saved successfuly', 200);
    // }

    // public function cancel_trip(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'trip_id'   => [
    //             'required',
    //             Rule::exists('trips', 'id'),
    //         ],

    //         'reason_id' => [
    //             'required', Rule::exists('trip_cancelling_reasons', 'id'),
    //         ],

    //     ]);
    //     // dd($request->all());
    //     if ($validator->fails()) {

    //         $errors = implode(" / ", $validator->errors()->all());

    //         return $this->sendError(null, $errors, 400);
    //     }
    //     $trip                            = Trip::find($request->trip_id);
    //     $trip->status                    = 'cancelled';
    //     $trip->cancelled_by_id           = auth()->user()->id;
    //     $trip->trip_cancelling_reason_id = $request->reason_id;
    //     $trip->save();
    //     return $this->sendResponse(null, 'trip cancelled successfuly', 200);

    // }

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
        $reasons = TripCancellingReason::whereIn('type', [$request->category, 'for_all'])->get();
        return $this->sendResponse($reasons, null, 200);
    }

    // public function check_barcode(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'trip_id' => 'required|',
    //         'barcode' => 'required|string',
    //     ]);
    //     // dd($request->all());
    //     if ($validator->fails()) {
    //         $errors = implode(" / ", $validator->errors()->all());

    //         return $this->sendError(null, $errors, 400);
    //     }

    //     $trip = Trip::where('id', $request->trip_id)->first();
    //     if (! $trip) {
    //         return $this->sendError(null, 'Trip not found', 404);
    //     }
    //     if ($trip->barcode == $request->barcode) {
    //         return $this->sendResponse(null, 'Barcode verified successfully', 200);
    //     } else {
    //         return $this->sendError(null, 'Invalid barcode for this trip', 422);
    //     }

    // }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'  => 'required|exists:trips,id',
            'message'  => 'nullable|string',
            'location' => 'nullable|string',
            'image'    => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120',
            'record'   => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $chat = TripChat::create([
            'sender_id' => auth()->id(),
            'trip_id'   => $request->trip_id,
            'message'   => $request->message,
            'location'  => $request->location,
        ]);
        if ($request->file('image')) {
            uploadMedia($request->image, $chat->imageCollection, $chat);

        }
        if ($request->file('record')) {
            uploadMedia($request->record, $chat->recordCollection, $chat);

        }
        $trip = Trip::findOrFail($request->trip_id);
        if ($trip->user_id != auth()->id()) {
            $receiverId = $trip->user_id;
        } else {
            if ($trip->type == 'scooter') {
                $receiverId = $trip->scooter->user_id;
            } else {
                $receiverId = $trip->car->user_id;
            }

        }
        event(new \App\Events\NewChatMessage($chat, $receiverId));

        return $this->sendResponse($chat, 'Message sent successfully', 201);
    }

    public function getTripMessages($id)
    {
        $tripChats = TripChat::with('sender')
            ->where('trip_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->sendResponse($tripChats, 'Messages retrieved successfully', 200);
    }

    public function getMessage($id)
    {
        $tripChat = TripChat::with('sender')
            ->where('id', $id)
            ->first();
        $tripChat->seen = "1";
        $tripChat->save();

        return $this->sendResponse($tripChat, 'Message retrieved successfully', 200);
    }

    public function updateUserLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $user      = auth()->user();
        $user->lat = $request->lat;
        $user->lng = $request->lng;
        $user->save();

        return $this->sendResponse($user, 'Location updated successfully', 200);
    }

    public function get_near_drivers(Request $request)
    {
        if ($request->mock) {
            $mockDrivers = [
                [
                    'name'         => 'Ahmed Ali',
                    'vehicle_type' => 'Car',
                    'lat'          => 30.0582,
                    'lng'          => 31.3279,
                ],
                [
                    'name'         => 'Fathy Samir',
                    'vehicle_type' => 'Scooter',
                    'lat'          => 30.0582,
                    'lng'          => 31.3279,
                ],
            ];

            return $this->sendResponse([
                'count'   => count($mockDrivers),
                'drivers' => $mockDrivers,
            ], 'Mock data returned successfully.', 200);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ], [
            'lat.required' => 'Latitude is required.',
            'lng.required' => 'Longitude is required.',
            'lat.numeric'  => 'Latitude must be a valid number.',
            'lng.numeric'  => 'Longitude must be a valid number.',
        ]);

        if ($validator->fails()) {
            $errors = implode(' / ', $validator->errors()->all());
            return $this->sendError('Validation Error', $errors, 422);
        }

        $lat    = $request->lat;
        $lng    = $request->lng;
        $radius = 3; // km

        $tripFilter = fn($q) => $q->whereIn('status', ['in_progress', 'pending']);
        $haversine  = "(6371 * acos(cos(radians(?))
                * cos(radians(lat))
                * cos(radians(lng) - radians(?))
                + sin(radians(?))
                * sin(radians(lat))))";
        $carDrivers = Car::with('owner:id,name,is_online')
            ->where('status', 'confirmed')
            ->whereHas('owner', function ($q) {
                $q->where('is_online', 1)
                    ->where('status', 'confirmed');
            })
            ->whereDoesntHave('trips', $tripFilter)
            ->select('lat', 'lng', 'user_id')

            ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get()

            ->map(function ($car) {
                return [
                    'name'         => $car->owner->name ?? null,
                    'vehicle_type' => 'Car',
                    'lat'          => (float) $car->lat,
                    'lng'          => (float) $car->lng,
                ];
            });

        $scooterDrivers = Scooter::with('owner:id,name,is_online')->where('status', 'confirmed')
            ->whereHas('owner', function ($q) {
                $q->where('is_online', 1)
                    ->where('status', 'confirmed');
            })
            ->whereDoesntHave('trips', $tripFilter)
            ->select('lat', 'lng', 'user_id')
            ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get()
            ->map(function ($scooter) {
                return [
                    'name'         => $scooter->owner->name ?? null,
                    'vehicle_type' => 'Scooter',
                    'lat'          => (float) $scooter->lat,
                    'lng'          => (float) $scooter->lng,
                ];
            });

        $drivers = $carDrivers->concat($scooterDrivers)->values();

        return $this->sendResponse([
            'count'   => $drivers->count(),
            'drivers' => $drivers,
        ], 'Nearby drivers retrieved successfully.', 200);
    }
    public function add_address(Request $request)
    {
        if ($request->mock) {
            $response = [
                'id'    => 10,
                'title' => 'Home',
                'lat'   => 30.0500,
                'lng'   => 31.2400,
            ];
            return $this->sendResponse($response, null, 200);
        }

        $validator = Validator::make($request->all(), [
            'lat'   => 'required|numeric|between:-90,90',
            'lng'   => 'required|numeric|between:-180,180',
            'title' => 'required|string|max:255',
        ], [
            'lat.required'   => 'Latitude is required.',
            'lng.required'   => 'Longitude is required.',
            'title.required' => 'Address title is required.',
        ]);

        if ($validator->fails()) {
            $errors = implode(' / ', $validator->errors()->all());
            return $this->sendError('Validation Error', $errors, 422);
        }

        $user = auth()->user();
        if (! $user) {
            return $this->sendError('Unauthorized', 'User not authenticated.', 401);
        }

        // ✅ Optional: Prevent duplicate addresses for same location
        $exists = UserAddress::where('user_id', $user->id)
            ->where('lat', $request->lat)
            ->where('lng', $request->lng)
            ->exists();

        if ($exists) {
            return $this->sendError('Duplicate Address', 'This address already exists.', 409);
        }

        // ✅ Create new address
        $address = UserAddress::create([
            'user_id' => $user->id,
            'lat'     => $request->lat,
            'lng'     => $request->lng,
            'title'   => $request->title,
        ]);

        // ✅ Format response
        $response = [
            'id'    => $address->id,
            'title' => $address->title,
            'lat'   => (float) $address->lat,
            'lng'   => (float) $address->lng,
        ];

        return $this->sendResponse($response, 'Address added successfully.', 201);
    }

    public function get_all_user_addresses(Request $request)
    {
        // ✅ Mock mode (for demo/testing)
        if ($request->mock) {
            $response = [
                'count'     => 2,
                'addresses' => [
                    [
                        'id'    => 10,
                        'title' => 'Home',
                        'lat'   => 30.0459,
                        'lng'   => 31.2243,
                    ],
                    [
                        'id'    => 11,
                        'title' => 'Work',
                        'lat'   => 30.0602,
                        'lng'   => 31.3309,
                    ],
                ],
            ];

            return $this->sendResponse($response, null, 200);
        }

        // ✅ Ensure the user is authenticated
        $user = auth()->user();
        if (! $user) {
            return $this->sendError('Unauthorized', 'User not authenticated.', 401);
        }

        // ✅ Retrieve addresses for the authenticated user
        $addresses = UserAddress::where('user_id', $user->id)
            ->select('id', 'title', 'lat', 'lng')
            ->orderBy('id', 'desc')
            ->get();

        $response = [
            'count'     => $addresses->count(),
            'addresses' => $addresses,
        ];

        return $this->sendResponse($response, null, 200);
    }

    public function delete_address(Request $request)
    {
        // ✅ Mock mode for testing/demo
        if ($request->mock) {
            $response = [
                'deleted_id' => 10,
                'message'    => 'Address deleted successfully (mock).',
            ];
            return $this->sendResponse($response, null, 200);
        }

        // ✅ Validate required parameters
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|exists:user_addresses,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(' / ', $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        // ✅ Ensure user is authenticated
        $user = auth()->user();
        if (! $user) {
            return $this->sendError('Unauthorized', 'User not authenticated.', 401);
        }

        // ✅ Find address
        $address = UserAddress::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $address) {
            return $this->sendError('Not Found', 'Address not found or does not belong to user.', 404);
        }

        // ✅ Soft delete
        $address->delete();

        $response = [
            'deleted_id' => $request->address_id,
            'message'    => 'Address deleted successfully.',
        ];

        return $this->sendResponse($response, null, 200);
    }

}

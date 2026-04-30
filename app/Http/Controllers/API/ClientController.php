<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\Car;
use App\Models\Complaint;
use App\Models\RateTripSetting;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Suggestion;
use App\Models\Trip;
use App\Models\TripCancellingReason;
use App\Models\TripChat;
use App\Models\UserAddress;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ClientController extends ApiController
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    private function mockTripResponse()
    {
        return $this->sendResponse([
            'start_date'      => '2025-12-08',
            'start_time'      => '18:06',
            'start_lat'       => 29.2154558,
            'start_lng'       => 31.2154875,
            'end_lat_1'       => 29.2154558,
            'end_lng_1'       => 30.3333333,
            'air_conditioned' => true,
            'distance'        => 100.21,
            'duration'        => 50,
            'car'             => ['discount' => 0, 'total_cost' => 125.50],
            'comfort'         => ['discount' => 0, 'total_cost' => 125.50],
            'scooter'         => ['discount' => 0, 'total_cost' => 125.50],
        ], null, 200);
    }

    private function resolveStartDateTime($request)
    {
        $date = $request->start_date ?? now()->toDateString();
        $time = $request->start_time ?? now()->format('H:i');
        return [date('Y-m-d', strtotime($date)), date('H:i', strtotime($time))];
    }

    private function calculateTripPath($request)
    {
        $distance = 0;
        $duration = 0;
        $response = [
            'start_lat' => $request->start_lat,
            'start_lng' => $request->start_lng,
        ];

        $points = [
            [$request->end_lat_1, $request->end_lng_1],
            [$request->end_lat_2, $request->end_lng_2],
            [$request->end_lat_3, $request->end_lng_3],
        ];

        $prevLat = $request->start_lat;
        $prevLng = $request->start_lng;

        foreach ($points as $i => $point) {
            [$lat, $lng] = $point;
            if ($lat && $lng) {
                $calc                             = calculate_distance($prevLat, $prevLng, $lat, $lng);
                $distance                        += $calc['distance_in_km'];
                $duration                        += $calc['duration_in_M'];
                $response["end_lat_" . ($i + 1)]  = $lat;
                $response["end_lng_" . ($i + 1)]  = $lng;
                $prevLat                          = $lat;
                $prevLng                          = $lng;
            } else {
                $response["end_lat_" . ($i + 1)] = null;
                $response["end_lng_" . ($i + 1)] = null;
            }
        }

        return [$distance, $duration, $response];
    }

    private function isPeakTime($date, $time)
    {
        $peakJson = Setting::where('key', 'peak_times')
            ->where('category', 'Trips')
            ->where('type', 'options')
            ->first()?->value;

        $peakTimes = json_decode($peakJson, true) ?? [];
        $day       = date('l', strtotime($date));

        if (! isset($peakTimes[$day])) {
            return false;
        }

        foreach ($peakTimes[$day] as $period) {
            if ($time >= $period['from'] && $time <= $period['to']) {
                return true;
            }
        }
        return false;
    }

    private function calculateTripCost($category, $distance, $isPeak, $airConditioned, $student, $studentTripsCount)
    {
        // Fetch all settings in one go
        $settings = Setting::where('category', $category)
            ->pluck('value', 'key')
            ->map(fn($v) => floatval($v));

        $short  = $settings["kilometer_price_" . strtolower(explode(' ', $category)[0]) . "_short_trip"] ?? 0;
        $medium = $settings["kilometer_price_" . strtolower(explode(' ', $category)[0]) . "_medium_trip"] ?? 0;
        $long   = $settings["kilometer_price_" . strtolower(explode(' ', $category)[0]) . "_long_trip"] ?? 0;

        $maxShort  = $settings["maximum_distance_" . strtolower(explode(' ', $category)[0]) . "_short_trip"] ?? 0;
        $maxMedium = $settings["maximum_distance_" . strtolower(explode(' ', $category)[0]) . "_medium_trip"] ?? 0;
        $maxLong   = $settings["maximum_distance_" . strtolower(explode(' ', $category)[0]) . "_long_trip"] ?? 0;

        $lessCost          = $settings["less_cost_for_" . strtolower(explode(' ', $category)[0]) . "_trip"] ?? 0;
        $peakRate          = $settings["increase_rate_peak_time_" . strtolower(explode(' ', $category)[0]) . "_trip"] ?? 0;
        $studentDiscount   = $settings["student_discount"] ?? 0;
        $airConditionPrice = Setting::where('key', 'Air_conditioning_service_price')->first()?->value ?? 0;

        if ($distance > $maxLong) {
            throw new \Exception("Trip distance ($distance km) exceeds max allowed ($maxLong km).");
        }

        $cost = 0;

        $cost += $short * min($distance, $maxShort);
        if ($distance > $maxShort) {
            $cost += $medium * (min($distance, $maxMedium) - $maxShort);
        }
        if ($distance > $maxMedium) {
            $cost += $long * (min($distance, $maxLong) - $maxMedium);
        }

        if ($isPeak) {
            $cost += round($cost * ($peakRate / 100), 4);
        }

        if ($airConditionPrice > 0 && $airConditioned) {
            $cost += round($cost * ($airConditionPrice / 100), 4);
        }

        $discount = 0;
        if ($student && $studentTripsCount < 3) {
            $discount  = $cost * ($studentDiscount / 100);
            $cost     -= $discount;
        }

        if ($cost < $lessCost) {
            $cost = $lessCost;
        }

        return [
            'discount'   => $discount,
            'total_cost' => ceil($cost),
        ];
    }
    // public function create_temporary_trip(Request $request)
    // {
    //     if ($request->mock) {
    //         return $this->mockTripResponse();
    //     }

    //     // Check banned users
    //     $check_account = $this->check_banned();
    //     if ($check_account !== true) {
    //         return $this->sendError(null, $check_account, 400);
    //     }

    //     // ✅ Validate request
    //     $validator = Validator::make($request->all(), [
    //         'start_date'      => 'nullable|date|date_format:Y-m-d',
    //         'start_time'      => 'nullable|date_format:H:i',
    //         'start_lat'       => 'required|numeric|between:-90,90',
    //         'start_lng'       => 'required|numeric|between:-180,180',
    //         'end_lat_1'       => 'required|numeric|between:-90,90',
    //         'end_lng_1'       => 'required|numeric|between:-180,180',
    //         'end_lat_2'       => 'nullable|numeric|between:-90,90',
    //         'end_lng_2'       => 'nullable|numeric|between:-180,180',
    //         'end_lat_3'       => 'nullable|numeric|between:-90,90',
    //         'end_lng_3'       => 'nullable|numeric|between:-180,180',
    //         'air_conditioned' => 'nullable|boolean',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError(null, implode(" / ", $validator->errors()->all()), 400);
    //     }

    //     // ✅ Setup start time/date
    //     [$start_date, $start_time] = $this->resolveStartDateTime($request);

    //     // ✅ Calculate route distance & duration
    //     [$distance, $duration, $response] = $this->calculateTripPath($request);

    //     // ✅ Determine if peak time
    //     $isPeak = $this->isPeakTime($start_date, $start_time);

    //     // ✅ Student logic
    //     $student = Student::where('user_id', auth()->id())
    //         ->where('status', 'confirmed')
    //         ->where('student_discount_service', 1)
    //         ->first();

    //     $student_trips_count = Trip::where('user_id', auth()->id())
    //         ->where('student_trip', 1)
    //         ->where('status', 'completed')
    //         ->where('start_date', now()->toDateString())
    //         ->count();

    //     $air_conditioned = $request->boolean('air_conditioned');

    //     // ✅ Calculate for each vehicle type
    //     $response['start_date']      = $start_date;
    //     $response['start_time']      = $start_time;
    //     $response['air_conditioned'] = $air_conditioned;
    //     $response['distance']        = $distance;
    //     $response['duration']        = $duration;

    //     foreach (['Car Trips' => 'car', 'Comfort Trips' => 'comfort', 'Scooter Trips' => 'scooter'] as $category => $key) {
    //         $response[$key] = $this->calculateTripCost(
    //             $category,
    //             $distance,
    //             $isPeak,
    //             $air_conditioned,
    //             $student,
    //             $student_trips_count
    //         );
    //     }

    //     return $this->sendResponse($response, null, 200);
    // }

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
                'car'             => [
                    'total_cost_before_discount' => 125.50,
                    'discount'                   => 0,
                    'total_cost'                 => 125.50,
                    'distance'                   => 100.21,
                    'duration'                   => 50,
                ],
                'comfort_car'     => [
                    'total_cost_before_discount' => 135.50,
                    'discount'                   => 10,
                    'total_cost'                 => 125.50,
                    'distance'                   => 100.21,
                    'duration'                   => 50,
                ],
                'scooter'         => [
                    'total_cost_before_discount' => 145.50,
                    'discount'                   => 20,
                    'total_cost'                 => 125.50,
                    'distance'                   => 100.21,
                    'duration'                   => 50,
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
            'start_time'      => 'nullable|date_format:H:i',
            'start_lat'       => 'required|numeric|between:-90,90',
            'start_lng'       => 'required|numeric|between:-180,180',
            'end_lat_1'       => 'required|numeric|between:-90,90',
            'end_lng_1'       => 'required|numeric|between:-180,180',
            'end_lat_2'       => 'nullable|numeric|between:-90,90',
            'end_lng_2'       => 'nullable|numeric|between:-180,180',
            'end_lat_3'       => 'nullable|numeric|between:-90,90',
            'end_lng_3'       => 'nullable|numeric|between:-180,180',
            'air_conditioned' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            $errors = implode(" / ", $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        // ── Peak time ──
        $peakJson  = Setting::where('key', 'peak_times')->where('category', 'Trips')->where('type', 'options')->first()->value;
        $peakTimes = json_decode($peakJson, true);

        if ($request->start_date == null || $request->start_time == null) {
            $start_date = now()->toDateString();
            $start_time = now()->format('H:i');
        } else {
            $start_date = date('Y-m-d', strtotime($request->start_date));
            $start_time = date('H:i', strtotime($request->start_time));
        }

        $day    = date('l', strtotime($start_date));
        $isPeak = false;

        if (isset($peakTimes[$day])) {
            foreach ($peakTimes[$day] as $period) {
                if ($start_time >= $period['from'] && $start_time <= $period['to']) {
                    $isPeak = true;
                    break;
                }
            }
        }

        // ── Base response fields ──
        $response                    = [];
        $response['start_date']      = $start_date;
        $response['start_time']      = $start_time;
        $response['start_lat']       = (float) $request->start_lat;
        $response['start_lng']       = (float) $request->start_lng;
        $response['air_conditioned'] = $request->boolean('air_conditioned');
        $response['end_lat_1']       = (float) $request->end_lat_1;
        $response['end_lng_1']       = (float) $request->end_lng_1;
        $response['end_lat_2']       = $request->end_lat_2 ? (float) $request->end_lat_2 : null;
        $response['end_lng_2']       = $request->end_lng_2 ? (float) $request->end_lng_2 : null;
        $response['end_lat_3']       = $request->end_lat_3 ? (float) $request->end_lat_3 : null;
        $response['end_lng_3']       = $request->end_lng_3 ? (float) $request->end_lng_3 : null;

        // ── Student check ──
        $student             = Student::where('user_id', auth()->user()->id)
            ->where('status', 'confirmed')
            ->where('student_discount_service', '1')
            ->first();
        $student_trips_count = Trip::where('user_id', auth()->user()->id)
            ->where('student_trip', '1')
            ->where('status', 'completed')
            ->where('start_date', now()->toDateString())
            ->count();

        // ══════════════════════════════════════════
        // CAR
        // ══════════════════════════════════════════
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

        $total_cost1 = 0;
        $r           = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat_1, $request->end_lng_1, 'car');
        $distance    = $r['distance_in_km'];
        $duration    = $r['duration_in_M'];

        if ($request->end_lat_2 && $request->end_lng_2) {
            $r        = calculate_distance($request->end_lat_1, $request->end_lng_1, $request->end_lat_2, $request->end_lng_2, 'car');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }
        if ($request->end_lat_3 && $request->end_lng_3) {
            $r        = calculate_distance($request->end_lat_2, $request->end_lng_2, $request->end_lat_3, $request->end_lng_3, 'car');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }

        $response['car']['distance'] = $distance;
        $response['car']['duration'] = $duration;

        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "Trip distance ($distance km) exceeds maximum allowed ($maximum_distance_long_trip km).", 400);
        }

        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }

        $air_conditioning_cost = ($Air_conditioning_service_price > 0 && $request->air_conditioned == '1')
            ? round($total_cost1 * ($Air_conditioning_service_price / 100), 4)
            : 0;

        $peakTimeCost = $isPeak ? round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4) : 0;
        $total_cost   = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);

        $response['car']['total_cost_before_discount'] = $total_cost;

        if ($student && $student_trips_count < 3) {
            $response['car']['discount'] = $total_cost * ($student_discount / 100);
            $total_cost                  = $total_cost - ($total_cost * ($student_discount / 100));
        } else {
            $response['car']['discount'] = 0;
        }

        if ($total_cost < $less_cost_for_trip) $total_cost = $less_cost_for_trip;
        $response['car']['total_cost'] = $total_cost;

        // ══════════════════════════════════════════
        // COMFORT CAR
        // ══════════════════════════════════════════
        $kilometer_price_short_trip   = floatval(Setting::where('key', 'kilometer_price_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $kilometer_price_long_trip    = floatval(Setting::where('key', 'kilometer_price_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $kilometer_price_medium_trip  = floatval(Setting::where('key', 'kilometer_price_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_long_trip   = floatval(Setting::where('key', 'maximum_distance_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_medium_trip = floatval(Setting::where('key', 'maximum_distance_comfort_medium_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $maximum_distance_short_trip  = floatval(Setting::where('key', 'maximum_distance_comfort_short_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $increase_rate_peak_time_trip = floatval(Setting::where('key', 'increase_rate_peak_time_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $less_cost_for_trip           = floatval(Setting::where('key', 'less_cost_for_comfort_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
        $student_discount             = floatval(Setting::where('key', 'student_discount')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);

        $total_cost1 = 0;
        $r           = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat_1, $request->end_lng_1, 'comfort_car');
        $distance    = $r['distance_in_km'];
        $duration    = $r['duration_in_M'];

        if ($request->end_lat_2 && $request->end_lng_2) {
            $r        = calculate_distance($request->end_lat_1, $request->end_lng_1, $request->end_lat_2, $request->end_lng_2, 'comfort_car');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }
        if ($request->end_lat_3 && $request->end_lng_3) {
            $r        = calculate_distance($request->end_lat_2, $request->end_lng_2, $request->end_lat_3, $request->end_lng_3, 'comfort_car');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }

        $response['comfort_car']['distance'] = $distance;
        $response['comfort_car']['duration'] = $duration;

        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "Trip distance ($distance km) exceeds maximum allowed ($maximum_distance_long_trip km).", 400);
        }

        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }

        $peakTimeCost = $isPeak ? round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4) : 0;
        $total_cost   = ceil($total_cost1 + $peakTimeCost);

        $response['comfort_car']['total_cost_before_discount'] = $total_cost;

        if ($student && $student_trips_count < 3) {
            $response['comfort_car']['discount'] = $total_cost * ($student_discount / 100);
            $total_cost                          = $total_cost - ($total_cost * ($student_discount / 100));
        } else {
            $response['comfort_car']['discount'] = 0;
        }

        if ($total_cost < $less_cost_for_trip) $total_cost = $less_cost_for_trip;
        $response['comfort_car']['total_cost'] = $total_cost;

        // ══════════════════════════════════════════
        // SCOOTER
        // ══════════════════════════════════════════
        $kilometer_price_short_trip   = floatval(Setting::where('key', 'kilometer_price_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $kilometer_price_long_trip    = floatval(Setting::where('key', 'kilometer_price_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $kilometer_price_medium_trip  = floatval(Setting::where('key', 'kilometer_price_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_long_trip   = floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_medium_trip = floatval(Setting::where('key', 'maximum_distance_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $maximum_distance_short_trip  = floatval(Setting::where('key', 'maximum_distance_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $increase_rate_peak_time_trip = floatval(Setting::where('key', 'increase_rate_peak_time_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $less_cost_for_trip           = floatval(Setting::where('key', 'less_cost_for_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
        $student_discount             = floatval(Setting::where('key', 'student_discount')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);

        $total_cost1 = 0;
        $r           = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat_1, $request->end_lng_1, 'scooter');
        $distance    = $r['distance_in_km'];
        $duration    = $r['duration_in_M'];

        if ($request->end_lat_2 && $request->end_lng_2) {
            $r        = calculate_distance($request->end_lat_1, $request->end_lng_1, $request->end_lat_2, $request->end_lng_2, 'scooter');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }
        if ($request->end_lat_3 && $request->end_lng_3) {
            $r        = calculate_distance($request->end_lat_2, $request->end_lng_2, $request->end_lat_3, $request->end_lng_3, 'scooter');
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }

        $response['scooter']['distance'] = $distance;
        $scooterDuration = round($duration * 0.8); // أسرع 20%

        $response['scooter']['duration'] = $scooterDuration;
        if ($distance > $maximum_distance_long_trip) {
            return $this->sendError(null, "Trip distance ($distance km) exceeds maximum allowed ($maximum_distance_long_trip km).", 400);
        }

        if ($distance >= $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
        } else {
            $total_cost1 += $kilometer_price_short_trip * $distance;
        }
        if ($distance >= $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
        } elseif ($distance > $maximum_distance_short_trip) {
            $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
        }
        if ($distance == $maximum_distance_long_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
        } elseif ($distance > $maximum_distance_medium_trip) {
            $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
        }

        $air_conditioning_cost = ($Air_conditioning_service_price > 0 && $request->air_conditioned == '1')
            ? round($total_cost1 * ($Air_conditioning_service_price / 100), 4)
            : 0;

        $peakTimeCost = $isPeak ? round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4) : 0;
        $total_cost   = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);

        $response['scooter']['total_cost_before_discount'] = $total_cost;

        if ($student && $student_trips_count < 3) {
            $response['scooter']['discount'] = $total_cost * ($student_discount / 100);
            $total_cost                      = $total_cost - ($total_cost * ($student_discount / 100));
        } else {
            $response['scooter']['discount'] = 0;
        }

        if ($total_cost < $less_cost_for_trip) $total_cost = $less_cost_for_trip;
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
            $query->select('id', 'user_id', 'car_mark_id', 'car_model_id', 'year', 'lat', 'lng', 'color', 'car_plate')->with(['mark:id,en_name,ar_name', 'model:id,en_name,ar_name', 'owner:id,name,country_code,phone,level']);
        }, 'scooter' => function ($query) {
            $query->select('id', 'user_id', 'motorcycle_mark_id', 'motorcycle_model_id', 'year', 'lat', 'lng', 'color', 'scooter_plate')->with(['motorcycleMark:id,en_name,ar_name', 'motorcycleModel:id,en_name,ar_name', 'owner:id,name,country_code,phone,level']);
        }, 'finalDestination' => function ($xx) {
            $xx->select('id', 'trip_id', 'lat', 'lng', 'address') // مكان الأعمدة الصحيح
                ->orderBy('id');
        }])->first();

        if ($trip) {
            $totalDistance = 0;
            $totalDuration = 0;

            // Start from trip starting point
            $prevLat = $trip->start_lat;
            $prevLng = $trip->start_lng;
            $type    = '';
            switch (strtolower($trip->type)) {
                case 'scooter':
                    $type = 'scooter'; // special mode for scooters / motorbikes
                    break;
                case 'comfort_car':
                    $type = 'car';
                    break;
                default:
                    $type = 'car';
            }

            foreach ($trip->finalDestination as $destination) {
                $response = calculate_distance($prevLat, $prevLng, $destination->lat, $destination->lng, $type);

                $totalDistance += $response['distance_in_km'];
                $totalDuration += $response['duration_in_M'];

                // update previous point
                $prevLat = $destination->lat;
                $prevLng = $destination->lng;
            }
            $trip->duration  = $totalDuration;
            $barcode_image   = url(barcodeImage($trip->id));
            $trip->barcode   = $barcode_image;
            if ($trip->status == 'pending' || $trip->status == 'in_progress') {
                if (in_array($trip->type, ['car', 'comfort_car'])) {
                    $driver_                = $trip->car->owner;
                    $trip->car->owner->rate = Trip::whereHas('car', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                    $trip->car->owner->trips_count = Trip::whereHas('car', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->count();
                    $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
                } elseif ($trip->type == 'scooter') {
                    $driver_                    = $trip->scooter->owner;
                    $trip->scooter->owner->rate = Trip::whereHas('scooter', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                    $trip->scooter->owner->trips_count = Trip::whereHas('scooter', function ($query) use ($driver_) {
                        $query->where('user_id', $driver_->id);
                    })->where('status', 'completed')->count();
                    $trip->scooter->image = getFirstMediaUrl($trip->scooter, $trip->scooter->avatarCollection);
                }

            }
            if ($trip->status == 'pending') {
                if (in_array($trip->type, ['car', 'comfort_car'])) {
                    $response = calculate_distance($trip->car->lat, $trip->car->lng, $trip->start_lat, $trip->start_lng, 'car');
                } elseif ($trip->type == 'scooter') {
                    $response = calculate_distance($trip->scooter->lat, $trip->scooter->lng, $trip->start_lat, $trip->start_lng, 'scooter');
                }
                $distance                       = $response['distance_in_km'];
                $duration                       = $response['duration_in_M'];
                $trip->client_location_distance = $distance;
                $trip->client_location_duration = $duration;
            }
            if ($trip->status == 'created') {
                $pendingOffers = $trip->offers()->where('status', 'pending')->get()->map(function ($offer) use ($trip) {
                    if (in_array($trip->type, ['car', 'comfort_car'])) {
                        $response = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng, 'car');
                    } elseif ($trip->type == 'scooter') {
                        $response = calculate_distance($offer->scooter->lat, $offer->scooter->lng, $trip->start_lat, $trip->start_lng, 'scooter');

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
                        $offer_result['user']['trips_count'] = Trip::whereHas('car', function ($query) use ($driver_) {
                            $query->where('user_id', $driver_->id);
                        })->where('status', 'completed')->count();
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
                        $offer_result['user']['rate'] = Trip::whereHas('scooter', function ($query) use ($driver_) {
                            $query->where('user_id', $driver_->id);
                        })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
                        $offer_result['user']['trips_count'] = Trip::whereHas('scooter', function ($query) use ($driver_) {
                            $query->where('user_id', $driver_->id);
                        })->where('status', 'completed')->count();
                        $offer_result['scooter']['id']              = $offer->scooter()->first()->id;
                        $offer_result['scooter']['image']           = getFirstMediaUrl($offer->scooter()->first(), $offer->scooter()->first()->avatarCollection);
                        $offer_result['scooter']['year']            = $offer->scooter()->first()->year;
                        $offer_result['scooter']['scooter_mark_id'] = $offer->scooter()->first()->motorcycle_mark_id;
                        $offer_result['scooter']['scooter_model_id'] = $offer->scooter()->first()->motorcycle_model_id;
                        $offer_result['scooter']['mark']['id']      = $offer->scooter()->first()->mark()->first()->id;
                        $offer_result['scooter']['mark']['name']    = $offer->scooter()->first()->mark()->first()->name;
                        $offer_result['scooter']['model']['id']     = $offer->scooter()->first()->model()->first()->id;
                        $offer_result['scooter']['model']['name']   = $offer->scooter()->first()->model()->first()->name;
                    }
                    $offer_result['created_at'] = $offer->created_at;
                    return $offer_result;

                });
                $trip->offers = $pendingOffers;
                // Clean car plate
if ($trip->car) {
    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
}

// Clean scooter plate
if ($trip->scooter) {
    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
}
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
        $user = auth()->user();

        $completed_trips = Trip::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')

            // مهم لتفادي أي inconsistency
            ->select('trips.*')

            ->with([
                // minimal relations زي باقي endpoints
                'user:id,name,country_code,phone',

                'car:id,code,car_mark_id,car_model_id,user_id,color,year,status',
                'car.mark',
                'car.model',
                'car.owner:id,name,country_code,phone',

                'scooter.motorcycleMark',
                'scooter.motorcycleModel',
                'scooter.owner:id,name,country_code,phone',

                'finalDestination:id,trip_id,lat,lng,address',
            ])

            ->latest()
            ->get()
            ->map(function ($trip) {

                // barcode standardization (same across all APIs)
                $trip->barcode = url(barcodeImage($trip->id));
                // Clean car plate
if ($trip->car) {
    $trip->car->car_plate = str_replace('|', '', $trip->car->car_plate);
}

// Clean scooter plate
if ($trip->scooter) {
    $trip->scooter->scooter_plate = str_replace('|', '', $trip->scooter->scooter_plate);
}

                // driver flag
                $trip->is_driver_arrived = !is_null($trip->driver_arrived);

                // ================= IMAGE HANDLING =================
                if ($trip->car && $trip->car->owner) {
                    $trip->car->owner->image = getFirstMediaUrl(
                        $trip->car->owner,
                        $trip->car->owner->avatarCollection
                    );
                }

                if ($trip->scooter && $trip->scooter->owner) {
                    $trip->scooter->owner->image = getFirstMediaUrl(
                        $trip->scooter->owner,
                        $trip->scooter->owner->avatarCollection
                    );
                }

                return $trip;
            });

        return $this->sendResponse($completed_trips, null, 200);
    }

    public function cancelled_trips()
{
    $user = auth()->user();

    $cancelled_trips = Trip::query()
        ->where('user_id', $user->id)
        ->where('status', 'cancelled')

        // مهم جدًا: نخليها clean
        ->select('trips.*')

        ->with([
            // user minimal (لو محتاجه)
            'user:id,name,country_code,phone',

            // car lightweight زي TripByID
            'car:id,code,car_mark_id,car_model_id,user_id,color,year,status',
            'car.mark',
            'car.model',
            'car.owner:id,name,country_code,phone',

            // scooter lightweight
            'scooter.motorcycleMark',
            'scooter.motorcycleModel',
            'scooter.owner:id,name,country_code,phone',

            // essentials only
            'cancelled_by:id,name,phone',
            'cancelling_reason:id,ar_reason,en_reason',
            'finalDestination:id,trip_id,lat,lng,address',
        ])

        ->latest()
        ->get()
        ->map(function ($trip) {

            // barcode standardization
            $trip->barcode = url(barcodeImage($trip->id));

            // driver flag
            $trip->is_driver_arrived = !is_null($trip->driver_arrived);

            // image handling (safe null check)
            if ($trip->car && $trip->car->owner) {
                $trip->car->owner->image = getFirstMediaUrl(
                    $trip->car->owner,
                    $trip->car->owner->avatarCollection
                );
            }

            if ($trip->scooter && $trip->scooter->owner) {
                $trip->scooter->owner->image = getFirstMediaUrl(
                    $trip->scooter->owner,
                    $trip->scooter->owner->avatarCollection
                );
            }

            // rename consistency with other APIs
            $trip->final_destination = $trip->finalDestination;
            unset($trip->finalDestination);

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
            if ($request->tip) {
                $trip->tip      = floatval($request->tip);
                $client         = $trip->user;
                $client->wallet = $client->wallet - floatval($request->tip);
                $client->save();
                if ($trip->car) {
                    $driver = $trip->car->owner;
                } elseif ($trip->scooter) {
                    $driver = $trip->scooter->owner;
                }
                $driver->wallet = $driver->wallet + floatval($request->tip);
                $driver->save();
            }
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

        return $this->sendResponse(null, 'trip rating saved successfully', 200);
    }

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
            'trip_id' => 'required|exists:trips,id',
        ]);
        if ($validator->fails()) {

            $errors = implode(" / ", $validator->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
        $trip = Trip::findOrFail($request->trip_id);
        if ($trip->user_id == auth()->user()->id) {
            $type = 'client';
        } else {
            $type = 'driver';
        }
        if ($trip->driver_arrived != null && $trip->status == 'pending') {
            $status = 'driver_arrived';
        } elseif ($trip->status == 'pending') {
            $status = 'before';
        } elseif ($trip->status == 'in_progress') {
            $status = 'after';
        }
        $reasons = TripCancellingReason::whereIn('type', [$type, 'all'])->where('status', $status)->get();
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
            'record' => 'nullable|file|mimetypes:audio/mpeg,audio/wav,audio/mp4,audio/x-m4a,audio/aac,audio/m4a,video/mp4|max:20480',
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

    public function update_trip_price(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'price'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $errors = implode(' / ', $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }

        $trip = Trip::where('id', $request->trip_id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $trip) {

            return $this->sendError(null, 'Trip not found or not authorized.', 403);

        }

        if (! in_array($trip->status, ['created', 'scheduled'])) {

            return $this->sendError(null, 'Trip cannot be updated. Invalid status.', 422);

        }
        try {
            if ($trip->total_price != $request->price) {
                DB::table('drivers_trips')->where('trip_id', $trip->id)->delete();
                $trip->total_price = $request->price;
                $trip->save();

            }

            return $this->sendResponse(null, 'Trip price updated successfully.', 200);

        } catch (\Exception $e) {

            return $this->sendError(null, 'Failed to update trip price.', 500);

        }
    }

    public function get_rate_trip_setting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'stars'   => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            $errors = implode(' / ', $validator->errors()->all());
            return $this->sendError(null, $errors, 400);
        }
        $trip = Trip::findOrFail($request->trip_id);
        if (auth()->user()->id == $trip->user_id) {
            $type = 'client';
        } else {
            $type = 'driver';
        }

        $labels = RateTripSetting::where('star_count', $request->stars)->where('category', $type)->get();
        return $this->sendResponse($labels, null, 200);

    }
    public function calculate_trip_price(Request $request)
{
    $validator = Validator::make($request->all(), [
        'start_lat'      => 'required|numeric|between:-90,90',
        'start_lng'      => 'required|numeric|between:-180,180',
        'end_lat_1'      => 'required|numeric|between:-90,90',
        'end_lng_1'      => 'required|numeric|between:-180,180',
        'end_lat_2'      => 'nullable|numeric|between:-90,90',
        'end_lng_2'      => 'nullable|numeric|between:-180,180',
        'end_lat_3'      => 'nullable|numeric|between:-90,90',
        'end_lng_3'      => 'nullable|numeric|between:-180,180',
        'air_conditioned'=> 'nullable|in:0,1',
        'bags'           => 'nullable|in:0,1',
        'animals'        => 'nullable|in:0,1',
        'type'           => 'required|in:car,comfort_car,scooter',
        'payment_method' => 'required|in:cash,wallet',
        'start_date'     => 'nullable|date|date_format:Y-m-d',
        'start_time'     => 'nullable|date_format:H:i',
        'address1'       => 'nullable|string',
        'address2'       => 'nullable|string',
        'address3'       => 'nullable|string',
        'address4'       => 'nullable|string',
        'discount'       => 'nullable|numeric',
        'distance'       => 'nullable|numeric',
        'total_cost'     => 'nullable|numeric',
        'duration'       => 'nullable|numeric',
        'luggage'        => 'nullable',
    ]);

    if ($validator->fails()) {
        $errors = implode(" / ", $validator->errors()->all());
        return $this->sendError(null, $errors, 400);
    }

    // ── 1. Resolve date/time ──
    $start_date = $request->start_date ?? now()->toDateString();
    $start_time = $request->start_time ?? now()->format('H:i');
    $day        = date('l', strtotime($start_date));

    // ── 2. Peak time check ──
    $peakJson  = Setting::where('key', 'peak_times')->where('category', 'Trips')->where('type', 'options')->first()->value;
    $peakTimes = json_decode($peakJson, true);
    $isPeak    = false;

    if (isset($peakTimes[$day])) {
        foreach ($peakTimes[$day] as $period) {
            if ($start_time >= $period['from'] && $start_time <= $period['to']) {
                $isPeak = true;
                break;
            }
        }
    }

    // ── 3. Student check ──
    $student = \App\Models\Student::where('user_id', auth()->id())
        ->where('status', 'confirmed')
        ->where('student_discount_service', '1')
        ->first();

    $student_trips_today = Trip::where('user_id', auth()->id())
        ->where('student_trip', '1')
        ->where('status', 'completed')
        ->where('start_date', $start_date)
        ->count();

    $is_student = $student && $student_trips_today < 3;

    // ── 4. Build base response fields ──
    $response                    = [];
    $response['start_date']      = $start_date;
    $response['start_time']      = $start_time;
    $response['start_lat']       = (float) $request->start_lat;
    $response['start_lng']       = (float) $request->start_lng;
    $response['air_conditioned'] = $request->air_conditioned == '1';
    $response['end_lat_1']       = (float) $request->end_lat_1;
    $response['end_lng_1']       = (float) $request->end_lng_1;
    $response['end_lat_2']       = $request->end_lat_2 ? (float) $request->end_lat_2 : null;
    $response['end_lng_2']       = $request->end_lng_2 ? (float) $request->end_lng_2 : null;
    $response['end_lat_3']       = $request->end_lat_3 ? (float) $request->end_lat_3 : null;
    $response['end_lng_3']       = $request->end_lng_3 ? (float) $request->end_lng_3 : null;

    // ── 5. Helper closure to calculate one vehicle type ──
    $calcType = function (string $type) use ($request, $isPeak, $is_student) {

        $categoryMap = [
            'car'         => 'Car Trips',
            'comfort_car' => 'Comfort Trips',
            'scooter'     => 'Scooter Trips',
        ];
        $keyPrefix = $type === 'comfort_car' ? 'comfort' : $type;
        $category  = $categoryMap[$type];

        // Settings
        $kilometer_price_short  = floatval(Setting::where('key', "kilometer_price_{$keyPrefix}_short_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $kilometer_price_medium = floatval(Setting::where('key', "kilometer_price_{$keyPrefix}_medium_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $kilometer_price_long   = floatval(Setting::where('key', "kilometer_price_{$keyPrefix}_long_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $max_short              = floatval(Setting::where('key', "maximum_distance_{$keyPrefix}_short_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $max_medium             = floatval(Setting::where('key', "maximum_distance_{$keyPrefix}_medium_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $max_long               = floatval(Setting::where('key', "maximum_distance_{$keyPrefix}_long_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $less_cost              = floatval(Setting::where('key', "less_cost_for_{$keyPrefix}_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $peak_rate              = floatval(Setting::where('key', "increase_rate_peak_time_{$keyPrefix}_trip")->where('category', $category)->where('type', 'number')->first()->value);
        $student_discount_rate  = floatval(Setting::where('key', 'student_discount')->where('category', $category)->where('type', 'number')->first()->value);

        $ac_price = ($type === 'car')
            ? floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', $category)->where('type', 'number')->first()->value)
            : 0;

        // Distance & duration
        $r        = calculate_distance($request->start_lat, $request->start_lng, $request->end_lat_1, $request->end_lng_1, $type);
        $distance = $r['distance_in_km'];
        $duration = $r['duration_in_M'];

        if ($request->end_lat_2 && $request->end_lng_2) {
            $r        = calculate_distance($request->end_lat_1, $request->end_lng_1, $request->end_lat_2, $request->end_lng_2, $type);
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }

        if ($request->end_lat_3 && $request->end_lng_3) {
            $r        = calculate_distance($request->end_lat_2, $request->end_lng_2, $request->end_lat_3, $request->end_lng_3, $type);
            $distance += $r['distance_in_km'];
            $duration += $r['duration_in_M'];
        }

        // Distance limit check
        if ($distance > $max_long) {
            return ['error' => "Trip distance ({$distance} km) exceeds maximum allowed ({$max_long} km)."];
        }

        // Tiered base price
        $base_price = 0;

        if ($distance >= $max_short) {
            $base_price += $kilometer_price_short * $max_short;
        } else {
            $base_price += $kilometer_price_short * $distance;
        }

        if ($distance >= $max_medium) {
            $base_price += $kilometer_price_medium * ($max_medium - $max_short);
        } elseif ($distance > $max_short) {
            $base_price += $kilometer_price_medium * ($distance - $max_short);
        }

        if ($distance == $max_long) {
            $base_price += $kilometer_price_long * ($max_long - $max_medium);
        } elseif ($distance > $max_medium) {
            $base_price += $kilometer_price_long * ($distance - $max_medium);
        }

        // AC surcharge (car only)
        $ac_cost = ($ac_price > 0 && $request->air_conditioned == '1')
            ? round($base_price * ($ac_price / 100), 4)
            : 0;

        // Peak surcharge
        $peak_cost = $isPeak ? round($base_price * ($peak_rate / 100), 4) : 0;

        // Total before discount (apply minimum)
        $total_before_discount = ceil($base_price + $ac_cost + $peak_cost);
        if ($total_before_discount < $less_cost) {
            $total_before_discount = $less_cost;
        }

        // Student discount
        $discount = $is_student
            ? round($total_before_discount * ($student_discount_rate / 100), 2)
            : 0;

        $total_cost = $total_before_discount - $discount;

        // Scooter is 20% faster
        $final_duration = $type === 'scooter' ? round($duration * 0.8) : intval($duration);

        return [
            'distance'                   => round($distance, 2),
            'duration'                   => $final_duration,
            'total_cost_before_discount' => floatval($total_before_discount),
            'discount'                   => floatval($discount),
            'total_cost'                 => floatval($total_cost),
        ];
    };

    // ── 6. Calculate all three types ──
    foreach (['car', 'comfort_car', 'scooter'] as $type) {
        $result = $calcType($type);

        if (isset($result['error'])) {
            return $this->sendError(null, $result['error'], 422);
        }

        $response[$type] = $result;
    }

    return $this->sendResponse($response, 'Price calculated successfully', 200);
}
}

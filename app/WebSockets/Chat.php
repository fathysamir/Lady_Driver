<?php
namespace App\WebSockets;

use App\Models\Car;
use App\Models\LiveLocation;
use App\Models\Offer;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Trip;
use App\Models\TripChat;
use App\Models\TripDestination;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $loop;
    protected $firebaseService;
    private $clientUserIdMap;

    public function __construct($loop)
    {
        $this->clients         = new \SplObjectStorage();
        $this->loop            = $loop;
        $this->firebaseService = new FirebaseService();
        $this->clientUserIdMap = [];
    }

    // private function create_trip(ConnectionInterface $from, $AuthUserID, $tripRequest)
    // {

    //     $data     = json_decode($tripRequest, true);
    //     $response = calculate_distance($data['start_lat'], $data['start_lng'], $data['end_lat'], $data['end_lng']);
    //     $distance = $response['distance_in_km'];
    //     $duration = $response['duration_in_M'];

    //     $kilometer_price                = floatval(Setting::where('key', 'kilometer_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
    //     $Air_conditioning_service_price = floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
    //     $app_ratio                      = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
    //     if ($data['air_conditioned'] == 1) {
    //         $driver_rate = round(($distance * $kilometer_price) + $Air_conditioning_service_price, 2);
    //     } else {
    //         $driver_rate = round($distance * $kilometer_price, 2);
    //     }
    //     $app_rate    = round(($distance * $kilometer_price * $app_ratio) / 100, 2);
    //     $total_price = $driver_rate + $app_rate;
    //     $lastTrip    = Trip::orderBy('id', 'desc')->first();

    //     if ($lastTrip) {
    //         $lastCode = $lastTrip->code;
    //         $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
    //     } else {
    //         $code = 'TRP-000001';
    //     }
    //     $trip = Trip::create(['user_id' => $AuthUserID,
    //         'code'                          => $code,
    //         'start_lat'                     => floatval($data['start_lat']),
    //         'start_lng'                     => floatval($data['start_lng']),
    //         'end_lat'                       => floatval($data['end_lat']),
    //         'end_lng'                       => floatval($data['end_lng']),
    //         'address1'                      => $data['address1'],
    //         'address2'                      => $data['address2'],
    //         'total_price'                   => $total_price,
    //         'app_rate'                      => $app_rate,
    //         'driver_rate'                   => $driver_rate,
    //         'distance'                      => $distance,
    //         'type'                          => $data['type'],
    //     ]);
    //     $u                      = User::findOrFail($AuthUserID);
    //     $user_image             = 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection);
    //     $newTrip['id']          = $trip->id;
    //     $newTrip['code']        = $code;
    //     $newTrip['user_id']     = intval($AuthUserID);
    //     $newTrip["car_id"]      = null;
    //     $newTrip["start_date"]  = null;
    //     $newTrip["end_date"]    = null;
    //     $newTrip["start_time"]  = null;
    //     $newTrip["end_time"]    = null;
    //     $newTrip['start_lat']   = floatval($data['start_lat']);
    //     $newTrip['start_lng']   = floatval($data['start_lng']);
    //     $newTrip['end_lat']     = floatval($data['end_lat']);
    //     $newTrip['end_lng']     = floatval($data['end_lng']);
    //     $newTrip['address1']    = $data['address1'];
    //     $newTrip['address2']    = $data['address2'];
    //     $newTrip['total_price'] = $total_price;
    //     $newTrip['app_rate']    = $app_rate;
    //     $newTrip['driver_rate'] = $driver_rate;
    //     $newTrip['distance']    = $distance;
    //     $newTrip['type']        = $data['type'];
    //     if ($data['air_conditioned'] == '1') {
    //         $trip->air_conditioned      = '1';
    //         $newTrip['air_conditioned'] = '1';
    //     } else {
    //         $trip->air_conditioned      = '0';
    //         $newTrip['air_conditioned'] = '0';
    //     }

    //     if ($data['animal'] == '1') {
    //         $trip->animals      = '1';
    //         $newTrip['animals'] = '0';
    //     } else {
    //         $trip->animals      = '0';
    //         $newTrip['animals'] = '0';
    //     }
    //     $trip->save();
    //     $from->send(json_encode(['type' => 'created_trip', 'message' => 'Trip Created Successfully']));
    //     $date_time = date('Y-m-d h:i:s a');
    //     echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $AuthUserID);

    //     $newTrip["client_stare_rate"]         = 0;
    //     $newTrip["client_comment"]            = null;
    //     $newTrip["status"]                    = "created";
    //     $newTrip["cancelled_by_id"]           = null;
    //     $newTrip["trip_cancelling_reason_id"] = null;
    //     $newTrip["driver_stare_rate"]         = 0;
    //     $newTrip["driver_comment"]            = null;
    //     $newTrip["payment_status"]            = "unpaid";
    //     $newTrip["current_offer"]             = null;
    //     $newTrip['duration']                  = $duration;

    //     $radius        = 6371;
    //     $decimalPlaces = 2;

    //     $eligibleCars = Car::where('status', 'confirmed')
    //         ->whereHas('owner', function ($query) {
    //             $query->where('is_online', '1')
    //                 ->where('status', 'confirmed');
    //         })
    //         ->where(function ($query) use ($trip) {
    //             if ($trip->air_conditioned == '1') {
    //                 $query->where('air_conditioned', '1');
    //             }
    //             if ($trip->animals == '1') {
    //                 $query->where('animals', '1');
    //             }
    //             if ($trip->user->gendor == 'Male') {
    //                 $query->where('passenger_type', 'male_female');
    //             }
    //         })
    //         ->select('*')
    //         ->selectRaw(
    //             "
    //                 ROUND(
    //                     ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
    //                 ) AS distance",
    //             [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
    //         )
    //         ->having('distance', '<=', 3)
    //         ->get()
    //         ->filter(function ($car) use ($trip) {
    //             $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
    //             return $response['distance_in_km'] <= 3;
    //         });

    //     $eligibleDriverIds = [];

    //     foreach ($eligibleCars as $car) {
    //         $eligibleDriverIds[] = $car->user_id;
    //         if ($car->owner->device_token) {
    //             // $this->firebaseService->sendNotification($car->owner->device_token,'Lady Driver - New Trip',"There is a new trip created in your current area",["screen"=>"New Trip","ID"=>$trip->id]);
    //             // $data=[
    //             //     "title"=>"Lady Driver - New Trip",
    //             //     "message"=>"There is a new trip created in your current area",
    //             //     "screen"=>"New Trip",
    //             //     "ID"=>$trip->id
    //             // ];
    //             // Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
    //         }
    //     }
    //     if (count($eligibleDriverIds) > 0) {
    //         foreach ($eligibleDriverIds as $eligibleDriverId) {
    //             $client = $this->getClientByUserId($eligibleDriverId);
    //             if ($client) {
    //                 $car2                                = Car::where('user_id', $eligibleDriverId)->first();
    //                 $response2                           = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
    //                 $distance2                           = $response2['distance_in_km'];
    //                 $duration2                           = $response2['duration_in_M'];
    //                 $newTrip['client_location_distance'] = $distance2;
    //                 $newTrip['client_location_duration'] = $duration2;
    //                 $newTrip['created_at']               = $trip->created_at;
    //                 $newTrip['updated_at']               = $trip->updated_at;
    //                 $newTrip['user']['id']               = intval($AuthUserID);
    //                 $newTrip['user']['name']             = $u->name;
    //                 $newTrip['user']['image']            = $user_image;
    //                 $newTrip['user']['rate']             = Trip::where('user_id', $AuthUserID)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate') ?? 0.00;

    //                 $data2['type'] = 'new_trip';
    //                 $data2['data'] = $newTrip;
    //                 $message       = json_encode($data2, JSON_UNESCAPED_UNICODE);
    //                 $client->send($message);
    //                 $date_time = date('Y-m-d h:i:s a');
    //                 echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
    //             }
    //         }
    //     }

    // }

    private function cancel_trip(ConnectionInterface $from, $AuthUserID, $cancelTripRequest)
    {
        $data                            = json_decode($cancelTripRequest, true);
        $trip                            = Trip::findOrFail($data['trip_id']);
        $trip->status                    = 'cancelled';
        $trip->cancelled_by_id           = $AuthUserID;
        $trip->trip_cancelling_reason_id = $data['reason_id'];
        $trip->save();
        $canceled_trip['trip_id'] = $trip->id;
        $data2                    = [
            'type'    => 'canceled_trip',
            'data'    => $canceled_trip,
            'message' => 'Trip canceled successfully',
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $client  = $this->getClientByUserId($trip->user_id);
        if ($client) {
            $client->send($message);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of canceled trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);
        }
        $driver = $this->getClientByUserId($trip->car->user_id);
        if ($driver) {
            $driver->send($message);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of canceled trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->car->user_id);
        }
    }

    private function start_end_trip(ConnectionInterface $from, $AuthUserID, $startTripRequest)
    {
        $data = json_decode($startTripRequest, true);
        $trip = Trip::find($data['trip_id']);
        if ($trip->status == 'pending') {
            $trip->status     = 'in_progress';
            $trip->start_date = date('Y-m-d');
            $trip->start_time = date('H:i:s');
            $trip->save();
            $type    = 'started_trip';
            $message = 'trip started now';
        } elseif ($trip->status == 'in_progress') {
            $trip->status   = 'completed';
            $trip->end_date = date('Y-m-d');
            $trip->end_time = date('H:i:s');
            $trip->save();
            $type    = 'ended_trip';
            $message = 'trip ended now';
        }
        $trip             = Trip::find($data['trip_id']);
        $x['trip_id']     = $trip->id;
        $x['trip_status'] = $trip->status;
        $data1            = [
            'type'    => $type,
            'data'    => $x,
            'message' => $message,
        ];
        $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
        $from->send($res);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ] Message of ' . $message . ' "%s" sent to user %d' . "\n", $date_time, $res, $AuthUserID);

        $client = $this->getClientByUserId($trip->user_id);
        if ($client) {
            $client->send($res);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of ' . $message . ' "%s" sent to user %d' . "\n", $date_time, $res, $trip->user_id);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    public function onOpen(ConnectionInterface $conn)
    {

        // Store the new connection to send messages to later
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        if (isset($queryParams['live_location_token']) && ! empty($queryParams['live_location_token'])) {
            $token = $queryParams['live_location_token'];
            $live  = LiveLocation::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();
            if (! $live) {
                echo "Invalid Live Location. Connection refused.\n";
                $conn->close();
                return;
            } else {
                $this->clients->attach($conn, "live_{$live->id}");
                $this->clientUserIdMap["live_{$live->id}"] = $conn;

                $date_time = date('Y-m-d h:i:s a');
                $this->periodicPing($conn);

                echo "[ {$date_time} ], Live Location Connection! Live ID: {$live->id}, Conn ID: ({$conn->resourceId})\n";
                $conn->send(json_encode([
                    'type'    => 'live_connected',
                    'message' => 'Live location connected successfully',
                ]));

            }

        } else {

            $userId = $queryParams['user_id'] ?? null;

            $userToken = $queryParams['token'] ?? null;
            $user      = User::where('id', $userId)->first();

            if (! $user) {
                echo "Invalid user. Connection refused.\n";
                $conn->close();
                return;
            } else {

                $tokenParts = explode('|', $userToken);
                $tokenId    = $tokenParts[0];
                $tokenValue = $tokenParts[1];

                // // Find the token by ID and user
                $token = $user->tokens()->where('id', $tokenId)->first();
                // //dd($userToken,$tokenId,$tokenValue,$token->token);

                // dd(Crypt::encryptString($tokenValue),$token->token);
                if ($token && hash('sha256', $tokenValue) === $token->token) {
                    // Token matches
                    $this->clients->attach($conn, $userId);
                    $this->clientUserIdMap[$userId] = $conn;
                    $date_time                      = date('Y-m-d h:i:s a');
                    //$conn->send(json_encode(['type' => 'ping']));
                    $this->periodicPing($conn);
                    //echo "New connection! ({$conn->resourceId})\n";
                    echo "[ {$date_time} ],New connection! User ID: {$userId}, Connection ID: ({$conn->resourceId})\n";

                } else {
                    // Token does not match
                    echo "Token does not match.";
                    $conn->close();
                    return;
                }
            }
        }
    }

    private function periodicPing(ConnectionInterface $conn)
    {
        $timer = 30; // Send a ping every 60 seconds

        $this->loop->addPeriodicTimer($timer, function () use ($conn) {
            try {
                                                              // Try sending a ping message, if connection is closed, it'll throw an error
                $conn->send(json_encode(['type' => 'ping'])); // Send a ping
                $date_time = date('Y-m-d h:i:s a');
                echo "[ {$date_time} ], Ping sent to Connection {$conn->resourceId}\n";
            } catch (\Exception $e) {
                // If there's an error sending the ping, the connection is probably closed
                $date_time = date('Y-m-d h:i:s a');
                echo "[ {$date_time} ], Connection {$conn->resourceId} has closed during ping\n";
            }
        });
    }

    private function create_trip_and_find_drivers(ConnectionInterface $from, $AuthUserID, $tripRequest)
    {
        $data = json_decode($tripRequest, true);
        $type = $data['type'];
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
                $newTrip["car_id"]              = null;
                $student_discount               = floatval(Setting::where('key', 'student_discount')->where('category', 'Car Trips')->where('type', 'number')->first()->value);

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
                $newTrip["car_id"]              = null;
                $student_discount               = floatval(Setting::where('key', 'student_discount')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);

                break;

            case 'scooter':
                $Air_conditioning_service_price = 0;
                $kilometer_price_short_trip     = floatval(Setting::where('key', 'kilometer_price_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $kilometer_price_long_trip      = floatval(Setting::where('key', 'kilometer_price_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $kilometer_price_medium_trip    = floatval(Setting::where('key', 'kilometer_price_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_long_trip     = floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_medium_trip   = floatval(Setting::where('key', 'maximum_distance_scooter_medium_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $maximum_distance_short_trip    = floatval(Setting::where('key', 'maximum_distance_scooter_short_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $increase_rate_peak_time_trip   = floatval(Setting::where('key', 'increase_rate_peak_time_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $less_cost_for_trip             = floatval(Setting::where('key', 'less_cost_for_scooter_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
                $newTrip["scooter_id"]          = null;
                $student_discount               = floatval(Setting::where('key', 'student_discount')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);

                break;

            default:
                $Air_conditioning_service_price = 0;
                $kilometer_price_short_trip     = 0;
                $kilometer_price_long_trip      = 0;
                $kilometer_price_medium_trip    = 0;
                $maximum_distance_long_trip     = 0;
                $maximum_distance_medium_trip   = 0;
                $maximum_distance_short_trip    = 0;
                $increase_rate_peak_time_trip   = 0;
                $less_cost_for_trip             = 0;
                $student_discount               = 0;
                break;
        }

        $peakJson  = Setting::where('key', 'peak_times')->where('category', 'Trips')->where('type', 'options')->first()->value;
        $peakTimes = json_decode($peakJson, true);

        $response_x = calculate_distance($data['start_lat'], $data['start_lng'], $data['end_lat_1'], $data['end_lng_1']);
        $distance   = $response_x['distance_in_km'];
        $duration   = $response_x['duration_in_M'];
        if ($data['end_lat_2'] != null && $data['end_lng_2'] != null) {
            $response_x = calculate_distance($data['end_lat_1'], $data['end_lng_1'], $data['end_lat_2'], $data['end_lng_2']);
            $distance   = $distance + $response_x['distance_in_km'];
            $duration   = $duration + $response_x['duration_in_M'];
        }
        if ($data['end_lat_3'] != null && $data['end_lng_3'] != null) {
            $response_x = calculate_distance($data['end_lat_2'], $data['end_lng_2'], $data['end_lat_3'], $data['end_lng_3']);
            $distance   = $distance + $response_x['distance_in_km'];
            $duration   = $duration + $response_x['duration_in_M'];
        }

        if ($distance > $maximum_distance_long_trip) {
            $from->send(json_encode(['type' => 'error', 'message' => "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km)."]));
        } else {
            $total_cost1 = 0;

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

            if ($Air_conditioning_service_price > 0 && $data['air_conditioned'] == '1') {
                $air_conditioning_cost = round($total_cost1 * ($Air_conditioning_service_price / 100), 4);
            } else {
                $air_conditioning_cost = 0;
            }

            if ($data['start_date'] == null || $data['start_time'] == null) {
                $start_date = now()->toDateString(); // e.g., '2025-07-05'
                $start_time = now()->format('H:i');
                $scheduled  = '0';
                $TripStatus = 'created';
            } elseif ($data['start_date'] != null && $data['start_time'] != null) {
                $start_date = date('Y-m-d', strtotime($data['start_date']));
                $start_time = date('H:i', strtotime($data['start_time']));
                $scheduled  = '1';
                $TripStatus = 'scheduled';
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
            if ($isPeak) {
                $peakTimeCost = round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4);
            } else {
                $peakTimeCost = 0;
            }
            $total_cost = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);
            $student    = Student::where('user_id', $AuthUserID)->where('status', 'confirmed')->where('student_discount_service', '1')->first();
            if ($student) {
                $student_trips_count = Trip::where('user_id', $AuthUserID)->where('student_trip', '1')->where('status', 'completed')->where('start_date', $start_date)->count();
                if ($student_trips_count < 3) {

                    $discount     = $total_cost * ($student_discount / 100);
                    $total_cost   = $total_cost - $discount;
                    $student_trip = '1';

                } else {
                    $discount     = 0;
                    $student_trip = '0';
                }
            } else {
                $discount     = 0;
                $student_trip = '0';
            }
            if ($total_cost < $less_cost_for_trip) {
                $total_cost = $less_cost_for_trip;
            }
            $lastTrip = Trip::orderBy('id', 'desc')->first();

            if ($lastTrip) {
                $lastCode = $lastTrip->code;
                $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $code = 'TRP-000001';
            }
            do {
                $barcode = Str::uuid();
            } while (Trip::where('barcode', $barcode)->exists());
            $trip = Trip::create(['user_id' => $AuthUserID,
                'code'                          => $code,
                'barcode'                       => $barcode,
                'start_lat'                     => floatval($data['start_lat']),
                'start_lng'                     => floatval($data['start_lng']),
                'address1'                      => $data['address1'],
                'total_price'                   => $total_cost,
                'distance'                      => $distance,
                'type'                          => $data['type'],
                'start_date'                    => $start_date,
                'start_time'                    => $start_time,
                'scheduled'                     => $scheduled,
                'status'                        => $TripStatus,
                'payment_method'                => $data['payment_method'],
                'remaining_amount'              => $total_cost,
                'discount'                      => $discount,
                'student_trip'                  => $student_trip,
            ]);

            $u                           = User::findOrFail($AuthUserID);
            $user_image                  = 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection);
            $newTrip['id']               = $trip->id;
            $newTrip['code']             = $code;
            $newTrip['barcode']          = barcodeImage($trip->id);
            $newTrip['user_id']          = intval($AuthUserID);
            $newTrip["start_date"]       = $start_date;
            $newTrip["end_date"]         = null;
            $newTrip["start_time"]       = $start_time;
            $newTrip["end_time"]         = null;
            $newTrip['start_lat']        = floatval($data['start_lat']);
            $newTrip['start_lng']        = floatval($data['start_lng']);
            $newTrip['address1']         = $data['address1'];
            $newTrip['total_price']      = $total_cost;
            $newTrip['app_rate']         = 0.00;
            $newTrip['driver_rate']      = 0.00;
            $newTrip['discount']         = $discount;
            $newTrip['paid_amount']      = 0.00;
            $newTrip['remaining_amount'] = $total_cost;
            $newTrip['distance']         = $distance;
            $newTrip['duration']         = $duration;
            $newTrip['scheduled']        = $scheduled;
            $newTrip['type']             = $data['type'];
            $newTrip["status"]           = $TripStatus;

            if ($data['air_conditioned'] == '1') {
                $trip->air_conditioned      = '1';
                $newTrip['air_conditioned'] = '1';
            } else {
                $trip->air_conditioned      = '0';
                $newTrip['air_conditioned'] = '0';
            }

            if ($data['animals'] == '1') {
                $trip->animals      = '1';
                $newTrip['animals'] = '1';
            } else {
                $trip->animals      = '0';
                $newTrip['animals'] = '0';
            }
            if ($data['bags'] == '1') {
                $trip->bags      = '1';
                $newTrip['bags'] = '1';
            } else {
                $trip->bags      = '0';
                $newTrip['bags'] = '0';
            }
            $trip->save();

            TripDestination::create(['trip_id' => $trip->id, 'lat' => $data['end_lat_1'], 'lng' => $data['end_lng_1'], 'address' => $data['address2']]);
            $newTrip['end_lat_1'] = $data['end_lat_1'];
            $newTrip['end_lng_1'] = $data['end_lng_1'];
            $newTrip['address2']  = $data['address2'];
            if ($data['end_lat_2'] != null && $data['end_lng_2'] != null) {
                TripDestination::create(['trip_id' => $trip->id, 'lat' => $data['end_lat_2'], 'lng' => $data['end_lng_2'], 'address' => $data['address3']]);
                $newTrip['end_lat_2'] = $data['end_lat_2'];
                $newTrip['end_lng_2'] = $data['end_lng_2'];
                $newTrip['address3']  = $data['address3'];
            }
            if ($data['end_lat_3'] != null && $data['end_lng_3'] != null) {
                TripDestination::create(['trip_id' => $trip->id, 'lat' => $data['end_lat_3'], 'lng' => $data['end_lng_3'], 'address' => $data['address4']]);
                $newTrip['end_lat_3'] = $data['end_lat_3'];
                $newTrip['end_lng_3'] = $data['end_lng_3'];
                $newTrip['address4']  = $data['address4'];
            }

            $from->send(json_encode(['type' => 'created_trip', 'data' => $newTrip, 'message' => 'Trip Created Successfully']));
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $AuthUserID);
            $newTrip["client_stare_rate"]         = 0;
            $newTrip["client_comment"]            = null;
            $newTrip["cancelled_by_id"]           = null;
            $newTrip["trip_cancelling_reason_id"] = null;
            $newTrip["driver_stare_rate"]         = 0;
            $newTrip["student_trip"]              = $student_trip;
            $newTrip["driver_comment"]            = null;
            $newTrip["driver_arrived"]            = null;
            $newTrip["payment_status"]            = "unpaid";
            $newTrip["current_offer"]             = null;
            $newTrip['created_at']                = $trip->created_at;
            $newTrip['updated_at']                = $trip->updated_at;
            $newTrip['user']['id']                = intval($AuthUserID);
            $newTrip['user']['name']              = $u->name;
            $newTrip['user']['image']             = $user_image;
            $newTrip['user']['rate']              = Trip::where('user_id', $AuthUserID)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate') ?? 0.00;
            switch ($type) {
                case 'car':
                    $decimalPlaces = 2;
                    $eligibleCars  = Car::where('status', 'confirmed')->where('is_comfort', '0')

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
                        ->selectRaw(
                            "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                            [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                        )
                        ->having('distance', '<=', 3)
                        ->get()
                        ->filter(function ($car) use ($trip) {
                            $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });

                    $eligibleDriverIds = [];

                    foreach ($eligibleCars as $car) {
                        $eligibleDriverIds[] = $car->user_id;
                        if ($car->owner->device_token) {
                            // $this->firebaseService->sendNotification($car->owner->device_token,'Lady Driver - New Trip',"There is a new trip created in your current area",["screen"=>"New Trip","ID"=>$trip->id]);
                            // $data=[
                            //     "title"=>"Lady Driver - New Trip",
                            //     "message"=>"There is a new trip created in your current area",
                            //     "screen"=>"New Trip",
                            //     "ID"=>$trip->id
                            // ];
                            // Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
                        }
                    }

                    if (count($eligibleDriverIds) > 0) {
                        foreach ($eligibleDriverIds as $eligibleDriverId) {
                            $client = $this->getClientByUserId($eligibleDriverId);
                            if ($client) {
                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = round(($total_cost * $app_ratio) / 100, 2);
                                $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_UNICODE);
                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'comfort_car':
                    $decimalPlaces = 2;
                    $eligibleCars  = Car::where('status', 'confirmed')->where('is_comfort', '1')

                        ->whereHas('owner', function ($query) {
                            $query->where('is_online', '1')
                                ->where('status', 'confirmed');
                        })
                        ->where(function ($query) use ($trip) {

                            if ($trip->animals == '1') {
                                $query->where('animals', '1');
                            }
                            if ($trip->user->gendor == 'Male') {
                                $query->where('passenger_type', 'male_female');
                            }
                        })
                        ->select('*')
                        ->selectRaw(
                            "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                            [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                        )
                        ->having('distance', '<=', 3)
                        ->get()
                        ->filter(function ($car) use ($trip) {
                            $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });

                    $eligibleDriverIds = [];

                    foreach ($eligibleCars as $car) {
                        $eligibleDriverIds[] = $car->user_id;
                        if ($car->owner->device_token) {
                            // $this->firebaseService->sendNotification($car->owner->device_token,'Lady Driver - New Trip',"There is a new trip created in your current area",["screen"=>"New Trip","ID"=>$trip->id]);
                            // $data=[
                            //     "title"=>"Lady Driver - New Trip",
                            //     "message"=>"There is a new trip created in your current area",
                            //     "screen"=>"New Trip",
                            //     "ID"=>$trip->id
                            // ];
                            // Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
                        }
                    }
                    if (count($eligibleDriverIds) > 0) {
                        foreach ($eligibleDriverIds as $eligibleDriverId) {
                            $client = $this->getClientByUserId($eligibleDriverId);
                            if ($client) {
                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = round(($total_cost * $app_ratio) / 100, 2);
                                $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_UNICODE);
                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'scooter':
                    $decimalPlaces    = 2;
                    $eligibleScooters = Scooter::where('status', 'confirmed')

                        ->whereHas('owner', function ($query) {
                            $query->where('is_online', '1')
                                ->where('status', 'confirmed');
                        })

                        ->select('*')
                        ->selectRaw(
                            "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                            [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                        )
                        ->having('distance', '<=', 3)
                        ->get()
                        ->filter(function ($scooter) use ($trip) {
                            $response = calculate_distance($scooter->lat, $scooter->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });

                    $eligibleDriverIds = [];

                    foreach ($eligibleScooters as $scooter) {
                        $eligibleDriverIds[] = $scooter->user_id;
                        if ($scooter->owner->device_token) {
                            // $this->firebaseService->sendNotification($car->owner->device_token,'Lady Driver - New Trip',"There is a new trip created in your current area",["screen"=>"New Trip","ID"=>$trip->id]);
                            // $data=[
                            //     "title"=>"Lady Driver - New Trip",
                            //     "message"=>"There is a new trip created in your current area",
                            //     "screen"=>"New Trip",
                            //     "ID"=>$trip->id
                            // ];
                            // Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
                        }
                    }
                    if (count($eligibleDriverIds) > 0) {
                        foreach ($eligibleDriverIds as $eligibleDriverId) {
                            $client = $this->getClientByUserId($eligibleDriverId);
                            if ($client) {
                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = round(($total_cost * $app_ratio) / 100, 2);
                                $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_UNICODE);
                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                default:

                    break;
            }
        }

    }
    private function expire_trip(ConnectionInterface $from, $AuthUserID, $expireTripRequest)
    {
        $data         = json_decode($expireTripRequest, true);
        $trip         = Trip::findOrFail($data['trip_id']);
        $trip->status = 'expired';
        $trip->save();
        $expired_trip['trip_id'] = $trip->id;
        $data2                   = [
            'type'    => 'expired_trip',
            'data'    => $expired_trip,
            'message' => 'Trip expired successfully',
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $from->send($message);
        $date_time = date('Y-m-d h:i:s a');

        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $AuthUserID);
        switch ($trip->type) {
            case 'car':
                $decimalPlaces = 2;
                $userIds       = Car::where('status', 'confirmed')->where('is_comfort', '0')

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
                    ->selectRaw(
                        "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                        [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                    )
                    ->having('distance', '<=', 4)
                    ->get()
                    ->pluck('user_id') // Extract user_ids as an array
                    ->toArray();
                foreach ($userIds as $userID) {
                    $client = $this->getClientByUserId($userID);
                    if ($client) {
                        $client->send($message);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $userID);
                    }
                }

                break;

            case 'comfort_car':
                $decimalPlaces = 2;
                $userIds       = Car::where('status', 'confirmed')->where('is_comfort', '1')

                    ->whereHas('owner', function ($query) {
                        $query->where('is_online', '1')
                            ->where('status', 'confirmed');
                    })
                    ->where(function ($query) use ($trip) {

                        if ($trip->animals == '1') {
                            $query->where('animals', '1');
                        }
                        if ($trip->user->gendor == 'Male') {
                            $query->where('passenger_type', 'male_female');
                        }
                    })
                    ->select('*')
                    ->selectRaw(
                        "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                        [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                    )
                    ->having('distance', '<=', 4)
                    ->get()
                    ->pluck('user_id') // Extract user_ids as an array
                    ->toArray();
                foreach ($userIds as $userID) {
                    $client = $this->getClientByUserId($userID);
                    if ($client) {
                        $client->send($message);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $userID);
                    }
                }

                break;

            case 'scooter':
                $decimalPlaces = 2;
                $userIds       = Scooter::where('status', 'confirmed')

                    ->whereHas('owner', function ($query) {
                        $query->where('is_online', '1')
                            ->where('status', 'confirmed');
                    })

                    ->select('*')
                    ->selectRaw(
                        "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                        [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                    )
                    ->having('distance', '<=', 4)
                    ->get()
                    ->pluck('user_id') // Extract user_ids as an array
                    ->toArray();
                foreach ($userIds as $userID) {
                    $client = $this->getClientByUserId($userID);
                    if ($client) {
                        $client->send($message);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $userID);
                    }
                }

                break;

            default:

                break;
        }

    }

    private function create_offer(ConnectionInterface $from, $AuthUserID, $offerRequest)
    {
        $data      = json_decode($offerRequest, true);
        $driver    = User::findOrFail($AuthUserID);
        $lastOffer = Offer::orderBy('id', 'desc')->first();

        if ($lastOffer) {
            $lastCode = $lastOffer->code;
            $code     = 'OFR-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $code = 'OFR-000001';
        }
        $trip = Trip::findOrFail($data['trip_id']);
        switch ($trip->type) {
            case 'car':
                $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                $driver_car = Car::where('user_id', $AuthUserID)->first();
                $offer      = Offer::create(['user_id' => $AuthUserID,
                    'code'                                 => $code,
                    'car_id'                               => $driver_car->id,
                    'trip_id'                              => intval($data['trip_id']),
                    'offer'                                => floatval($data['offer'])]);
                $offer_result['car_id'] = $offer->car()->first()->id;
                $response               = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng);

                break;
            case 'comfort_car':
                $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                $driver_car = Car::where('user_id', $AuthUserID)->first();
                $offer      = Offer::create(['user_id' => $AuthUserID,
                    'code'                                 => $code,
                    'car_id'                               => $driver_car->id,
                    'trip_id'                              => intval($data['trip_id']),
                    'offer'                                => floatval($data['offer'])]);
                $offer_result['car_id'] = $offer->car()->first()->id;
                $response               = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng);

                break;
            case 'scooter':
                $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                $driver_scooter = Scooter::where('user_id', $AuthUserID)->first();
                $offer          = Offer::create(['user_id' => $AuthUserID,
                    'code'                                     => $code,
                    'scooter_id'                               => $driver_scooter->id,
                    'trip_id'                                  => intval($data['trip_id']),
                    'offer'                                    => floatval($data['offer'])]);
                $offer_result['scooter_id'] = $offer->scooter()->first()->id;
                $response                   = calculate_distance($offer->scooter->lat, $offer->scooter->lng, $trip->start_lat, $trip->start_lng);

                break;
            default:

                break;
        }

        if ($trip->user->device_token) {
            // $this->firebaseService->sendNotification($trip->user->device_token,'Lady Driver - New Offer',"Offer No. (" . $offer->code . ") was created on your trip by Captain (" . auth()->user()->name .").",["screen"=>"Current Trip","ID"=>$trip->id]);
            // $data=[
            //     "title"=>"Lady Driver - New Offer",
            //     "message"=>"Offer No. (" . $offer->code . ") was created on your trip by Captain (" . auth()->user()->name .").",
            //     "screen"=>"Current Trip",
            //     "ID"=>$trip->id
            // ];
            // Notification::create(['user_id'=>$trip->user_id,'data'=>json_encode($data)]);
        }
        $x['offer_id'] = $offer->id;
        $x['trip_id']  = $trip->id;
        $data1         = [
            'type'    => 'created_offer',
            'data'    => $x,
            'message' => 'Offer Created Successfully',
        ];
        $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
        $from->send($res);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ],created offer message has been sent to user %d' . "\n", $date_time, $AuthUserID);

        $distance                                 = $response['distance_in_km'];
        $duration                                 = $response['duration_in_M'];
        $driver_                                  = $offer->user()->first();
        $offer_result['id']                       = $offer->id;
        $offer_result['user_id']                  = $offer->user()->first()->id;
        $offer_result['trip_id']                  = $trip->id;
        $offer_result['client_location_distance'] = $distance;
        $offer_result['client_location_duration'] = $duration;
        $offer_result['offer']                    = floatval($data['offer']);
        $offer_result['user']['id']               = $offer->user()->first()->id;
        $offer_result['user']['name']             = $offer->user()->first()->name;
        $offer_result['user']['image']            = 'https://api.lady-driver.com' . getFirstMedia($offer->user()->first(), $offer->user()->first()->avatarCollection);
        if ($trip->type == 'comfort_car' || $trip->type == 'car') {
            $offer_result['user']['rate'] = Trip::whereHas('car', function ($query) use ($driver_) {
                $query->where('user_id', $driver_->id);
            })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 0.00;

            $offer_result['car']['id']            = $offer->car()->first()->id;
            $offer_result['car']['image']         = 'https://api.lady-driver.com' . getFirstMedia($offer->car()->first(), $offer->car()->first()->avatarCollection);
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
            })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 0.00;
            $offer_result['scooter']['id']            = $offer->scooter()->first()->id;
            $offer_result['scooter']['image']         = 'https://api.lady-driver.com' . getFirstMedia($offer->scooter()->first(), $offer->scooter()->first()->avatarCollection);
            $offer_result['scooter']['year']          = $offer->scooter()->first()->year;
            $offer_result['scooter']['car_mark_id']   = $offer->scooter()->first()->car_mark_id;
            $offer_result['scooter']['car_model_id']  = $offer->scooter()->first()->car_model_id;
            $offer_result['scooter']['mark']['id']    = $offer->scooter()->first()->mark()->first()->id;
            $offer_result['scooter']['mark']['name']  = $offer->scooter()->first()->mark()->first()->name;
            $offer_result['scooter']['model']['id']   = $offer->scooter()->first()->model()->first()->id;
            $offer_result['scooter']['model']['name'] = $offer->scooter()->first()->model()->first()->name;
        }
        $offer_result['created_at'] = $offer->created_at;

        $client = $this->getClientByUserId($trip->user_id);
        if ($client) {
            $data2 = [
                'type' => 'new_offer',
                'data' => $offer_result,
            ];
            $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
            $client->send($message);

            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] New Offer "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);
        }

    }

    private function cancel_offer(ConnectionInterface $from, $AuthUserID, $expireOfferRequest)
    {
        $data          = json_decode($expireOfferRequest, true);
        $offer         = Offer::findOrFail($data['offer_id']);
        $offer->status = 'expired';
        $offer->save();
        $canceled_offer['offer_id'] = $offer->id;
        $data2                      = [
            'type'    => 'canceled_offer',
            'data'    => $canceled_offer,
            'message' => 'Offer canceled successfully',
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $from->send($message);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ] Message of canceled offer "%s" sent to user %d' . "\n", $date_time, $message, $AuthUserID);
        $client = $this->getClientByUserId($offer->user_id);
        if ($client) {
            $client->send($message);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of canceled offer "%s" sent to user %d' . "\n", $date_time, $message, $offer->user_id);
        }
    }

    private function accept_offer(ConnectionInterface $from, $AuthUserID, $acceptOfferRequest)
    {
        $data = json_decode($acceptOfferRequest, true);

        $offer = Offer::where('id', $data['offer_id'])->where('status', 'pending')->first();
        if (! $offer) {
            $x['offer_id'] = $data['offer_id'];
            $data1         = [
                'type'    => 'rejected_offer',
                'data'    => $x,
                'message' => 'The selected offer is not pending.',
            ];
            $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
            $from->send($res);
            $date_time = date('Y-m-d h:i:s a');

            echo sprintf('[ %s ] Message of expired offer "%s" sent to user %d' . "\n", $date_time, $res, $AuthUserID);

        } else {
            $trip = $offer->trip;
            switch ($trip->type) {
                case 'car':
                    $app_ratio    = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $trip->car_id = $offer->car_id;
                    break;

                case 'comfort_car':
                    $app_ratio    = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $trip->car_id = $offer->car_id;
                    break;

                case 'scooter':
                    $app_ratio        = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $trip->scooter_id = $offer->scooter_id;
                    break;

                default:

                    break;
            }
            if ($trip->status == 'created') {
                $trip->status = 'pending';
            }
            $trip->total_price = $offer->offer;
            $trip->app_rate    = round(($offer->offer * $app_ratio) / 100, 2);
            $trip->driver_rate = $offer->offer - round(($offer->offer * $app_ratio) / 100, 2);
            $trip->save();
            if ($trip->status == 'created') {
                $offer->status = 'accepted';
            } elseif ($trip->status == 'scheduled') {
                $offer->status = 'scheduled';
            }

            $offer->save();
            Offer::where('id', '!=', $data['offer_id'])->where('trip_id', $trip->id)->update(['status' => 'expired']);
            if ($offer->user->device_token) {
                // $this->firebaseService->sendNotification($offer->user->device_token,'Lady Driver - Accept Offer',"Your offer for trip No. (" . $trip->code . ") has been approved.",["screen"=>"Current Trip","ID"=>$trip->id]);
                // $data=[
                //     "title"=>"Lady Driver - Accept Offer",
                //     "message"=>"Your offer for trip No. (" . $trip->code . ") has been approved.",
                //     "screen"=>"Current Trip",
                //     "ID"=>$trip->id
                // ];
                // Notification::create(['user_id'=>$offer->user_id,'data'=>json_encode($data)]);
            }
            $x['offer_id'] = $offer->id;
            $x['trip_id']  = $trip->id;
            $data1         = [
                'type'    => 'accepted_offer',
                'data'    => $x,
                'message' => 'Offer accepted Successfully',
            ];
            $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
            $from->send($res);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of accept offer "%s" sent to user %d' . "\n", $date_time, $res, $AuthUserID);

            $client = $this->getClientByUserId($offer->user_id);
            if ($client) {
                $client->send($res);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of accept offer "%s" sent to user %d' . "\n", $date_time, $res, $offer->user_id);
            }

        }

    }
    private function track_car(ConnectionInterface $from, $AuthUserID, $trackCarRequest)
    {
        $data         = json_decode($trackCarRequest, true);
        $trip         = Trip::find($data['trip_id']);
        $x['trip_id'] = $data['trip_id'];
        if ($trip->type == 'comfort_car' || $trip->type == 'car') {
            $car      = $trip->car();
            dd($car->lat);
            $x['lat'] = $car->lat;
            $x['lng'] = $car->lng;
        } elseif ($trip->type == 'scooter') {
            $scooter  = $trip->scooter();
            $x['lat'] = $scooter->lat;
            $x['lng'] = $scooter->lng;
        }
        $data1 = [
            'type' => 'track_car',
            'data' => $x,

        ];
        $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
        $from->send($res);
    }
    private function send_message(ConnectionInterface $from, $AuthUserID, $trackSendMessageRequest)
    {
        $data            = json_decode($trackSendMessageRequest, true);
        $x['message_id'] = $data['message_id'];
        $data1           = [
            'type'    => 'Receiving_message',
            'data'    => $x,
            'message' => null,
        ];
        $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
        $from->send($res);
        $message = TripChat::where('id', $data['message_id'])->first();

        if ($AuthUserID == $message->trip->user_id) {
            switch ($message->trip->type) {
                case 'car':
                    $client = $this->getClientByUserId($message->trip->car->user_id);
                    if ($client) {
                        $client->send($res);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message "%s" sent to user %d' . "\n", $date_time, $res, $message->trip->car->user_id);
                    }
                    break;

                case 'comfort_car':
                    $client = $this->getClientByUserId($message->trip->car->user_id);
                    if ($client) {
                        $client->send($res);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message "%s" sent to user %d' . "\n", $date_time, $res, $message->trip->car->user_id);
                    }
                    break;

                case 'scooter':
                    $client = $this->getClientByUserId($message->trip->scooter->user_id);
                    if ($client) {
                        $client->send($res);
                        $date_time = date('Y-m-d h:i:s a');
                        echo sprintf('[ %s ] Message "%s" sent to user %d' . "\n", $date_time, $res, $message->trip->scooter->user_id);
                    }
                    break;

                default:

                    break;
            }
        } else {
            $client = $this->getClientByUserId($message->trip->user_id);
            if ($client) {
                $client->send($res);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message "%s" sent to user %d' . "\n", $date_time, $res, $message->trip->user_id);
            }
        }
    }
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;

        // echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //     , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);

        if (array_key_exists('pong', $data)) {
            echo sprintf("sss");
        } else {
            $AuthUserID = $this->clients[$from];
            if (array_key_exists('data', $data)) {
                $requestData = json_encode($data['data'], JSON_UNESCAPED_UNICODE);
            }

            switch ($data['type']) {
                case 'new_trip':
                    $this->create_trip_and_find_drivers($from, $AuthUserID, $requestData);
                    break;

                case 'new_offer':
                    $this->create_offer($from, $AuthUserID, $requestData);
                    break;

                case 'cancel_trip':
                    $this->cancel_trip($from, $AuthUserID, $requestData);
                    break;

                case 'cancel_offer':
                    $this->cancel_offer($from, $AuthUserID, $requestData);
                    break;

                case 'expire_trip':
                    $this->expire_trip($from, $AuthUserID, $requestData);
                    break;
                case 'accept_offer':
                    $this->accept_offer($from, $AuthUserID, $requestData);
                    break;
                case 'start_end_trip':
                    $this->start_end_trip($from, $AuthUserID, $requestData);
                    break;
                case 'track_car':
                    $this->track_car($from, $AuthUserID, $requestData);
                    break;
                case 'send_message':
                    $this->send_message($from, $AuthUserID, $requestData);
                    break;
                case 'ping':
                    $from->send(json_encode(['type' => 'pong']));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ], New pong has been sent' . "\n", $date_time);
                    break;
                case 'live_location':
                    $data = json_decode($requestData, true);
                    $live = LiveLocation::where('token', $data['token'])
                        ->where('expires_at', '>', now())
                        ->first();
                    $x['lat'] = $live->lat;
                    $x['lng'] = $live->lng;
                    $x['name'] = $live->user->name;
                    $from->send(json_encode(['type' => 'live_location', 'data' => $x]));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ], live location has been sent' . "\n", $date_time);
                    break;
                default:
                    $from->send(json_encode(['type' => 'pong']));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ], New pong has been sent' . "\n", $date_time);
                    break;
            }

        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages

        $userId = $this->clients[$conn];
        unset($this->clientUserIdMap[$userId]);
        $this->clients->detach($conn);
        $date_time = date('Y-m-d h:i:s a');
        echo "[ {$date_time} ],Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function getClientByUserId($userId)
    {
        return $this->clientUserIdMap[$userId] ?? null; // Retrieve client in one step
    }
}

<?php

namespace App\WebSockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\User;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\Offer;
use App\Models\Car;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $loop;
    protected $firebaseService;
    private $clientUserIdMap;

    public function __construct($loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->loop = $loop;
        $this->firebaseService = new FirebaseService();
        $this->clientUserIdMap = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        $userId = $queryParams['user_id'] ?? null;

        $userToken = $queryParams['token'] ?? null;
        $user = User::where('id', $userId)->first();


        if (!$user) {
            echo "Invalid user. Connection refused.\n";
            $conn->close();
            return;
        } else {

            $tokenParts = explode('|', $userToken);
            $tokenId = $tokenParts[0];
            $tokenValue = $tokenParts[1];

            // // Find the token by ID and user
            $token = $user->tokens()->where('id', $tokenId)->first();
            // //dd($userToken,$tokenId,$tokenValue,$token->token);

            // dd(Crypt::encryptString($tokenValue),$token->token);
            if ($token && hash('sha256', $tokenValue) ===  $token->token) {
                // Token matches
                $this->clients->attach($conn, $userId);
                $this->clientUserIdMap[$userId] = $conn;
                $date_time = date('Y-m-d h:i:s a');
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
    private function create_trip(ConnectionInterface $from, $AuthUserID, $tripRequest)
    {

        $data = json_decode($tripRequest, true);
        $response = calculate_distance($data['start_lat'], $data['start_lng'], $data['end_lat'], $data['end_lng']);
        $distance = $response['distance_in_km'];
        $duration = $response['duration_in_M'];

        $kilometer_price = floatval(Setting::where('key', 'kilometer_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
        $Air_conditioning_service_price = floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Trips')->where('type', 'number')->first()->value);
        $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
        if ($data['air_conditioned'] == 1) {
            $driver_rate = round(($distance * $kilometer_price) + $Air_conditioning_service_price, 2);
        } else {
            $driver_rate = round($distance * $kilometer_price, 2);
        }
        $app_rate = round(($distance * $kilometer_price * $app_ratio) / 100, 2);
        $total_price = $driver_rate + $app_rate;
        $lastTrip = Trip::orderBy('id', 'desc')->first();

        if ($lastTrip) {
            $lastCode = $lastTrip->code;
            $code = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $code = 'TRP-000001';
        }
        $trip = Trip::create(['user_id' => $AuthUserID,
                             'code' => $code,
                             'start_lat' => floatval($data['start_lat']),
                             'start_lng' => floatval($data['start_lng']),
                             'end_lat' => floatval($data['end_lat']),
                             'end_lng' => floatval($data['end_lng']),
                             'address1' => $data['address1'],
                             'address2' => $data['address2'],
                             'total_price' => $total_price,
                             'app_rate' => $app_rate,
                             'driver_rate' => $driver_rate,
                             'distance' => $distance,
                             'type' => $data['type']
        ]);
        $u = User::findOrFail($AuthUserID);
        $user_image = 'https://api.lady-driver.com'.getFirstMedia($u, $u->avatarCollection);
        $newTrip['id'] = $trip->id;
        $newTrip['code'] = $code;
        $newTrip['user_id'] = intval($AuthUserID);
        $newTrip["car_id"] = null;
        $newTrip["start_date"] = null;
        $newTrip["end_date"] = null;
        $newTrip["start_time"] = null;
        $newTrip["end_time"] = null;
        $newTrip['start_lat'] = floatval($data['start_lat']);
        $newTrip['start_lng'] = floatval($data['start_lng']);
        $newTrip['end_lat'] = floatval($data['end_lat']);
        $newTrip['end_lng'] = floatval($data['end_lng']);
        $newTrip['address1'] = $data['address1'];
        $newTrip['address2'] = $data['address2'];
        $newTrip['total_price'] = $total_price;
        $newTrip['app_rate'] = $app_rate;
        $newTrip['driver_rate'] = $driver_rate;
        $newTrip['distance'] = $distance;
        $newTrip['type'] = $data['type'];
        if ($data['air_conditioned'] == '1') {
            $trip->air_conditioned = '1';
            $newTrip['air_conditioned'] = '1';
        } else {
            $trip->air_conditioned = '0';
            $newTrip['air_conditioned'] = '0';
        }


        if ($data['animal'] == '1') {
            $trip->animals = '1';
            $newTrip['animals'] = '0';
        } else {
            $trip->animals = '0';
            $newTrip['animals'] = '0';
        }
        $trip->save();
        $from->send(json_encode(['type' => 'created_trip','message' => 'Trip Created Successfully']));
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $AuthUserID);

        $newTrip["client_stare_rate"] = 0;
        $newTrip["client_comment"] = null;
        $newTrip["status"] = "created";
        $newTrip["cancelled_by_id"] = null;
        $newTrip["trip_cancelling_reason_id"] = null;
        $newTrip["driver_stare_rate"] = 0;
        $newTrip["driver_comment"] = null;
        $newTrip["payment_status"] = "unpaid";
        $newTrip["current_offer"] = null;
        $newTrip['duration'] = $duration;



        $radius = 6371;
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
                    $car2 = Car::where('user_id', $eligibleDriverId)->first();
                    $response2 = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                    $distance2 = $response2['distance_in_km'];
                    $duration2 = $response2['duration_in_M'];
                    $newTrip['client_location_distance'] = $distance2;
                    $newTrip['client_location_duration'] = $duration2;
                    $newTrip['created_at'] = $trip->created_at;
                    $newTrip['updated_at'] = $trip->updated_at;
                    $newTrip['user']['id'] = intval($AuthUserID);
                    $newTrip['user']['name'] = $u->name;
                    $newTrip['user']['image'] = $user_image;
                    $data2['type'] = 'new_trip';
                    $data2['data'] = $newTrip;
                    $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
                    $client->send($message);
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                }
            }
        }

    }
    private function cancel_trip(ConnectionInterface $from, $AuthUserID, $cancelTripRequest)
    {
        $data = json_decode($cancelTripRequest, true);
        $trip = Trip::findOrFail($data['trip_id']);
        $trip->status = 'cancelled';
        $trip->cancelled_by_id = $AuthUserID;
        $trip->trip_cancelling_reason_id = $data['reason_id'];
        $trip->save();
        $canceled_trip['trip_id'] = $trip->id;
        $data2 = [
            'type' => 'canceled_trip',
            'data' => $canceled_trip,
            'message' => 'Trip canceled successfully'
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $client = $this->getClientByUserId($trip->user_id);
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
    private function create_offer(ConnectionInterface $from, $AuthUserID, $offerRequest)
    {
        $data = json_decode($offerRequest, true);
        $driver_car = Car::where('user_id', $AuthUserID)->first();
        $lastOffer = Offer::orderBy('id', 'desc')->first();

        if ($lastOffer) {
            $lastCode = $lastOffer->code;
            $code = 'OFR-' . str_pad((int) substr($lastCode, 4) + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $code = 'OFR-000001';
        }
        $offer = Offer::create(['user_id' => $AuthUserID,
                                'code' => $code,
                               'car_id' => $driver_car->id,
                               'trip_id' => intval($data['trip_id']),
                               'offer' => floatval($data['offer'])]);
        $trip = Trip::findOrFail($data['trip_id']);
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
        $x['offer_id']=$offer->id;
        $x['trip_id'] = $trip->id;
        $data1 = [
            'type' => 'created_offer',
            'data' => $x,
            'message'=>'Offer Created Successfully'
        ];
        $res = json_encode($data1, JSON_UNESCAPED_UNICODE);
        $from->send($res);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ],created offer message has been sent to user %d' . "\n", $date_time, $AuthUserID);

        $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', 'Trips')->where('type', 'number')->first()->value);
        $response = calculate_distance($offer->car->lat, $offer->car->lng, $trip->start_lat, $trip->start_lng);
        $distance = $response['distance_in_km'];
        $duration = $response['duration_in_M'];

        $offer_result['id'] = $offer->id;
        $offer_result['user_id'] = $offer->user()->first()->id;
        $offer_result['car_id'] = $offer->car()->first()->id;
        $offer_result['trip_id'] = $trip->id;
        $offer_result['client_location_distance'] = $distance;
        $offer_result['client_location_duration'] = $duration;
        $offer_result['offer'] = round(($offer->offer - $trip->driver_rate) + (($offer->offer - $trip->driver_rate) * $app_ratio / 100) + $trip->total_price, 2);
        $offer_result['user']['id'] = $offer->user()->first()->id;
        $offer_result['user']['name'] = $offer->user()->first()->name;
        $offer_result['user']['image'] = 'https://api.lady-driver.com' . getFirstMedia($offer->user()->first(), $offer->user()->first()->avatarCollection);
        $offer_result['car']['id'] = $offer->car()->first()->id;
        $offer_result['car']['image'] = 'https://api.lady-driver.com' . getFirstMedia($offer->car()->first(), $offer->car()->first()->avatarCollection);
        $offer_result['car']['year'] = $offer->car()->first()->year;
        $offer_result['car']['car_mark_id'] = $offer->car()->first()->car_mark_id;
        $offer_result['car']['car_model_id'] = $offer->car()->first()->car_model_id;
        $offer_result['car']['mark']['id'] = $offer->car()->first()->mark()->first()->id;
        $offer_result['car']['mark']['name'] = $offer->car()->first()->mark()->first()->name;
        $offer_result['car']['model']['id'] = $offer->car()->first()->model()->first()->id;
        $offer_result['car']['model']['name'] = $offer->car()->first()->model()->first()->name;

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
    private function expire_offer(ConnectionInterface $from, $AuthUserID, $expireOfferRequest)
    {
        $data = json_decode($expireOfferRequest, true);
        $offer = Offer::findOrFail($data['offer_id']);
        $offer->status = 'expired';
        $offer->save();
        $canceled_offer['offer_id'] = $offer->id;
        $data2 = [
            'type' => 'canceled_offer',
            'data' => $canceled_offer,
            'message' => 'Offer canceled successfully'
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $client = $this->getClientByUserId($offer->trip->user_id);
        if ($client) {
            $client->send($message);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of canceled offer "%s" sent to user %d' . "\n", $date_time, $message, $offer->trip->user_id);
        }
    }
    private function expire_trip(ConnectionInterface $from, $AuthUserID, $expireTripRequest)
    {
        $data = json_decode($expireTripRequest, true);
        $trip = Trip::findOrFail($data['trip_id']);
        $trip->status = 'expired';
        $trip->save();
        $expired_trip['trip_id'] = $trip->id;
        $data2 = [
            'type' => 'expired_trip',
            'data' => $expired_trip,
            'message' => 'Trip expired successfully'
        ];
        $message = json_encode($data2, JSON_UNESCAPED_UNICODE);
        $decimalPlaces = 2;
        $userIds = Car::where('status', 'confirmed')
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
                    ->select('user_id') // Select only the user_id column
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
            $requestData = json_encode($data['data'], JSON_UNESCAPED_UNICODE);

            switch ($data['type']) {
                case 'new_trip':
                    $this->create_trip($from, $AuthUserID, $requestData);
                    break;

                case 'new_offer':
                    $this->create_offer($from, $AuthUserID, $requestData);
                    break;

                case 'cancel_trip':
                    $this->cancel_trip($from, $AuthUserID, $requestData);
                    break;

                case 'cancel_offer':
                    $this->expire_offer($from, $AuthUserID, $requestData);
                    break;

                case 'expire_trip':
                    $this->expire_trip($from, $AuthUserID, $requestData);
                    break;
                case 'ping':
                    $from->send(json_encode(['type' => 'pong']));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ], New pong has been sent' . "\n", $date_time);
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

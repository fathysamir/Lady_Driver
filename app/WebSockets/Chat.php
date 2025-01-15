<?php
namespace App\WebSockets;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\User;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\Car;
use App\Models\Notification;
use App\Services\FirebaseService;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $loop;
    protected $firebaseService;

    public function __construct($loop) {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
        $this->firebaseService = new FirebaseService;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        $userId = $queryParams['user_id'] ?? null;

        $userToken = $queryParams['token'] ?? null;
        $user = User::where('id', $userId)->first();
      

        if (!$user) {
            echo "Invalid user. Connection refused.\n";
            $conn->close();
            return;
        }else{
           
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
                $date_time=date('Y-m-d h:i:s a');
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

    private function periodicPing(ConnectionInterface $conn) {
        $timer = 60; // Send a ping every 60 seconds
    
        $this->loop->addPeriodicTimer($timer, function() use ($conn) {
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
    private function create_trip($AuthUserID, $tripRequest) {
        $data = json_decode($tripRequest, true);
        $response=calculate_distance($data['start_lat'],$data['start_lng'],$data['end_lat'],$data['end_lng']);
         $distance=$response['distance_in_km'];
         $duration=$response['duration_in_M'];
         
         $kilometer_price=floatval(Setting::where('key','kilometer_price')->where('category','Trips')->where('type','number')->first()->value);
         $Air_conditioning_service_price=floatval(Setting::where('key','Air_conditioning_service_price')->where('category','Trips')->where('type','number')->first()->value);
         $app_ratio=floatval(Setting::where('key','app_ratio')->where('category','Trips')->where('type','number')->first()->value);
         if($data['air_conditioned']==1){
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
         $trip=Trip::create(['user_id'=>$AuthUserID,
                             'code'=>$code,
                             'start_lat'=>floatval($data['start_lat']),
                             'start_lng'=>floatval($data['start_lng']),
                             'end_lat'=>floatval($data['end_lat']),
                             'end_lng'=>floatval($data['end_lng']),
                             'address1' => $data['address1'],
                             'address2' => $data['address2'],
                             'total_price'=>$total_price,
                             'app_rate'=>$app_rate,
                             'driver_rate'=>$driver_rate,
                             'distance'=>$distance,
                             'type'=>$data['type']
        ]);
        $u=User::findOrFail($AuthUserID);
        $user_image=getFirstMediaUrl($u,$u->avatarCollection);
        $newTrip['id']=$trip->id;
        $newTrip['code']=$code;
        $newTrip['start_lat']=floatval($data['start_lat']);
        $newTrip['start_lng']=floatval($data['start_lng']);
        $newTrip['end_lat']=floatval($data['end_lat']);
        $newTrip['end_lng']=floatval($data['end_lng']);
        $newTrip['address1']=$data['address1'];
        $newTrip['address2']=$data['address2'];
        $newTrip['total_price']=$total_price;
        $newTrip['app_rate']=$app_rate;
        $newTrip['driver_rate']=$driver_rate;
        $newTrip['distance']=$distance;
        $newTrip['type']=$data['type'];
        if($data['air_conditioned']=='1'){
            $trip->air_conditioned='1';
            $newTrip['air_conditioned']='1';
        }else{
            $trip->air_conditioned='0';
            $newTrip['air_conditioned']='0';
        }
        

        if($data['animal']=='1'){
            $trip->animals='1';
            $newTrip['animals']='0';
        }else{
            $trip->animals='0';
            $newTrip['animals']='0';
        }
        $trip->save();
        $newTrip['duration']=$duration;
        
       

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
       
        $eligibleDriverIds = [];
        
        foreach ($eligibleCars as $car) {
            $eligibleDriverIds[] = $car->user_id;
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
        foreach ($this->clients as $client) {
            $clientUserId = $this->clients[$client];
            if (in_array($clientUserId, $eligibleDriverIds)) {
                $car=Car::where('user_id',$clientUserId)->first();
                $response=calculate_distance($car->lat,$car->lng,$trip->start_lat,$trip->start_lng);
                $distance=$response['distance_in_km'];
                $duration=$response['duration_in_M'];
                $newTrip['client_location_distance']=$distance;
                $newTrip['client_location_duration']=$duration;
                $newTrip['user_id']=$AuthUserID;
                $newTrip['user_name']=$u->name;
                $newTrip['user_image']=$user_image;
                $data2['type']='new_trip';
                $data2['data']=$newTrip;
                $message=json_encode($data2, JSON_UNESCAPED_UNICODE);
                $client->send($message);
                $date_time=date('Y-m-d h:i:s a');
                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n",$date_time,$message, $clientUserId);
            }
        }
    }
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        
        // echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //     , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        
        $data = json_decode($msg, true);

        if(array_key_exists('pong', $data)){
            echo sprintf("sss");
        }else{
            if ($data['type']=='new_trip') {
                $AuthUserID=$this->clients[$from];
                $tripRequest = json_encode($data['data'], JSON_UNESCAPED_UNICODE);

                $this->create_trip($AuthUserID,$tripRequest);
            }
            // foreach ($this->clients as $client) {
            //     if ($from !== $client) {
            //         $clientUserId = $this->clients[$client];
                    
            //         if (array_key_exists('to_user_id', $data)) {
                    
            //             if ($clientUserId == $toUserId) {
            //                 $client->send($msg);
            //                 $date_time=date('Y-m-d h:i:s a');
            //                 echo sprintf('[ %s ],Message "%s" sent from user %d sent to user %d' . "\n",$date_time,$msg, $this->clients[$from], $toUserId);
            //             }
                        
            //         }
            //     }
                
            // }
        }
        
        
        //$data = json_decode($msg, true);
        // if ($data['type'] === 'candidate') {
        //     // Broadcast the ICE candidate to all clients except the sender
        //     foreach ($this->clients as $client) {
        //         if ($from !== $client) {
        //             $client->send(json_encode($data));
        //         }
        //     }
        // }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        $date_time=date('Y-m-d h:i:s a');
        echo "[ {$date_time} ],Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
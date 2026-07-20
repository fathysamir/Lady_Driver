<?php
namespace App\WebSockets;

use App\Models\Car;
use App\Models\LiveLocation;
use App\Models\Offer;
use App\Models\Scooter;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Trip;
use App\Models\TripCancellingReason;
use App\Models\TripDestination;
use App\Models\User;
use Carbon\Carbon;
use Clue\React\Redis\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\TimerInterface;
use App\Services\FirebaseService;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $loop;
    protected $firebaseService;
    protected $messaging;
    private $clientUserIdMap;
    private $cachedAccessToken = null;
private $tokenExpiresAt = 0;
private $routeCache = [];
private $lastPong = [];
private $pingTimers = [];



    public function __construct($loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->loop    = $loop;
        // ADD THIS after $this->loop = $loop;
        $this->firebaseService = new FirebaseService();
        /*
try {
    $firebase        = (new \Kreait\Firebase\Factory)->withServiceAccount(storage_path('firebase_credentials.json'));
    $this->messaging = $firebase->createMessaging();
    echo "✅ Firebase initialized\n";
} catch (\Exception $e) {
    echo "❌ Firebase initialization failed: " . $e->getMessage() . "\n";

}
*/
        $this->clientUserIdMap = [];
        $this->connectRedis(new Factory($loop));

    }
    private function connectRedis($factory)
{
    $factory->createClient('redis://127.0.0.1:6379')->then(function ($redis) use ($factory) {
        echo "✅ Connected to Redis\n";
        $redis->psubscribe('*');

        $redis->on('pmessage', function ($pattern, $channel, $message) {
            $payload = json_decode($message, true);
            $parts   = explode('.', $channel);
            $userId  = $parts[count($parts) - 1] ?? null;

            if (!$userId) return;

            echo "📡 Received from Redis channel={$channel}, userId={$userId}\n";

            $event = [
                'type' => $payload['event'] ?? null,
                'data' => $payload['data'] ?? $payload,
            ];

            $delivered = false;

            if (isset($this->clientUserIdMap[$userId])) {
                try {
                    $this->clientUserIdMap[$userId]->send(
                        json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    );
                    $delivered = true;
                    echo "➡️ Sent via WS to user {$userId}\n";
                } catch (\Exception $e) {
                    echo "❌ WS send failed for user {$userId}, purging dead connection: " . $e->getMessage() . "\n";
                    unset($this->clientUserIdMap[$userId]);
                }
            }

            if (!$delivered) {
                $eventType = $payload['event'] ?? null;

                // إشعارات الـ push (فولباك) مسموحة بس لنوع "new_trip"، وبس للكباتن (سواق/سكوتر)
                if ($eventType !== 'new_trip') {
                    echo "🔕 Skipped fallback push for user {$userId} (event: " . ($eventType ?? 'unknown') . ") — only 'new_trip' pushes allowed.\n";
                } else {
                    $user = \App\Models\User::find($userId);
                    $isDriver = $user && ($user->car()->exists() || $user->scooter()->exists());

                    if ($isDriver) {
                        $this->sendPushToUser($user, [
                            'screen'   => 'new_trip',
                            'id'       => (string) ($payload['data']['trip_id'] ?? ($payload['data']['id'] ?? '')),
                            'title_en' => 'New Trip Near You',
                            'body_en'  => $payload['body_en'] ?? 'There is a new trip near you',
                            'title_ar' => 'رحلة جديدة بالقرب منك',
                            'body_ar'  => $payload['body_ar'] ?? 'يوجد رحلة جديدة بالقرب منك',
                        ]);
                        echo "📲 Fallback 'new_trip' push sent to driver {$userId}\n";
                    } else {
                        echo "🔕 Skipped fallback push for user {$userId} (client or user not found — no push allowed).\n";
                    }
                }
            }

            if (($payload['event'] ?? null) === 'driver_arriving') {
                $this->driverArrivingBroadcast($payload);
            }
        });

        $redis->on('close', function () use ($factory) {
            echo "⚠️ Redis connection closed, reconnecting in 3s...\n";
            $this->loop->addTimer(3, function () use ($factory) {
                $this->connectRedis($factory);
            });
        });

        $redis->on('error', function (\Exception $e) {
            echo "❌ Redis error: " . $e->getMessage() . "\n";
        });

    }, function (\Exception $e) use ($factory) {
        echo "❌ Redis connection failed, retrying in 3s: " . $e->getMessage() . "\n";
        $this->loop->addTimer(3, function () use ($factory) {
            $this->connectRedis($factory);
        });
    });
}
/**
 * فلتر تمهيدي بالـ SQL (خط مستقيم) + فلتر نهائي دقيق بمسافة الطريق الفعلي.
 * بيرجع نفس الـ Collection بس بعد استبعاد اللي مسافتهم الحقيقية برا النطاق،
 * وبيخزن المسافة/المدة الحقيقية على كل عنصر عشان متعملش نفس الـ API call مرتين.
 */
private function filterByRealDistance($items, $lat, $lng, $min = 0.5, $max = 7)
{
    return $items->filter(function ($item) use ($lat, $lng, $min, $max) {
        $response = calculate_distance($item->lat, $item->lng, $lat, $lng);
        $real = $response['distance_in_km'] ?? null;

        echo "📏 Item {$item->id} real distance: " . ($real ?? 'NULL') . " km\n";

        if ($real === null) {
            return false;
        }

        $item->_real_distance_km = round($real, 2);
        $item->_real_duration_m  = intval($response['duration_in_M'] ?? 0);

        return $real >= $min && $real <= $max;
    })->values();
}
///////////////////////////////////////////////////////////////////////////////////////
private function getUserFcmTokens($user): array
{
    return $user->tokens()
        ->where('name', 'like', 'fcm::%')
        ->pluck('name')
        ->map(fn($name) => str_replace('fcm::', '', $name))
        ->filter(fn($token) => $token && $token !== 'no-device')
        ->values()
        ->toArray();
}

private function sendPushToUser($user, array $data): void
{
    $tokens = $this->getUserFcmTokens($user);

    // fallback to device_token column if no fcm:: tokens yet (old users)
    if (empty($tokens) && $user->device_token) {
        $tokens = [$user->device_token];
    }

    foreach ($tokens as $token) {
        $this->sendPushNotification($token, $data);
    }
}

///////////////////////////////////////////////////////////////////////////////////////

private function sendPushNotification(string $deviceToken, array $data): void
{
    if (empty(trim($deviceToken))) {
        return;
    }

    try {
        // =========================
        // 1) Access Token
        // =========================
        $accessToken = $this->getFirebaseAccessToken();

        if (!$accessToken) {
            echo "❌ No access token available\n";
            return;
        }

        $credentialsData = json_decode(
            file_get_contents(storage_path('firebase_credentials.json')),
            true
        );

        $projectId = $credentialsData['project_id'];

        // =========================
        // 2) Language
        // =========================
        $lang = $data['lang'] ?? 'en';

        $title = $lang === 'ar'
            ? ($data['title_ar'] ?? 'رحلة جديدة')
            : ($data['title_en'] ?? 'New Trip Near You');

        $body = $lang === 'ar'
            ? ($data['body_ar'] ?? 'يوجد رحلة جديدة بالقرب منك')
            : ($data['body_en'] ?? 'New Trip Near You');

        // =========================
        // 3) Clean data payload
        // =========================
        $stringData = array_map('strval', $data);

        // =========================
        // 4) FCM Payload
        // =========================
        $payload = [
            'message' => [
                'token' => $deviceToken,

                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],

                'data' => $stringData,

                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'default',
                    ],
                ],

                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // =========================
        // 5) Send Request
        // =========================
        echo "🔔 Sending push to: {$deviceToken}\n";

        $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        // =========================
        // 6) Error Handling
        // =========================
        if ($httpCode !== 200) {

            $errorMessage = $responseData['error']['message'] ?? $response;

            $errorCode = $responseData['error']['details'][0]['errorCode'] ?? null;

            echo "❌ Push failed [{$httpCode}]: {$errorMessage}\n";

            // remove invalid tokens
            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                \App\Models\User::where('device_token', $deviceToken)
                    ->update(['device_token' => null]);

                echo "🧹 Cleared invalid FCM token\n";
            }

            return;
        }

        echo "✅ Push sent successfully\n";

    } catch (\Exception $e) {
        echo "❌ sendPushNotification exception: " . $e->getMessage() . "\n";
    }
}
///////////////////////////////////////////////////////////////////////////////////////


private function getFirebaseAccessToken(): ?string
{
    if ($this->cachedAccessToken && time() < $this->tokenExpiresAt - 60) {
        return $this->cachedAccessToken;
    }

    try {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . storage_path('firebase_credentials.json'));
        $credentials = \Google\Auth\ApplicationDefaultCredentials::getCredentials(
            'https://www.googleapis.com/auth/firebase.messaging'
        );
        $token = $credentials->fetchAuthToken();

        $this->cachedAccessToken = $token['access_token'];
        $this->tokenExpiresAt = time() + ($token['expires_in'] ?? 3600);

        return $this->cachedAccessToken;
    } catch (\Exception $e) {
        echo "❌ Token fetch failed: " . $e->getMessage() . "\n";
        return null;
    }
}

///////////////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////////////
    public function driverArrivingBroadcast($data)
    {
        $maxDuration = 300; // seconds
        $interval    = 60;  // seconds
        $startTime   = time();
        $timer       = $this->loop->addPeriodicTimer($interval, function (TimerInterface $timer) use ($data, $startTime, $maxDuration) {
            $trip = Trip::findOrFail($data['data']['trip_id']);
            $trip->refresh();
            if (in_array($trip->status, ['in_progress', 'cancelled'])) {
                echo "🛑 Trip {$trip->id} stopped broadcasting (status: {$trip->status})\n";
                $this->loop->cancelTimer($timer);
                return;
            }

            // Calculate waiting time in minutes (1, 2, 3...)
            $waitingSeconds = time() - $startTime;
            $waitingMinutes = floor($waitingSeconds / 60);

            if ($waitingSeconds > $maxDuration) {
                echo "🕓 Trip {$trip->id} broadcast expired after 5 minutes\n";
                $this->loop->cancelTimer($timer);
                return;
            }
            $extraCost = $waitingMinutes * 5;

            // Dynamic message
            $message = "Your driver has arrived. Waiting time: {$waitingMinutes} min. Additional cost: {$extraCost} EGP.";

            if ($trip->user->device_token) {
                // $this->firebaseService->sendNotification($trip->user->device_token,'Lady Driver - Driver Arriving',$message,["screen"=>"Current Trip","ID"=>$trip->id]);
                // $data=[
                //     "title"=>"Lady Driver - Driver Arriving",
                //     "message"=>$message,
                //     "screen"=>"Current Trip",
                //     "ID"=>$trip->id
                // ];
                // Notification::create(['user_id'=>$car->user_id,'data'=>json_encode($data)]);
            }

        });
    }
   ///////////////////////////////////////////////////////////////////////////////////////

   private function startPendingWatchdog(Trip $trip)
{
    $maxDuration = 7200; // 2 hours
    $interval    = 60;
    $startTime   = time();

    $this->loop->addPeriodicTimer($interval, function (TimerInterface $timer) use ($trip, $startTime, $maxDuration) {
        $trip->refresh();

        if (!in_array($trip->status, ['pending'])) {
            echo "✅ Watchdog: trip {$trip->id} is now {$trip->status}, stopping.\n";
            $this->loop->cancelTimer($timer);
            return;
        }

        if (time() - $startTime < $maxDuration) {
            echo "⏳ Watchdog: trip {$trip->id} still pending...\n";
            return;
        }

        echo "🔥 Watchdog: trip {$trip->id} stuck in pending for 2 hours, expiring.\n";
        $this->loop->cancelTimer($timer);

        $trip->status = 'expired';
        $trip->save();

        $payload = json_encode([
            'type'    => 'expired_trip',
            'data'    => ['trip_id' => $trip->id],
            'message' => 'Your driver did not start the trip in time.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $client = $this->getClientByUserId($trip->user_id);
        if ($client) $client->send($payload);

        $driverUserId = null;
        if ($trip->car_id && $trip->car) {
            $driverUserId = $trip->car->user_id;
        } elseif ($trip->scooter_id && $trip->scooter) {
            $driverUserId = $trip->scooter->user_id;
        }

        if ($driverUserId) {
            $driver = $this->getClientByUserId($driverUserId);
            if ($driver) $driver->send($payload);
        }
    });
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
                $this->lastPong["live_{$live->id}"] = time();

                $date_time = date('Y-m-d h:i:s a');
                $this->periodicPing($conn);

                echo "[ {$date_time} ], Live Location Connection! Live ID: {$live->id}, Conn ID: ({$conn->resourceId})\n";
                $conn->send(json_encode([
                    'type'    => 'live_connected',
                    'message' => 'Live location connected successfully',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

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
                    if (isset($this->clientUserIdMap[$userId])) {
                        $oldConn = $this->clientUserIdMap[$userId];
                        try {
                            $oldConn->close();
                        } catch (\Exception $e) {
                            echo "Old connection already closed\n";
                        }
                        $this->clients->detach($oldConn);
                        unset($this->clientUserIdMap[$userId]);
                    }
                    // Token matches
                    $this->clients->attach($conn, $userId);
                    $this->clientUserIdMap[$userId] = $conn;
                    $this->lastPong[$userId] = time();
                    $date_time                      = date('Y-m-d h:i:s a');
                    //$conn->send(json_encode(['type' => 'ping']));
                    $this->periodicPing($conn);
                    //echo "New connection! ({$conn->resourceId})\n";
                    echo "[ {$date_time} ],New connection! User ID: {$userId}, Connection ID: ({$conn->resourceId})\n";
                    if ($user->is_online == '0' && $user->auto_offline_at) {
                        if ($user->auto_offline_at->diffInMinutes(now()) <= 10) {
                            $user->is_online       = '1';
                            $user->auto_offline_at = null;
                            $user->save();

                            $conn->send(json_encode([
                                'type' => 'online_status_updated',
                                'data' => ['is_online' => $user->is_online]
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        }
                    }
                    if ($user->is_online == '1') {
                        $this->pushPendingTripsToDriver($user, $conn);
                    }

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
        $interval = 30;
        $connId   = spl_object_id($conn);

        $timer = $this->loop->addPeriodicTimer($interval, function () use ($conn, $connId) {
            $userId = $this->getUserIdByConn($conn);

            if ($userId && isset($this->lastPong[$userId]) && (time() - $this->lastPong[$userId] > 90)) {
                echo "💀 Connection {$conn->resourceId} (user {$userId}) stale, closing & purging.\n";
                unset($this->clientUserIdMap[$userId]);
                unset($this->lastPong[$userId]);
                $this->clients->detach($conn);
                try { $conn->close(); } catch (\Exception $e) {}
                $this->cancelPingTimer($connId);
                return;
            }

            try {
                $conn->send(json_encode(['type' => 'ping']));
                echo "[ " . date('Y-m-d h:i:s a') . " ], Ping sent to Connection {$conn->resourceId}\n";
            } catch (\Exception $e) {
                $userId = $this->getUserIdByConn($conn);
                if ($userId) {
                    unset($this->clientUserIdMap[$userId]);
                    unset($this->lastPong[$userId]);
                }
                echo "[ " . date('Y-m-d h:i:s a') . " ], Connection {$conn->resourceId} closed during ping\n";
                $this->cancelPingTimer($connId);
            }
        });

        $this->pingTimers[$connId] = $timer;
    }

    private function cancelPingTimer($connId)
    {
        if (isset($this->pingTimers[$connId])) {
            $this->loop->cancelTimer($this->pingTimers[$connId]);
            unset($this->pingTimers[$connId]);
        }
    }
    private function create_trip_and_find_drivers(ConnectionInterface $from, $AuthUserID, $tripRequest)
{
    $data = json_decode($tripRequest, true);
    $type = $data['type'];

    switch ($type) {
        case 'car':
            $maximum_distance_long_trip = floatval(Setting::where('key', 'maximum_distance_car_long_trip')->where('category', 'Car Trips')->where('type', 'number')->first()->value);
            $newTrip["car_id"] = null;
            break;
        case 'comfort_car':
            $maximum_distance_long_trip = floatval(Setting::where('key', 'maximum_distance_comfort_long_trip')->where('category', 'Comfort Trips')->where('type', 'number')->first()->value);
            $newTrip["car_id"] = null;
            break;
        case 'scooter':
            $maximum_distance_long_trip = floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->where('type', 'number')->first()->value);
            $newTrip["scooter_id"] = null;
            break;
        default:
            $maximum_distance_long_trip = 0;
            break;
    }

    $distance = (float) $data['distance'];
    $duration = (int) $data['duration'];

    // ── Peak time detection ──
    $peakJson  = Setting::where('key', 'peak_times')->where('category', 'Trips')->where('type', 'options')->first()->value;
    $peakTimes = json_decode($peakJson, true);

    if (empty($data['start_date']) || empty($data['start_time'])) {
        $start_date = now()->toDateString();
        $start_time = now()->format('H:i');
        $scheduled  = '0';
        $TripStatus = 'created';
    } else {
        $start_date = date('Y-m-d', strtotime($data['start_date']));
        $start_time = date('H:i', strtotime($data['start_time']));
        $scheduled  = '1';
        $TripStatus = 'scheduled';
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

    // ── Student check ──
    $student = Student::where('user_id', $AuthUserID)
        ->where('status', 'confirmed')
        ->where('student_discount_service', '1')
        ->first();

    $student_trips_count = Trip::where('user_id', $AuthUserID)
        ->where('student_trip', '1')
        ->where('status', 'completed')
        ->where('start_date', $start_date)
        ->count();

    $is_student   = $student && $student_trips_count < 3;
    $student_trip = $is_student ? '1' : '0';

    // ── Price calculator ──
    $calcPrice = function (
        $distance,
        $km_short, $km_medium, $km_long,
        $max_short, $max_medium, $max_long,
        $less_cost,
        $peak_rate,
        $student_discount,
        $air_conditioning_rate,
        $air_conditioned,
        $isPeak,
        $is_student
    ) {
        $base = 0;

        if ($distance >= $max_short) {
            $base += $km_short * $max_short;
        } else {
            $base += $km_short * $distance;
        }

        if ($distance >= $max_medium) {
            $base += $km_medium * ($max_medium - $max_short);
        } elseif ($distance > $max_short) {
            $base += $km_medium * ($distance - $max_short);
        }

        if ($distance >= $max_long) {
            $base += $km_long * ($max_long - $max_medium);
        } elseif ($distance > $max_medium) {
            $base += $km_long * ($distance - $max_medium);
        }

        $air_cost = 0;
        if ($air_conditioning_rate > 0 && $air_conditioned) {
            $air_cost = round($base * ($air_conditioning_rate / 100), 2);
        }

        $peak_cost = 0;
        if ($isPeak && $peak_rate > 0) {
            $peak_cost = round($base * ($peak_rate / 100), 2);
        }

        $total_before_discount = ceil($base + $air_cost + $peak_cost);

        if ($total_before_discount < $less_cost) {
            $total_before_discount = $less_cost;
        }

        $discount = 0;
        if ($is_student) {
            $discount = round($total_before_discount * ($student_discount / 100), 2);
        }

        $total_cost = $total_before_discount - $discount;

        if ($total_cost < $less_cost) {
            $total_cost = $less_cost;
            $discount   = $total_before_discount - $less_cost;
        }

        return [
            'total_cost_before_discount' => (float) $total_before_discount,
            'discount'                   => (float) $discount,
            'total_cost'                 => (float) $total_cost,
        ];
    };

    // ── Server-side price calculation per type ──
    switch ($type) {
        case 'car':
            $priceResult = $calcPrice(
                $distance,
                floatval(Setting::where('key', 'kilometer_price_car_short_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_car_medium_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_car_long_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_car_short_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_car_medium_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_car_long_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'less_cost_for_car_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'increase_rate_peak_time_car_trip')->where('category', 'Car Trips')->first()->value),
                floatval(Setting::where('key', 'student_discount')->where('category', 'Car Trips')->first()->value),
                0,
                $data['air_conditioned'] == '1',
                $isPeak,
                $is_student
            );
            break;

        case 'comfort_car':
            $priceResult = $calcPrice(
                $distance,
                floatval(Setting::where('key', 'kilometer_price_comfort_short_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_comfort_medium_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_comfort_long_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_comfort_short_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_comfort_medium_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_comfort_long_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'less_cost_for_comfort_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'increase_rate_peak_time_comfort_trip')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'student_discount')->where('category', 'Comfort Trips')->first()->value),
                floatval(Setting::where('key', 'Air_conditioning_service_price')->where('category', 'Car Trips')->first()->value),
                true,
                $isPeak,
                $is_student
            );
            break;

        case 'scooter':
            $priceResult = $calcPrice(
                $distance,
                floatval(Setting::where('key', 'kilometer_price_scooter_short_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_scooter_medium_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'kilometer_price_scooter_long_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_scooter_short_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_scooter_medium_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'maximum_distance_scooter_long_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'less_cost_for_scooter_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'increase_rate_peak_time_scooter_trip')->where('category', 'Scooter Trips')->first()->value),
                floatval(Setting::where('key', 'student_discount')->where('category', 'Scooter Trips')->first()->value),
                0,
                false,
                $isPeak,
                $is_student
            );
            break;

        default:
            $priceResult = ['total_cost' => 0, 'discount' => 0];
            break;
    }

   // Server-calculated base price
$server_total = $priceResult['total_cost'];
$client_total = floatval($data['total_cost'] ?? 0);

// Allow client to send higher price (from +/- buttons), but never lower than server price
$total_cost = ($client_total > $server_total) ? $client_total : $server_total;
$discount   = $priceResult['discount'];

    if ($distance > $maximum_distance_long_trip) {
        $from->send(json_encode([
            'type'    => 'error',
            'message' => "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km).",
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return;
    }

    $lastTrip = Trip::orderBy('id', 'desc')->first();
    if ($lastTrip) {
        $lastCode = $lastTrip->code;
        $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 12, '0', STR_PAD_LEFT);
    } else {
        $code = 'TRP-000000000001';
    }

    do {
        $barcode = Str::uuid();
    } while (Trip::where('barcode', $barcode)->exists());

    $trip = Trip::create([
        'user_id'          => $AuthUserID,
        'code'             => $code,
        'barcode'          => $barcode,
        'start_lat'        => floatval($data['start_lat']),
        'start_lng'        => floatval($data['start_lng']),
        'address1'         => $data['address1'],
        'total_price'      => $total_cost,
        'distance'         => $distance,
        'duration'         => $duration,
        'type'             => $data['type'],
        'start_date'       => $start_date,
        'start_time'       => $start_time,
        'scheduled'        => $scheduled,
        'payment_method'   => $data['payment_method'],
        'remaining_amount' => $total_cost,
        'discount'         => $discount,
        'student_trip'     => $student_trip,
    ]);

    // Set separately (not in fillable or need explicit control)
    $trip->status          = $TripStatus;
    $trip->air_conditioned = ($type === 'comfort_car' || $data['air_conditioned'] == '1') ? '1' : '0';
    $trip->animals         = $data['animals'] == '1' ? '1' : '0';
    $trip->bags            = $data['bags'] == '1' ? '1' : '0';
    $trip->save();

    $p = barcodeImage($trip->id);

    $u          = User::findOrFail($AuthUserID);
    $user_image = getFirstMedia($u, $u->avatarCollection)
        ? 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection)
        : null;

    $newTrip['id']               = $trip->id;
    $newTrip['code']             = $code;
    $newTrip['barcode']          = 'https://api.lady-driver.com' . getFirstMedia($trip, $trip->barcodeImageCollection);
    $newTrip['user_id']          = intval($AuthUserID);
    $newTrip['start_date']       = $start_date;
    $newTrip['end_date']         = null;
    $newTrip['start_time']       = $start_time;
    $newTrip['end_time']         = null;
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
    $newTrip['status']           = $TripStatus;
    $newTrip['payment_method']   = $data['payment_method'];
    $newTrip['air_conditioned']  = $trip->air_conditioned;
    $newTrip['animals']          = $trip->animals;
    $newTrip['bags']             = $trip->bags;
    $newTrip['seen_count']       = ['count' => 0, 'images' => []];

    $from->send(json_encode([
        'type'    => 'created_trip',
        'data'    => $newTrip,
        'message' => 'Trip Created Successfully',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

    $date_time = date('Y-m-d h:i:s a');
    echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $AuthUserID);

    $newTrip['client_stare_rate']         = 0;
    $newTrip['client_comment']            = null;
    $newTrip['cancelled_by_id']           = null;
    $newTrip['trip_cancelling_reason_id'] = null;
    $newTrip['driver_stare_rate']         = 0;
    $newTrip['student_trip']              = $student_trip;
    $newTrip['driver_comment']            = null;
    $newTrip['driver_arrived']            = null;
    $newTrip['payment_status']            = 'unpaid';
    $newTrip['current_offer']             = null;
    $newTrip['created_at']                = $trip->created_at;
    $newTrip['updated_at']                = $trip->updated_at;
    $newTrip['user']['id']                = intval($AuthUserID);
    $newTrip['user']['name']              = $u->name;
    $newTrip['user']['country_code']      = $u->country_code;
    $newTrip['user']['phone']             = $u->phone;
    $newTrip['user']['image']             = $user_image;
    $newTrip['user']['rate']              = Trip::where('user_id', $AuthUserID)
        ->where('status', 'completed')
        ->where('driver_stare_rate', '>', 0)
        ->avg('driver_stare_rate') ?? 5.00;

    TripDestination::create([
        'trip_id' => $trip->id,
        'lat'     => $data['end_lat_1'],
        'lng'     => $data['end_lng_1'],
        'address' => $data['address2'],
    ]);
    $newTrip['end_lat_1'] = $data['end_lat_1'];
    $newTrip['end_lng_1'] = $data['end_lng_1'];
    $newTrip['address2']  = $data['address2'];

    if (!empty($data['end_lat_2']) && !empty($data['end_lng_2'])) {
        TripDestination::create([
            'trip_id' => $trip->id,
            'lat'     => $data['end_lat_2'],
            'lng'     => $data['end_lng_2'],
            'address' => $data['address3'],
        ]);
        $newTrip['end_lat_2'] = $data['end_lat_2'];
        $newTrip['end_lng_2'] = $data['end_lng_2'];
        $newTrip['address3']  = $data['address3'];
    }

    if (!empty($data['end_lat_3']) && !empty($data['end_lng_3'])) {
        TripDestination::create([
            'trip_id' => $trip->id,
            'lat'     => $data['end_lat_3'],
            'lng'     => $data['end_lng_3'],
            'address' => $data['address4'],
        ]);
        $newTrip['end_lat_3'] = $data['end_lat_3'];
        $newTrip['end_lng_3'] = $data['end_lng_3'];
        $newTrip['address4']  = $data['address4'];
    }

    switch ($type) {
        case 'car':
            $application_commission = Setting::where('key', 'application_commission')->where('category', 'Car Trips')->where('type', 'boolean')->first()->value;
            $decimalPlaces          = 2;
            $eligibleCars           = Car::where('status', 'confirmed')
                ->where('is_comfort', '0')
                ->whereNotIn('id', busyCarIds())
                ->whereHas('owner', function ($query) {
                    $query->where('is_online', '1')->where('status', 'confirmed');
                })
                ->where(function ($query) use ($trip) {
                    if ($trip->air_conditioned == '1') $query->where('air_conditioned', '1');
                    if ($trip->animals == '1') $query->where('animals', '1');
                    if ($trip->user->gendor == 'Male') $query->where('passenger_type', 'male_female');
                })
                ->select('*')
                ->selectRaw(
                    "ROUND((6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))), ?) AS distance",
                    [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                )
                ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                ->get();
                $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


            $eligibleDriverIds = [];
            foreach ($eligibleCars as $car) {
                $eligibleDriverIds[] = $car->user_id;
                $response2           = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                $distance2           = round($response2['distance_in_km'], 1);
                $duration2           = intval($response2['duration_in_M']);
                $this->sendPushToUser($car->owner, [
                    'screen'   => 'new_trip',
                    'id'       => (string) $trip->id,
                    'title_en' => 'New Trip Near You',
                    'body_en'  => 'There is a trip ' . $distance2 . ' km away (' . $duration2 . ' min)',
                    'title_ar' => 'رحلة جديدة بالقرب منك',
                    'body_ar'  => 'يوجد رحلة على بُعد ' . $distance2 . ' كم (' . $duration2 . ' دقيقة)',
                ]);
            }

            foreach ($eligibleDriverIds as $eligibleDriverId) {
                $client = $this->getClientByUserId($eligibleDriverId);
                if ($client) {
                    DB::table('drivers_trips')->insert(['driver_id' => $eligibleDriverId, 'trip_id' => $trip->id]);
                    $driver                               = User::findOrFail($eligibleDriverId);
                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
                    $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                    $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                    $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                    $newTrip['client_location_distance']  = $response2['distance_in_km'];
                    $newTrip['client_location_duration']  = $response2['duration_in_M'];
                    $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $data2 = ['type' => 'new_trip', 'data' => $newTrip];
                    $client->send(json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ],New Trip sent to user %d' . "\n", $date_time, $eligibleDriverId);
                }
            }
            break;

        case 'comfort_car':
            $application_commission = Setting::where('key', 'application_commission')->where('category', 'Comfort Trips')->where('type', 'boolean')->first()->value;
            $decimalPlaces          = 2;
            $eligibleCars           = Car::where('status', 'confirmed')
                ->where('is_comfort', '1')
                ->whereNotIn('id', busyCarIds())
                ->whereHas('owner', function ($query) {
                    $query->where('is_online', '1')->where('status', 'confirmed');
                })
                ->where(function ($query) use ($trip) {
                    if ($trip->animals == '1') $query->where('animals', '1');
                    if ($trip->user->gendor == 'Male') $query->where('passenger_type', 'male_female');
                })
                ->select('*')
                ->selectRaw(
                    "ROUND((6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))), ?) AS distance",
                    [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                )
                ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                ->get();
                $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


            $eligibleDriverIds = [];
            foreach ($eligibleCars as $car) {
                $eligibleDriverIds[] = $car->user_id;
                $response2           = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                $distance2           = round($response2['distance_in_km'], 1);
                $duration2           = intval($response2['duration_in_M']);
                $this->sendPushToUser($car->owner, [
                    'screen'   => 'new_trip',
                    'id'       => (string) $trip->id,
                    'title_en' => 'New Trip Near You',
                    'body_en'  => 'There is a trip ' . $distance2 . ' km away (' . $duration2 . ' min)',
                    'title_ar' => 'رحلة جديدة بالقرب منك',
                    'body_ar'  => 'يوجد رحلة على بُعد ' . $distance2 . ' كم (' . $duration2 . ' دقيقة)',
                ]);
            }

            foreach ($eligibleDriverIds as $eligibleDriverId) {
                $client = $this->getClientByUserId($eligibleDriverId);
                if ($client) {
                    DB::table('drivers_trips')->insert(['driver_id' => $eligibleDriverId, 'trip_id' => $trip->id]);
                    $driver                               = User::findOrFail($eligibleDriverId);
                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
                    $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                    $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                    $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                    $newTrip['client_location_distance']  = $response2['distance_in_km'];
                    $newTrip['client_location_duration']  = $response2['duration_in_M'];
                    $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $data2 = ['type' => 'new_trip', 'data' => $newTrip];
                    $client->send(json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ],New Trip sent to user %d' . "\n", $date_time, $eligibleDriverId);
                }
            }
            break;

        case 'scooter':
            $application_commission = Setting::where('key', 'application_commission')->where('category', 'Scooter Trips')->where('type', 'boolean')->first()->value;
            $decimalPlaces          = 2;
            $eligibleScooters       = Scooter::where('status', 'confirmed')
            ->whereNotIn('id', busyScooterIds())   // لـ scooter
                ->whereHas('owner', function ($query) {
                    $query->where('is_online', '1')->where('status', 'confirmed');
                })
                ->select('*')
                ->selectRaw(
                    "ROUND((6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))), ?) AS distance",
                    [$trip->start_lat, $trip->start_lng, $trip->start_lat, $decimalPlaces]
                )
                ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                ->get();
                $eligibleScooters = $this->filterByRealDistance($eligibleScooters, $trip->start_lat, $trip->start_lng);

                echo "🛵 Eligible scooters count: " . $eligibleScooters->count() . "\n";
                $allScooters = Scooter::where('status', 'confirmed')
                    ->whereHas('owner', function ($query) {
                        $query->where('is_online', '1')->where('status', 'confirmed');
                    })->get();
                echo "🛵 All confirmed+online scooters: " . $allScooters->count() . "\n";
                foreach ($allScooters as $s) {
                    echo "🛵 Scooter ID: {$s->id}, lat: {$s->lat}, lng: {$s->lng}\n";
                }

            $eligibleDriverIds = [];
            foreach ($eligibleScooters as $scooter) {
                $eligibleDriverIds[] = $scooter->user_id;
                $response2           = calculate_distance($scooter->lat, $scooter->lng, $trip->start_lat, $trip->start_lng);
                $distance2           = round($response2['distance_in_km'], 1);
                $duration2           = intval($response2['duration_in_M']);
                $owner = $scooter->owner;
                $tokens = $this->getUserFcmTokens($owner);
                echo "🛵 Scooter owner ID: {$owner->id}, device_token: {$owner->device_token}, fcm_tokens: " . json_encode($tokens) . "\n";
                $this->sendPushToUser($scooter->owner, [
                    'screen'   => 'new_trip',
                    'id'       => (string) $trip->id,
                    'title_en' => 'New Trip Near You',
                    'body_en'  => 'There is a trip ' . $distance2 . ' km away (' . $duration2 . ' min)',
                    'title_ar' => 'رحلة جديدة بالقرب منك',
                    'body_ar'  => 'يوجد رحلة على بُعد ' . $distance2 . ' كم (' . $duration2 . ' دقيقة)',
                ]);
            }

            foreach ($eligibleDriverIds as $eligibleDriverId) {
                $client = $this->getClientByUserId($eligibleDriverId);
                if ($client) {
                    DB::table('drivers_trips')->insert(['driver_id' => $eligibleDriverId, 'trip_id' => $trip->id]);
                    $driver                               = User::findOrFail($eligibleDriverId);
                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
                    $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                    $scooter2                             = Scooter::where('user_id', $eligibleDriverId)->first();
                    $response2                            = calculate_distance($scooter2->lat, $scooter2->lng, $trip->start_lat, $trip->start_lng);
                    $newTrip['client_location_distance']  = $response2['distance_in_km'];
                    $newTrip['client_location_duration']  = $response2['duration_in_M'];
                    $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                    $data2 = ['type' => 'new_trip', 'data' => $newTrip];
                    $client->send(json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ],New Trip sent to user %d' . "\n", $date_time, $eligibleDriverId);
                }
            }
            break;
    }

    $this->startTripBroadcast($trip, $newTrip, $type);
}
    private function startTripBroadcast($trip, $newTrip, $type)
    {
                            // Broadcast trip every 5 seconds for max 3 minutes
        $maxDuration = 600; // seconds    //it was 15 min but its now 10 min
        $interval    = 5;   // seconds
        $startTime   = time();

        // Save a reference so we can cancel it later
        $timer = $this->loop->addPeriodicTimer($interval, function (TimerInterface $timer) use ($trip, $newTrip, $type, $startTime, $maxDuration) {
            // Stop broadcasting if too old or trip already taken
            $trip->refresh();
            if (in_array($trip->status, ['cancelled', 'accepted', 'completed', 'expired', 'pending'])) {
                echo "🛑 Trip {$trip->id} stopped broadcasting (status: {$trip->status})\n";
                $this->loop->cancelTimer($timer);
                return;
            }

            $realSeenCount = DB::table('drivers_trips')
              ->where('trip_id', $trip->id)
              ->count();
            //  $totalCount = min($realSeenCount * 2, 10);

            $totalCount = $realSeenCount * 2;
           $trip->seen_count = $totalCount;
           $trip->save();
            $newTrip_client                        = [];
            $newTrip_client['id']                  = $trip->id;
            $newTrip_client['seen_count']['count'] = $trip->seen_count;
            $limit                                 = min($trip->seen_count, 6); // max 6 images

            $files = collect(File::files(public_path('driver_images')))
                ->map(function ($file) {
                    return 'https://api.lady-driver.com' . '/driver_images/' . $file->getFilename(); // return public path format
                })
                ->shuffle()
                ->take($limit)
                ->values()
                ->toArray();

            $newTrip_client['seen_count']['images'] = $files;
            $user_id                                = $trip->user_id;
            $client_trip_                           = $this->getClientByUserId($user_id);
            if ($client_trip_) {
                $client_trip_->send(json_encode(['type' => 'created_trip', 'data' => $newTrip_client, 'message' => 'Trip Created Successfully'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |JSON_PRESERVE_ZERO_FRACTION));
            }
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $trip->user_id);
            $trip->refresh();
            if ($trip->status == 'pending') {
                echo "🛑 Trip {$trip->id} stopped broadcasting (status: {$trip->status})\n";
                $this->loop->cancelTimer($timer);
                return;
            } elseif ($trip->status == 'scheduled') {
                if ($trip->offers()->where('status', 'scheduled')->exists()) {
                    echo "🛑 Trip {$trip->id} stopped broadcasting (status: {$trip->status})\n";
                    $this->loop->cancelTimer($timer);
                    return;
                }
            }

            if (time() - $startTime > $maxDuration) {
                if ($trip->status == 'created') {
                    $trip->status = 'expired';
                    $trip->save();
                    $expired_trip['trip_id'] = $trip->id;
                    $data2                   = [
                        'type'    => 'expired_trip',
                        'data'    => $expired_trip,
                        'message' => 'Trip expired successfully',
                    ];
                    $message = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
                    $from    = $this->getClientByUserId($trip->user_id);

                    if ($from) {
                        $from->send($message);
                        $date_time = date('Y-m-d h:i:s a');

                        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);

                    }
                    $this->expire_trip_notify($trip);
                } elseif ($trip->status == 'scheduled') {
                    if (! $trip->offers()->where('status', 'scheduled')->exists()) {
                        $trip->status = 'expired';
                        $trip->save();
                        $expired_trip['trip_id'] = $trip->id;
                        $data2                   = [
                            'type'    => 'expired_trip',
                            'data'    => $expired_trip,
                            'message' => 'Trip expired successfully',
                        ];
                        $message = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
                        $from    = $this->getClientByUserId($trip->user_id);
                        if ($from) {
                            $from->send($message);
                            $date_time = date('Y-m-d h:i:s a');

                            echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);

                        }
                        $this->expire_trip_notify($trip);
                    }
                }
                echo "🕓 Trip {$trip->id} broadcast expired after 15 minutes\n";
                $this->loop->cancelTimer($timer);
                return;
            }

// Always sync from DB — catches update_trip_price changes
$newTrip['total_price']      = (float) $trip->total_price;
$newTrip['remaining_amount'] = (float) $trip->total_price;
$newTrip['discount']         = (float) $trip->discount;


            // Re-run driver search (same logic per type)
            switch ($type) {
                case 'car':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Car Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '0')
                    ->whereNotIn('id', busyCarIds())

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                        $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


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
                            $exists = DB::table('drivers_trips')
                                ->where('driver_id', $eligibleDriverId)
                                ->where('trip_id', $trip->id)
                                ->exists();
                            if (! $exists && $client) {
                                DB::table('drivers_trips')->insert([
                                    'driver_id' => $eligibleDriverId,
                                    'trip_id'   => $trip->id,
                                ]);

                                // 🔔 send push to newly eligible driver
                                $car_push = Car::where('user_id', $eligibleDriverId)->first();
                                if ($car_push) {
                                    $response_push = calculate_distance($car_push->lat, $car_push->lng, $trip->start_lat, $trip->start_lng);
                                    $this->sendPushToUser($car_push->owner, [
                                        'screen'   => 'new_trip',
                                        'id'       => (string) $trip->id,
                                        'title_en' => 'New Trip Near You',
                                        'body_en'  => 'There is a trip ' . round($response_push['distance_in_km'], 1) . ' km away (' . intval($response_push['duration_in_M']) . ' min)',
                                        'title_ar' => 'رحلة جديدة بالقرب منك',
                                        'body_ar'  => 'يوجد رحلة على بُعد ' . round($response_push['distance_in_km'], 1) . ' كم (' . intval($response_push['duration_in_M']) . ' دقيقة)',
                                    ]);
                                }

                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($trip->total_price + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00;
                                $newTrip['driver_rate']               = $trip->total_price - $newTrip['app_rate'];
                                $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                $trip->refresh();
                              if (in_array($trip->status, ['expired', 'cancelled', 'accepted', 'pending', 'completed'])) {
                               $this->loop->cancelTimer($timer);
                                 return;
                                }
                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'comfort_car':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Comfort Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '1')
                    ->whereNotIn('id', busyCarIds())

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                        $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


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
                            $exists = DB::table('drivers_trips')
                                ->where('driver_id', $eligibleDriverId)
                                ->where('trip_id', $trip->id)
                                ->exists();
                            if (! $exists && $client) {
                                DB::table('drivers_trips')->insert([
                                    'driver_id' => $eligibleDriverId,
                                    'trip_id'   => $trip->id,
                                ]);

                                // 🔔 send push to newly eligible driver
                                $car_push = Car::where('user_id', $eligibleDriverId)->first();
                                if ($car_push) {
                                    $response_push = calculate_distance($car_push->lat, $car_push->lng, $trip->start_lat, $trip->start_lng);
                                    $this->sendPushToUser($car_push->owner, [
                                        'screen'   => 'new_trip',
                                        'id'       => (string) $trip->id,
                                        'title_en' => 'New Trip Near You',
                                        'body_en'  => 'There is a trip ' . round($response_push['distance_in_km'], 1) . ' km away (' . intval($response_push['duration_in_M']) . ' min)',
                                        'title_ar' => 'رحلة جديدة بالقرب منك',
                                        'body_ar'  => 'يوجد رحلة على بُعد ' . round($response_push['distance_in_km'], 1) . ' كم (' . intval($response_push['duration_in_M']) . ' دقيقة)',
                                    ]);
                                }

                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($trip->total_price + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00;
                                $newTrip['driver_rate']               = $trip->total_price - $newTrip['app_rate'];
                                $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($car2->lat, $car2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                $trip->refresh();
                             if (in_array($trip->status, ['expired', 'cancelled', 'accepted', 'pending', 'completed'])) {
                             $this->loop->cancelTimer($timer);
                              return;
                               }
                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'scooter':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Scooter Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleScooters       = Scooter::where('status', 'confirmed')
                    ->whereNotIn('id', busyScooterIds())

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                        $eligibleScooters = $this->filterByRealDistance($eligibleScooters, $trip->start_lat, $trip->start_lng);


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
                            $exists = DB::table('drivers_trips')
                                ->where('driver_id', $eligibleDriverId)
                                ->where('trip_id', $trip->id)
                                ->exists();
                            if (! $exists && $client) {
                                DB::table('drivers_trips')->insert([
                                    'driver_id' => $eligibleDriverId,
                                    'trip_id'   => $trip->id,
                                ]);

                                // 🔔 send push to newly eligible driver
                               // 🔔 send push to newly eligible driver
                               $scooter_push = Scooter::where('user_id', $eligibleDriverId)->first();
                               if ($scooter_push) {
                                   $response_push = calculate_distance($scooter_push->lat, $scooter_push->lng, $trip->start_lat, $trip->start_lng);
                                   $this->sendPushToUser($scooter_push->owner, [
                                       'screen'   => 'new_trip',
                                       'id'       => (string) $trip->id,
                                       'title_en' => 'New Trip Near You',
                                       'body_en'  => 'There is a trip ' . round($response_push['distance_in_km'], 1) . ' km away (' . intval($response_push['duration_in_M']) . ' min)',
                                       'title_ar' => 'رحلة جديدة بالقرب منك',
                                       'body_ar'  => 'يوجد رحلة على بُعد ' . round($response_push['distance_in_km'], 1) . ' كم (' . intval($response_push['duration_in_M']) . ' دقيقة)',
                                   ]);
                               }

                                $driver                               = User::findOrFail($eligibleDriverId);
                                $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($trip->total_price + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00;
                                $newTrip['driver_rate']               = $trip->total_price - $newTrip['app_rate'];
                                $scooter2                             = Scooter::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($scooter2->lat, $scooter2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                $trip->refresh();
                                if (in_array($trip->status, ['expired', 'cancelled', 'accepted', 'pending', 'completed'])) {
                                    $this->loop->cancelTimer($timer);
                                    return;
                                }
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
        });
    }

    private function expire_trip_notify($trip)
    {
        $expired_trip['trip_id'] = $trip->id;
        $data2                   = [
            'type'    => 'expired_trip',
            'data'    => $expired_trip,
            'message' => 'Trip expired successfully',
        ];
        $message = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
        $records = DB::table('drivers_trips')->where('trip_id', $trip->id)->get();
        foreach ($records as $record) {
            $client = $this->getClientByUserId($record->driver_id);
            if ($client) {
                $client->send($message);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $record->driver_id);
            }
        }
        DB::table('drivers_trips')->where('trip_id', $trip->id)->delete();
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
        $message = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
        $from->send($message);
        $date_time = date('Y-m-d h:i:s a');

        echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $AuthUserID);
        $records = DB::table('drivers_trips')->where('trip_id', $trip->id)->get();
        foreach ($records as $record) {
            $client = $this->getClientByUserId($record->driver_id);
            if ($client) {
                $client->send($message);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of expired trip "%s" sent to user %d' . "\n", $date_time, $message, $record->driver_id);
            }
        }
        DB::table('drivers_trips')->where('trip_id', $trip->id)->delete();

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
        $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        $from->send($res);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ],created offer message has been sent to user %d' . "\n", $date_time, $AuthUserID);

        $distance                                 = $response['distance_in_km'];
        $duration                                 = $response['duration_in_M'];
        $driver_                                  = $offer->user()->first();
        $offer_result['id']                       = $offer->id;
        $offer_result['user_id']                  = $driver_->id;
        $offer_result['trip_id']                  = $trip->id;
        $offer_result['client_location_distance'] = $distance;
        $offer_result['client_location_duration'] = $duration;
        $offer_result['offer']                    = floatval($data['offer']);
        $offer_result['user']['id']               = $offer->user()->first()->id;
        $offer_result['user']['name']             = $offer->user()->first()->name;
        $offer_result['user']['image']            = getFirstMedia($offer->user()->first(), $offer->user()->first()->avatarCollection) ? 'https://api.lady-driver.com' . getFirstMedia($offer->user()->first(), $offer->user()->first()->avatarCollection) : null;
        if ($trip->type == 'comfort_car' || $trip->type == 'car') {
            $offer_result['user']['rate'] = Trip::whereHas('car', function ($query) use ($driver_) {
                $query->where('user_id', $driver_->id);
            })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
            $offer_result['user']['trips_count'] = Trip::whereHas('car', function ($query) use ($driver_) {
                $query->where('user_id', $driver_->id);
            })->where('status', 'completed')->count();
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
            })->where('status', 'completed')->where('client_stare_rate', '>', 0)->avg('client_stare_rate') ?? 5.00;
            $offer_result['user']['trips_count'] = Trip::whereHas('scooter', function ($query) use ($driver_) {
                $query->where('user_id', $driver_->id);
            })->where('status', 'completed')->count();
            $offer_result['scooter']['id']               = $offer->scooter()->first()->id;
            $offer_result['scooter']['image']            = 'https://api.lady-driver.com' . getFirstMedia($offer->scooter()->first(), $offer->scooter()->first()->avatarCollection);
            $offer_result['scooter']['year']             = $offer->scooter()->first()->year;
            $offer_result['scooter']['scooter_mark_id']  = $offer->scooter()->first()->motorcycle_mark_id;
            $offer_result['scooter']['scooter_model_id'] = $offer->scooter()->first()->motorcycle_model_id;
            $offer_result['scooter']['mark']['id']       = $offer->scooter()->first()->motorcycleMark()->first()->id;
            $offer_result['scooter']['mark']['name']     = $offer->scooter()->first()->motorcycleMark()->first()->name;
            $offer_result['scooter']['model']['id']      = $offer->scooter()->first()->motorcycleModel()->first()->id;
            $offer_result['scooter']['model']['name']    = $offer->scooter()->first()->motorcycleModel()->first()->name;
        }
        $offer_result['created_at'] = $offer->created_at;

        $client = $this->getClientByUserId($trip->user_id);
        if ($client) {
            $data2 = [
                'type' => 'new_offer',
                'data' => $offer_result,
            ];
            $message = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
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
        $canceled_offer['trip_id']  = $offer->trip_id;
        $data2                      = [
            'type'    => 'canceled_offer',
            'data'    => $canceled_offer,
            'message' => 'Offer canceled successfully',
        ];
        $message   = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        $date_time = date('Y-m-d h:i:s a');

        $from->send($message);
        echo sprintf('[ %s ] canceled_offer sent to canceller %d' . "\n", $date_time, $AuthUserID);

        if ($offer->user_id != $AuthUserID) {
            $driver = $this->getClientByUserId($offer->user_id);
            if ($driver) {
                $driver->send($message);
                echo sprintf('[ %s ] canceled_offer sent to driver %d' . "\n", $date_time, $offer->user_id);
            }
        }

        if ($offer->trip->user_id != $AuthUserID) {
            $client = $this->getClientByUserId($offer->trip->user_id);
            if ($client) {
                $client->send($message);
                echo sprintf('[ %s ] canceled_offer sent to trip user %d' . "\n", $date_time, $offer->trip->user_id);
            }
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
                'message' => 'The selected offer is expired.',
            ];
            $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $from->send($res);
            $date_time = date('Y-m-d h:i:s a');

            echo sprintf('[ %s ] Message of expired offer "%s" sent to user %d' . "\n", $date_time, $res, $AuthUserID);

        } else {
            $trip = $offer->trip;
            switch ($trip->type) {
                case 'car':
                    $app_ratio              = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Car Trips')->where('type', 'boolean')->first()->value;
                    $trip->car_id           = $offer->car_id;
                    break;

                case 'comfort_car':
                    $app_ratio              = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Comfort Trips')->where('type', 'boolean')->first()->value;
                    $trip->car_id           = $offer->car_id;
                    break;

                case 'scooter':
                    $app_ratio              = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $offer->user->level)->first()->value);
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Scooter Trips')->where('type', 'boolean')->first()->value;
                    $trip->scooter_id       = $offer->scooter_id;
                    break;

                default:

                    break;
            }
            if ($trip->status == 'created') {
                $trip->status  = 'pending';
                $offer->status = 'accepted';
            } elseif ($trip->status == 'scheduled') {
                $offer->status = 'scheduled';
            }
            $trip->total_price = $offer->offer;
            $trip->app_rate    = $application_commission == 'On' ? round(((($offer->offer + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00;
            $trip->driver_rate = $trip->total_price - ($application_commission == 'On' ? round(((($offer->offer + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00);

            $trip->save();
            $offer->save();
            if ($trip->status === 'pending') {
                $this->startPendingWatchdog($trip);
            }
            Offer::where('id', '!=', $data['offer_id'])->where('trip_id', $trip->id)->update(['status' => 'expired']);
            $otherOffers = Offer::where('id', '!=', $data['offer_id'])->where('trip_id', $trip->id)->get();
            foreach ($otherOffers as $exp_offer) {
                $client = $this->getClientByUserId($exp_offer->user_id);
                if ($client) {
                    $x['offer_id'] = $offer->id;
                    $x['trip_id']  = $trip->id;
                    $data2         = [
                        'type'    => 'cancelled_offer',
                        'data'    => $x,
                        'message' => 'Sorry, the customer has chosen another offer.Have a pleasant trip.',
                    ];
                    $res = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                    $client->send($res);
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ] Message of expire offer "%s" sent to user %d' . "\n", $date_time, $res, $exp_offer->user_id);
                }
            }
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
            $x['trip']  = $trip;

            $data1 = [
                'type'    => 'accepted_offer',
                'data'    => $x,
                'message' => 'Offer accepted Successfully',
            ];
            $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $from->send($res);
            $date_time = date('Y-m-d h:i:s a');
            echo sprintf('[ %s ] Message of accept offer "%s" sent to user %d' . "\n", $date_time, $res, $AuthUserID);

            $client = $this->getClientByUserId($offer->user_id);
            if ($client) {
                $client->send($res);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of accept offer "%s" sent to user %d' . "\n", $date_time, $res, $offer->user_id);
            }
$seenDrivers = DB::table('drivers_trips')
->where('trip_id', $trip->id)
->get();

foreach ($seenDrivers as $record) {
// skip the accepted one driver
if ($record->driver_id == $offer->user_id) continue;

$client = $this->getClientByUserId($record->driver_id);
if ($client) {
    $x['offer_id'] = $offer->id;
    $x['trip_id']  = $trip->id;
    $data2 = [
        'type'    => 'trip_taken',
        'data'    => $x,
        'message' => 'Sorry, the customer has chosen another driver.',
    ];
    $client->send(json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $date_time = date('Y-m-d h:i:s a');
    echo sprintf('[ %s ] canceled_offer sent to driver %d' . "\n", $date_time, $record->driver_id);
}
}
            DB::table('drivers_trips')->where('trip_id', $trip->id)->delete();

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
            if ($trip->driver_arrived) {
                $arrivedAt = Carbon::parse($trip->driver_arrived);
                $now       = now();

                $minutesDelay = $arrivedAt->diffInMinutes($now);

                if ($minutesDelay > 5) {
                    $chargeableMinutes = $minutesDelay - 5;
                    $delay_cost        = Setting::where('key', 'delay_cost')
                                                ->where('category', 'Trips')
                                                ->where('type', 'number')
                                                ->first()->value;
                    $trip->delay_cost  = $chargeableMinutes * floatval($delay_cost);
                    $trip->total_price = $trip->total_price + ($chargeableMinutes * floatval($delay_cost));
                } else {
                    $trip->delay_cost = 0;
                }
            } else {
                $trip->delay_cost = 0;
            }
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
        $data1            = [
            'type'    => $type,
            'data'    => $trip,
            'message' => $message,
        ];

        $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

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
    private function cancel_trip(ConnectionInterface $from, $AuthUserID, $cancelTripRequest)
    {
        $data       = json_decode($cancelTripRequest, true);
        $trip       = Trip::findOrFail($data['trip_id']);

$reason = null;
$cancelling_cost = 0;
        $sss_status = $trip->status;
        if ($trip->status == 'pending') {
            $sss_status = 'created';
        } elseif ($trip->status == 'scheduled') {
            $sss_status = 'scheduled';
        }
        $create_new_trip = false;
        if ($trip->status == 'pending' && $trip->status == 'scheduled') {
            $create_new_trip = true;
        }
        $trip->status                      = 'cancelled';
        $trip->cancelled_by_id             = $AuthUserID;
        $trip->trip_cancelling_reason_id   = $data['reason_id'];
        $trip->trip_cancelling_reason_text = $data['reason_text'];
        $trip->save();
        $canceled_trip['trip_id'] = $trip->id;
        $canceled_trip['user_id'] = $AuthUserID;
        $data2                    = [
            'type' => 'canceled_trip',
            'data' => $canceled_trip,
            // 'message'=>'Trip canceled successfully'
        ];
        if ($data['reason_id']) {
            $reason = TripCancellingReason::find($trip->trip_cancelling_reason_id);
            if (!$reason) {
                echo "⚠️ TripCancellingReason ID {$trip->trip_cancelling_reason_id} not found — falling back to default cancelling cost.\n";
                $arr = [
                    'car'         => 'Car Trips',
                    'comfort_car' => 'Comfort Trips',
                    'scooter'     => 'Scooter Trips',
                ];
                $cancelling_cost = floatval(Setting::where('key', 'cancelling_cost')->where('category', $arr[$trip->type])->where('type', 'number')->first()->value);
            }
        } else {
            $arr = [
                'car'         => 'Car Trips',
                'comfort_car' => 'Comfort Trips',
                'scooter'     => 'Scooter Trips',
            ];
            $cancelling_cost = floatval(Setting::where('key', 'cancelling_cost')->where('category', $arr[$trip->type])->where('type', 'number')->first()->value);
        }
        if ($trip->user_id == $AuthUserID) {
            $data2['message'] = 'The trip canceled successfully.';
            $message          = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $client           = $this->getClientByUserId($trip->user_id);
            if ($client) {
                $client->send($message);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of canceled trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);
            }
            if ($reason != null) {
                if ($reason->value_type == 'ratio') {
                    $value = round(($reason->value * $trip->total_price) / 100);
                } else {
                    $value = $reason->value;
                }
            } else {
                $value = $cancelling_cost;
            }

            $trip->user->wallet = $trip->user->wallet - $value;
            $data2['message']   = 'The trip was cancelled by client.';
            $message            = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

$driver = null;
if ($trip->car_id != null && $trip->car) {
    $driver = $this->getClientByUserId($trip->car->user_id);
} elseif ($trip->scooter_id != null && $trip->scooter) {
    $driver = $this->getClientByUserId($trip->scooter->user_id);
}
if ($driver) {
    $driver->send($message);
}

$seenDrivers = DB::table('drivers_trips')
    ->where('trip_id', $trip->id)
    ->select('driver_id')
    ->get();

foreach ($seenDrivers as $seen) {
    $client = $this->getClientByUserId($seen->driver_id);
    if ($client) {
        $client->send($message);
        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ] canceled_trip sent to seen driver %d' . "\n", $date_time, $seen->driver_id);
    }
}
        } else {
            $data2['message'] = 'The trip was cancelled by the captain. Another one is being assigned.';
            $message          = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $client           = $this->getClientByUserId($trip->user_id);
            if ($client) {
                $client->send($message);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of canceled trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->user_id);
            }
            $data2['message'] = 'The trip was cancelled by you.';
            $message          = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $driver = null;
if ($trip->car_id != null && $trip->car) {
    $driver = $this->getClientByUserId($trip->car->user_id);
} elseif ($trip->scooter_id != null && $trip->scooter) {
    $driver = $this->getClientByUserId($trip->scooter->user_id);
}
            if ($driver) {
                $driver->send($message);
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf('[ %s ] Message of canceled trip "%s" sent to user %d' . "\n", $date_time, $message, $trip->car->user_id);
            }
            if ($reason != null) {
                if ($reason->value_type == 'ratio') {
                    $value = round(($reason->value * $trip->total_price) / 100);
                } else {
                    $value = $reason->value;
                }
            } else {
                $value = $cancelling_cost;
            }
            if ($trip->car) {
                $owner = $trip->car->owner;
            } elseif ($trip->scooter) {
                $owner = $trip->scooter->owner;
            }

            $owner->wallet -= $value;
            $owner->save();
            if ($create_new_trip == true) {
                $type = $trip->type;
                if ($type == 'car' || $type == 'comfort_car') {
                    $newTrip["car_id"] = null;
                } elseif ($type == 'scooter') {
                    $newTrip["scooter_id"] = null;
                }
                $distance   = (float) $trip->distance;
                $duration   = (int) $trip->duration;
                $total_cost = (float) $trip->total_price;
                $discount   = (float) $trip->discount;
                $lastTrip   = Trip::orderBy('id', 'desc')->first();

                if ($lastTrip) {
                    $lastCode = $lastTrip->code;
                    $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 12, '0', STR_PAD_LEFT);
                } else {
                    $code = 'TRP-000000000001';
                }
                do {
                    $barcode = Str::uuid();
                } while (Trip::where('barcode', $barcode)->exists());
                $n_trip = Trip::create(['user_id' => $trip->user_id,
                    'code'                            => $code,
                    'barcode'                         => $barcode,
                    'start_lat'                       => $trip->start_lat,
                    'start_lng'                       => $trip->start_lng,
                    'address1'                        => $trip->address1,
                    'total_price'                     => $total_cost,
                    'distance'                        => $distance,
                    'duration'                        => $duration,
                    'type'                            => $type,
                    'start_date'                      => $trip->start_date,
                    'start_time'                      => $trip->start_time,
                    'scheduled'                       => $trip->scheduled,
                    'status'                          => $sss_status,
                    'payment_method'                  => $trip->payment_method,
                    'remaining_amount'                => $total_cost,
                    'discount'                        => $discount,
                    'student_trip'                    => $trip->student_trip,
                    'air_conditioned'                 => $trip->air_conditioned,
                    'animals'                         => $trip->animals,
                    'bags'                            => $trip->bags,
                ]);
                $p = barcodeImage($n_trip->id);

                $u                               = User::findOrFail($n_trip->user_id);
                $user_image                      = getFirstMedia($u, $u->avatarCollection) ? 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection) : null;
                $newTrip['id']                   = $n_trip->id;
                $newTrip['code']                 = $code;
                $newTrip['barcode']              = 'https://api.lady-driver.com' . getFirstMedia($n_trip, $n_trip->barcodeImageCollection);
                $newTrip['user_id']              = intval($n_trip->user_id);
                $newTrip["start_date"]           = $n_trip->start_date;
                $newTrip["end_date"]             = null;
                $newTrip["start_time"]           = $n_trip->start_time;
                $newTrip["end_time"]             = null;
                $newTrip['start_lat']            = $n_trip->start_lat;
                $newTrip['start_lng']            = $n_trip->start_lng;
                $newTrip['address1']             = $n_trip->address1;
                $newTrip['total_price']          = $total_cost;
                $newTrip['app_rate']             = 0.00;
                $newTrip['driver_rate']          = 0.00;
                $newTrip['discount']             = $discount;
                $newTrip['paid_amount']          = 0.00;
                $newTrip['remaining_amount']     = $total_cost;
                $newTrip['distance']             = $distance;
                $newTrip['duration']             = $duration;
                $newTrip['scheduled']            = $n_trip->scheduled;
                $newTrip['type']                 = $type;
                $newTrip["status"]               = $n_trip->status;
                $newTrip['payment_method']       = $n_trip->payment_method;
                $newTrip['air_conditioned']      = $trip->air_conditioned;
                $newTrip['animals']              = $trip->animals;
                $newTrip['bags']                 = $trip->bags;
                $newTrip['seen_count']['count']  = 0;
                $newTrip['seen_count']['images'] = [];
                $TripDestinations                = TripDestination::where('trip_id', $trip->id)->orderBy('id', 'asc')->get();
                $xxx                             = 1;
                foreach ($TripDestinations as $TripDestination) {
                    TripDestination::create(['trip_id' => $n_trip->id, 'lat' => $TripDestination->lat, 'lng' => $TripDestination->lng, 'address' => $TripDestination->address]);
                    $newTrip['end_lat_' . $xxx]    = $TripDestination->lat;
                    $newTrip['end_lng_' . $xxx]    = $TripDestination->lng;
                    $newTrip['address' . $xxx + 1] = $TripDestination->address;
                    $xxx++;
                }
                $client = $this->getClientByUserId($trip->user_id);
                if ($client) {
                    $client->send(json_encode(['type' => 'created_trip', 'data' => $newTrip, 'message' => 'Trip Created Successfully'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION));
                    $date_time = date('Y-m-d h:i:s a');
                    echo sprintf('[ %s ],created trip message has been sent to user %d' . "\n", $date_time, $trip->user_id);
                }
                $newTrip["client_stare_rate"]         = 0;
                $newTrip["client_comment"]            = null;
                $newTrip["cancelled_by_id"]           = null;
                $newTrip["trip_cancelling_reason_id"] = null;
                $newTrip["driver_stare_rate"]         = 0;
                $newTrip["student_trip"]              = $n_trip->student_trip;
                $newTrip["driver_comment"]            = null;
                $newTrip["driver_arrived"]            = null;
                $newTrip["payment_status"]            = "unpaid";
                $newTrip["current_offer"]             = null;
                $newTrip['created_at']                = $n_trip->created_at;
                $newTrip['updated_at']                = $n_trip->updated_at;
                $newTrip['user']['id']                = intval($u->id);
                $newTrip['user']['name']              = $u->name;
                $newTrip['user']['image']             = $user_image;
                $newTrip['user']['rate']              = Trip::where('user_id', $AuthUserID)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate') ?? 5.00;
                switch ($type) {
                    case 'car':
                        $application_commission = Setting::where('key', 'application_commission')->where('category', 'Car Trips')->where('type', 'boolean')->first()->value;
                        $decimalPlaces          = 2;
                        $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '0')
                        ->whereNotIn('id', busyCarIds())

                            ->whereHas('owner', function ($query) {
                                $query->where('is_online', '1')
                                    ->where('status', 'confirmed');
                            })
                            ->where(function ($query) use ($n_trip) {
                                if ($n_trip->air_conditioned == '1') {
                                    $query->where('air_conditioned', '1');
                                }
                                if ($n_trip->animals == '1') {
                                    $query->where('animals', '1');
                                }
                                if ($n_trip->user->gendor == 'Male') {
                                    $query->where('passenger_type', 'male_female');
                                }
                            })
                            ->select('*')
                            ->selectRaw(
                                "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                                [$n_trip->start_lat, $n_trip->start_lng, $n_trip->start_lat, $decimalPlaces]
                            )
                            ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                            ->get();
                            $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


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
                                    DB::table('drivers_trips')->insert([
                                        'driver_id' => $eligibleDriverId,
                                        'trip_id'   => $n_trip->id,
                                    ]);
                                    $driver                               = User::findOrFail($eligibleDriverId);
                                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
                                    $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                    $car2                                 = Car::where('user_id', $eligibleDriverId)->first();
                                    $response2                            = calculate_distance($car2->lat, $car2->lng, $n_trip->start_lat, $n_trip->start_lng);
                                    $distance2                            = $response2['distance_in_km'];
                                    $duration2                            = $response2['duration_in_M'];
                                    $newTrip['client_location_distance']  = $distance2;
                                    $newTrip['client_location_duration']  = $duration2;
                                    $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Car Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                    $data2['type'] = 'new_trip';
                                    $data2['data'] = $newTrip;
                                    $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                    $client->send($message);
                                    $date_time = date('Y-m-d h:i:s a');
                                    echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                                }
                            }
                        }
                        break;

                    case 'comfort_car':
                        $application_commission = Setting::where('key', 'application_commission')->where('category', 'Comfort Trips')->where('type', 'boolean')->first()->value;
                        $decimalPlaces          = 2;
                        $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '1')
                        ->whereNotIn('id', busyCarIds())

                            ->whereHas('owner', function ($query) {
                                $query->where('is_online', '1')
                                    ->where('status', 'confirmed');
                            })
                            ->where(function ($query) use ($n_trip) {

                                if ($n_trip->animals == '1') {
                                    $query->where('animals', '1');
                                }
                                if ($n_trip->user->gendor == 'Male') {
                                    $query->where('passenger_type', 'male_female');
                                }
                            })
                            ->select('*')
                            ->selectRaw(
                                "
                    ROUND(
                        ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ), ?
                    ) AS distance",
                                [$n_trip->start_lat, $n_trip->start_lng, $n_trip->start_lat, $decimalPlaces]
                            )
                            ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                            ->get();
                            $eligibleCars = $this->filterByRealDistance($eligibleCars, $trip->start_lat, $trip->start_lng);


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
                                    DB::table('drivers_trips')->insert([
                                        'driver_id' => $eligibleDriverId,
                                        'trip_id'   => $n_trip->id,
                                    ]);
                                    $driver                               = User::findOrFail($eligibleDriverId);
                                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Comfort Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
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
                                    $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                    $client->send($message);
                                    $date_time = date('Y-m-d h:i:s a');
                                    echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                                }
                            }
                        }
                        break;

                    case 'scooter':
                        $application_commission = Setting::where('key', 'application_commission')->where('category', 'Scooter Trips')->where('type', 'boolean')->first()->value;
                        $decimalPlaces          = 2;
                        $eligibleScooters       = Scooter::where('status', 'confirmed')
                        ->whereNotIn('id', busyScooterIds())

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
                                [$n_trip->start_lat, $n_trip->start_lng, $n_trip->start_lat, $decimalPlaces]
                            )
                            ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                            ->get();
                            $eligibleScooters = $this->filterByRealDistance($eligibleScooters, $trip->start_lat, $trip->start_lng);


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
                                    DB::table('drivers_trips')->insert([
                                        'driver_id' => $eligibleDriverId,
                                        'trip_id'   => $n_trip->id,
                                    ]);
                                    $driver                               = User::findOrFail($eligibleDriverId);
                                    $app_ratio                            = floatval(Setting::where('key', 'app_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);
                                    $newTrip['app_rate']                  = $application_commission == 'On' ? round(((($total_cost + $discount) * $app_ratio) / 100) - $discount, 2) : 0.00;
                                    $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                    $scooter2                             = Scooter::where('user_id', $eligibleDriverId)->first();
                                    $response2                            = calculate_distance($scooter2->lat, $scooter2->lng, $trip->start_lat, $trip->start_lng);
                                    $distance2                            = $response2['distance_in_km'];
                                    $duration2                            = $response2['duration_in_M'];
                                    $newTrip['client_location_distance']  = $distance2;
                                    $newTrip['client_location_duration']  = $duration2;
                                    $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                    $data2['type'] = 'new_trip';
                                    $data2['data'] = $newTrip;
                                    $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

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
                $this->startTripBroadcast($n_trip, $newTrip, $type);
            }

        }

    }

    private function update_location(ConnectionInterface $from, $AuthUserID, $trackCarRequest)
{
    $data = json_decode($trackCarRequest, true);

    if (!$data) {
        return;
    }

    $lat = (float) ($data['lat'] ?? 0);
    $lng = (float) ($data['lng'] ?? 0);

    $heading = isset($data['heading']) ? (float) $data['heading'] : 0;
    $speed   = isset($data['speed']) ? (float) $data['speed'] : 0;

    $driver = User::with(['car', 'scooter'])->find($AuthUserID);

    if (!$driver) return;

    $driver->update([
        'lat' => $lat,
        'lng' => $lng,
        'heading' => $heading,
        'speed' => $speed,
    ]);

    $trip = null;

    // ================= TRIP =================
    if ($driver->car) {
        $driver->car->update(compact('lat','lng','heading','speed'));

        $trip = Trip::where('car_id', $driver->car->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();
    }

    elseif ($driver->scooter) {
        $driver->scooter->update(compact('lat','lng','heading','speed'));

        $trip = Trip::where('scooter_id', $driver->scooter->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();
    }

    // ================= ROUTE (client-supplied, free — no Google cost) =================
    $routeKey = $trip ? "trip_{$trip->id}" : "driver_{$AuthUserID}";

    if (isset($data['route']) && is_array($data['route'])) {
        $this->routeCache[$routeKey] = [
            'polyline'   => $data['route']['polyline']   ?? null,
            'version'    => $data['route']['version']    ?? null,
            'kind'       => $data['route']['kind']       ?? null,
            'distance_m' => $data['route']['distance_m'] ?? null,
            'duration_s' => $data['route']['duration_s'] ?? null,
        ];
        echo "🛣️ Route updated for {$routeKey} (v{$this->routeCache[$routeKey]['version']}, {$this->routeCache[$routeKey]['kind']})\n";
    }

    $route = $this->routeCache[$routeKey] ?? null;

    // ================= GOOGLE API FALLBACK (cost-efficient, throttled) =================
    // Only call TripTrackingService (which hits Google) if:
    // 1) We have a trip AND
    // 2) The client did NOT send its own route data AND
    // 3) We haven't called Google for this trip in the last N seconds
    $result = null;
    if ($trip && !$route) {
        $lastCallKey = "last_google_call_{$routeKey}";
        $now         = time();
        $lastCall    = $this->routeCache[$lastCallKey] ?? 0;
        $throttleSeconds = 20; // adjust based on how "live" you need it — bigger = cheaper

        if (($now - $lastCall) >= $throttleSeconds) {
            $tracker = app(\App\Services\TripTrackingService::class);
            $result  = $tracker->calculate($lat, $lng, $trip);
            $this->routeCache[$lastCallKey] = $now;
            echo "💰 Google API called for {$routeKey} (throttled every {$throttleSeconds}s)\n";
        }
    }

    // ================= SAFE PAYLOAD =================
    $payload = [
        'type' => 'track_car',
        'data' => [
            'lat'     => $lat,
            'lng'     => $lng,
            'heading' => $heading,
            'speed'   => $speed,

            'eta'      => $result['eta'] ?? null,
            'distance' => $route['distance_m'] ?? ($result['distance'] ?? null),
            'duration' => $route['duration_s'] ?? ($result['duration'] ?? null),
            'status'   => $result['status'] ?? 'on_the_way',

            'message_en' => $result['message']['en'] ?? null,
            'message_ar' => $result['message']['ar'] ?? null,

            'route_polyline' => $route['polyline'] ?? null,
            'route_version'  => $route['version']  ?? null,
            'route_kind'     => $route['kind']     ?? null,
        ],
    ];

    $res = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // ================= SEND =================
    if ($trip) {
        if ($client = $this->getClientByUserId($trip->user_id)) {
            $client->send($res);
        }
    }

    if ($driverClient = $this->getClientByUserId($AuthUserID)) {
        $driverClient->send($res);
    }

    $from->send($res);

    // Clean up cached route + throttle timestamp once the trip is over
    if ($trip && in_array($trip->status, ['completed', 'cancelled', 'expired'])) {
        unset($this->routeCache[$routeKey]);
        unset($this->routeCache["last_google_call_{$routeKey}"]);
    }
}
    public function check_barcode(ConnectionInterface $from, $AuthUserID, $checkBarcodeRequest)
    {
        $data = json_decode($checkBarcodeRequest, true);
        $trip = Trip::where('id', $data['trip_id'])->first();
        if ($trip->barcode == $data['barcode']) {
            $x['trip_id'] = $data['trip_id'];
            $x['message'] = 'Barcode verified successfully';
            $data1        = [
                'type' => 'barcode_verification',
                'data' => $x,
            ];
            $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $from->send($res);
            if ($trip->user_id == $AuthUserID) {
                if ($trip->car_id != null) {
                    $driver = $this->getClientByUserId($trip->car->user_id);
                    if ($driver) {
                        $driver->send($res);
                    }
                } else {
                    $driver = $this->getClientByUserId($trip->scooter->user_id);
                    if ($driver) {
                        $driver->send($res);
                    }
                }
            } else {
                $client = $this->getClientByUserId($trip->user_id);
                if ($client) {
                    $client->send($res);
                }
            }

        } else {
            $x['trip_id'] = $data['trip_id'];
            $x['message'] = 'Invalid barcode for this trip';
            $data1        = [
                'type' => 'barcode_verification',
                'data' => $x,
            ];
            $res = json_encode($data1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            $from->send($res);
        }

    }
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;

        // echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //     , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);
        $userId_forPong = $this->getUserIdByConn($from);
    if ($userId_forPong) {
        $this->lastPong[$userId_forPong] = time();
    }

    if (($data['type'] ?? null) === 'pong') {
        return;
     } else {
            $AuthUserID = $this->getUserIdByConn($from);
            if (!$AuthUserID) {
                return;
            }
            if (is_array($data) && array_key_exists('data', $data)) {

                $requestData = json_encode($data['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            }

            try {
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
                    case 'update_location':
                        $this->update_location($from, $AuthUserID, $requestData);
                        break;
                    // case 'send_message':
                    //     $this->send_message($from, $AuthUserID, $requestData);
                    //     break;
                    case 'barcode_verification_request':
                        $this->check_barcode($from, $AuthUserID, $requestData);
                        break;
                        case 'sos_triggered':
                            $sosData = json_decode($requestData, true);

                            $trip_id   = $sosData['trip_id'] ?? null;
                            $user_id   = $sosData['user_id'] ?? null;
                            $driver_id = $sosData['driver_id'] ?? $AuthUserID;
                            $lat       = $sosData['lat'] ?? null;
                            $lng       = $sosData['lng'] ?? null;

                            $payload = [
                                'type' => 'sos_triggered',
                                'data' => [
                                    'trip_id'   => $trip_id,
                                    'user_id'   => $user_id,
                                    'driver_id' => $driver_id,
                                    'lat'       => $lat,
                                    'lng'       => $lng,
                                ],
                            ];

                            $res = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);


                            if ($user_id) {
                                $client = $this->getClientByUserId($user_id);
                                if ($client) {
                                    $client->send($res);
                                }
                            }

                            $from->send($res);

                            $date_time = date('Y-m-d h:i:s a');
                            echo sprintf('[ %s ] SOS triggered "%s"' . "\n", $date_time, $res);

                            break;
                            case 'set_availability':
                                $user = User::findOrFail($AuthUserID);
                                $user->is_online = $data['data']['is_online'];
                                $user->auto_offline_at  = null;
                                $user->save();

                                $from->send(json_encode([
                                    'type' => 'online_status_updated',
                                    'data' => ['is_online' => $user->is_online]
                                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                                if ($user->is_online == '1') {
                                    $this->pushPendingTripsToDriver($user, $from);
                                }

                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ] Driver %d is now %s' . "\n", $date_time, $AuthUserID, $user->is_online == '1' ? 'ONLINE' : 'OFFLINE');
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
                        $x['lat']  = $live->lat;
                        $x['lng']  = $live->lng;
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
            } catch (\Throwable $e) {
                $date_time = date('Y-m-d h:i:s a');
                echo sprintf(
                    '[ %s ] ❌ onMessage error [type=%s, user=%s]: %s in %s:%d' . "\n",
                    $date_time,
                    $data['type'] ?? 'unknown',
                    $AuthUserID,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                );
                try {
                    $from->send(json_encode([
                        'type'    => 'error',
                        'message' => 'Something went wrong processing your request.',
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                } catch (\Exception $inner) {
                    // connection may already be dead, nothing more we can do
                }
            }

        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $userId = $this->getUserIdByConn($conn);

        unset($this->clientUserIdMap[$userId]);
        unset($this->lastPong[$userId]);
        $this->cancelPingTimer(spl_object_id($conn));
        $this->clients->detach($conn);

        $date_time = date('Y-m-d h:i:s a');
        echo "[ {$date_time} ],Connection {$conn->resourceId} has disconnected\n";
/*
        if ($userId && strpos((string) $userId, 'live_') === false) {
            $this->loop->addTimer(3600, function () use ($userId) {
                if (isset($this->clientUserIdMap[$userId])) return;

                $user = User::find($userId);
                if ($user && $user->is_online == '1') {
                    $user->is_online        = '0';
                    $user->auto_offline_at  = now();
                    $user->save();
                    echo "[ " . date('Y-m-d h:i:s a') . " ] Driver {$userId} auto-set OFFLINE (system)\n";
                }
            });
        }
            */

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
    private function create_trip_and_find_drivers2(ConnectionInterface $from, $AuthUserID, $tripRequest)
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

        // $response_x = calculate_distance($data['start_lat'], $data['start_lng'], $data['end_lat_1'], $data['end_lng_1']);
        // $distance   = $response_x['distance_in_km'];
        // $duration   = $response_x['duration_in_M'];
        // if ($data['end_lat_2'] != null && $data['end_lng_2'] != null) {
        //     $response_x = calculate_distance($data['end_lat_1'], $data['end_lng_1'], $data['end_lat_2'], $data['end_lng_2']);
        //     $distance   = $distance + $response_x['distance_in_km'];
        //     $duration   = $duration + $response_x['duration_in_M'];
        // }
        // if ($data['end_lat_3'] != null && $data['end_lng_3'] != null) {
        //     $response_x = calculate_distance($data['end_lat_2'], $data['end_lng_2'], $data['end_lat_3'], $data['end_lng_3']);
        //     $distance   = $distance + $response_x['distance_in_km'];
        //     $duration   = $duration + $response_x['duration_in_M'];
        // }

        $distance   = (float) $data['distance'];
        $duration   = (int) $data['duration'];
        $total_cost = (float) $data['total_cost'];
        $discount   = (float) $data['discount'];
        if ($distance > $maximum_distance_long_trip) {
            $from->send(json_encode(['type' => 'error', 'message' => "The trip distance ($distance km) exceeds the maximum allowed ($maximum_distance_long_trip km)."], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            // $total_cost1 = 0;

            // if ($distance >= $maximum_distance_short_trip) {
            //     $total_cost1 += $kilometer_price_short_trip * $maximum_distance_short_trip;
            // } else {
            //     $total_cost1 += $kilometer_price_short_trip * $distance;
            // }

            // if ($distance >= $maximum_distance_medium_trip) {
            //     $total_cost1 += $kilometer_price_medium_trip * ($maximum_distance_medium_trip - $maximum_distance_short_trip);
            // } elseif ($distance < $maximum_distance_medium_trip && $distance > $maximum_distance_short_trip) {
            //     $total_cost1 += $kilometer_price_medium_trip * ($distance - $maximum_distance_short_trip);
            // }

            // if ($distance == $maximum_distance_long_trip) {
            //     $total_cost1 += $kilometer_price_long_trip * ($maximum_distance_long_trip - $maximum_distance_medium_trip);
            // } elseif ($distance < $maximum_distance_long_trip && $distance > $maximum_distance_medium_trip) {
            //     $total_cost1 += $kilometer_price_long_trip * ($distance - $maximum_distance_medium_trip);
            // }

            // if ($Air_conditioning_service_price > 0 && $data['air_conditioned'] == '1') {
            //     $air_conditioning_cost = round($total_cost1 * ($Air_conditioning_service_price / 100), 4);
            // } else {
            //     $air_conditioning_cost = 0;
            // }

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
            //$day = date('l', strtotime($start_date));

            // $isPeak = false;

            // if (isset($peakTimes[$day])) {
            //     foreach ($peakTimes[$day] as $period) {
            //         if ($start_time >= $period['from'] && $start_time <= $period['to']) {
            //             $isPeak = true;
            //             break;
            //         }
            //     }
            // }
            // if ($isPeak) {
            //     $peakTimeCost = round($total_cost1 * ($increase_rate_peak_time_trip / 100), 4);
            // } else {
            //     $peakTimeCost = 0;
            // }
            //$total_cost = ceil($total_cost1 + $peakTimeCost + $air_conditioning_cost);
            $student = Student::where('user_id', $AuthUserID)->where('status', 'confirmed')->where('student_discount_service', '1')->first();
            if ($student) {
                $student_trips_count = Trip::where('user_id', $AuthUserID)->where('student_trip', '1')->where('status', 'completed')->where('start_date', $start_date)->count();
                if ($student_trips_count < 3) {
                    // $discount     = $total_cost * ($student_discount / 100);
                    // $total_cost   = $total_cost - $discount;
                    $student_trip = '1';
                } else {
                    //$discount     = 0;
                    $student_trip = '0';
                }
            } else {
                //$discount     = 0;
                $student_trip = '0';
            }
            // if ($total_cost < $less_cost_for_trip) {
            //     $total_cost = $less_cost_for_trip;
            // }
            $lastTrip = Trip::orderBy('id', 'desc')->first();

            if ($lastTrip) {
                $lastCode = $lastTrip->code;
                $code     = 'TRP-' . str_pad((int) substr($lastCode, 4) + 1, 12, '0', STR_PAD_LEFT);
            } else {
                $code = 'TRP-000000000001';
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

            $p = barcodeImage($trip->id);

            $u                           = User::findOrFail($AuthUserID);
            $user_image                  = getFirstMedia($u, $u->avatarCollection) ? 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection) : null;
            $newTrip['id']               = $trip->id;
            $newTrip['code']             = $code;
            $newTrip['barcode']          = 'https://api.lady-driver.com' . getFirstMedia($trip, $trip->barcodeImageCollection);
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
            $newTrip['payment_method']   = $data['payment_method'];

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

            $from->send(json_encode(['type' => 'created_trip', 'data' => $newTrip, 'message' => 'Trip Created Successfully'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION));
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
            $newTrip['user']['rate']              = Trip::where('user_id', $AuthUserID)->where('status', 'completed')->where('driver_stare_rate', '>', 0)->avg('driver_stare_rate') ?? 5.00;
            switch ($type) {
                case 'car':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Car Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '0')

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                       /* ->filter(function ($car) use ($trip) {
                            $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });*/

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
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(($total_cost * $app_ratio) / 100, 2) : 0.00;
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
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'comfort_car':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Comfort Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleCars           = Car::where('status', 'confirmed')->where('is_comfort', '1')

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                      /*  ->filter(function ($car) use ($trip) {
                            $response = calculate_distance($car->lat, $car->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });*/

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
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(($total_cost * $app_ratio) / 100, 2) : 0.00;
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
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

                                $client->send($message);
                                $date_time = date('Y-m-d h:i:s a');
                                echo sprintf('[ %s ],New Trip "%s" sent to user %d' . "\n", $date_time, $message, $eligibleDriverId);
                            }
                        }
                    }
                    break;

                case 'scooter':
                    $application_commission = Setting::where('key', 'application_commission')->where('category', 'Scooter Trips')->where('type', 'boolean')->first()->value;
                    $decimalPlaces          = 2;
                    $eligibleScooters       = Scooter::where('status', 'confirmed')

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
                        ->having('distance', '>=', 0.5)
->having('distance', '<=', 7)
                        ->get();
                        /*->filter(function ($scooter) use ($trip) {
                            $response = calculate_distance($scooter->lat, $scooter->lng, $trip->start_lat, $trip->start_lng);
                            return $response['distance_in_km'] <= 3;
                        });*/

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
                                $newTrip['app_rate']                  = $application_commission == 'On' ? round(($total_cost * $app_ratio) / 100, 2) : 0.00;
                                $newTrip['driver_rate']               = $total_cost - $newTrip['app_rate'];
                                $scooter2                             = Scooter::where('user_id', $eligibleDriverId)->first();
                                $response2                            = calculate_distance($scooter2->lat, $scooter2->lng, $trip->start_lat, $trip->start_lng);
                                $distance2                            = $response2['distance_in_km'];
                                $duration2                            = $response2['duration_in_M'];
                                $newTrip['client_location_distance']  = $distance2;
                                $newTrip['client_location_duration']  = $duration2;
                                $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', 'Scooter Trips')->where('type', 'number')->where('level', $driver->level)->first()->value);

                                $data2['type'] = 'new_trip';
                                $data2['data'] = $newTrip;
                                $message       = json_encode($data2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

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
    private function getUserIdByConn(ConnectionInterface $conn)
    {
        foreach ($this->clientUserIdMap as $userId => $client) {
            if ($client === $conn) {
                return $userId;
            }
        }
        return null;
    }
    private function pushPendingTripsToDriver(User $driver, ConnectionInterface $conn)
{
    $car     = Car::where('user_id', $driver->id)->where('status', 'confirmed')->first();
    $scooter = Scooter::where('user_id', $driver->id)->where('status', 'confirmed')->first();

    if (!$car && !$scooter) return;
    if ($driver->status != 'confirmed') return;

    // الرحلات النشطة اللي محتاجة سواق (created = فورية غير مأخوذة، scheduled = مجدولة لسه من غير offer مقبول)
    $activeTrips = Trip::whereIn('status', ['created', 'scheduled'])
        ->where(function ($q) {
            $q->whereNull('car_id')->whereNull('scooter_id');
        })
        ->get();

    foreach ($activeTrips as $trip) {

        // لو الدرايفر شاف الرحلة دي بالفعل، تجاهلها (ال periodic broadcast هيتكفل بيها)
        $alreadySeen = DB::table('drivers_trips')
            ->where('driver_id', $driver->id)
            ->where('trip_id', $trip->id)
            ->exists();
        if ($alreadySeen) continue;

        // لو رحلة مجدولة وعندها عرض scheduled مقبول بالفعل، متبعتهاش
        if ($trip->status == 'scheduled' && $trip->offers()->where('status', 'scheduled')->exists()) {
            continue;
        }

        $eligible = false;
        $vehicle  = null;
        $category = null;

        if ($trip->type == 'car' && $car && $car->is_comfort == '0') {
            $eligible = true;
            $vehicle  = $car;
            $category = 'Car Trips';
        } elseif ($trip->type == 'comfort_car' && $car && $car->is_comfort == '1') {
            $eligible = true;
            $vehicle  = $car;
            $category = 'Comfort Trips';
        } elseif ($trip->type == 'scooter' && $scooter) {
            $eligible = true;
            $vehicle  = $scooter;
            $category = 'Scooter Trips';
        }

        if (!$eligible) continue;

        // فلاتر إضافية زي الموجودة في create_trip_and_find_drivers
        if ($trip->type != 'scooter') {
            if ($trip->air_conditioned == '1' && $trip->type == 'car' && $vehicle->air_conditioned != '1') continue;
            if ($trip->animals == '1' && $vehicle->animals != '1') continue;
            if ($trip->user->gendor == 'Male' && $vehicle->passenger_type != 'male_female') continue;
        }

        // فلترة busy (نفس القواعد المستخدمة في create_trip_and_find_drivers)
        if ($trip->type != 'scooter' && in_array($vehicle->id, busyCarIds())) continue;
        if ($trip->type == 'scooter' && in_array($vehicle->id, busyScooterIds())) continue;

        // فلتر المسافة الحقيقية (نفس filterByRealDistance)
        $response = calculate_distance($vehicle->lat, $vehicle->lng, $trip->start_lat, $trip->start_lng);
        $realDistance = $response['distance_in_km'] ?? null;
        if ($realDistance === null || $realDistance < 0.5 || $realDistance > 7) continue;

        // ✅ الدرايفر مؤهل - سجّله وابعتله الرحلة
        DB::table('drivers_trips')->insert(['driver_id' => $driver->id, 'trip_id' => $trip->id]);

        $application_commission = Setting::where('key', 'application_commission')->where('category', $category)->where('type', 'boolean')->first()->value;
        $app_ratio = floatval(Setting::where('key', 'app_ratio')->where('category', $category)->where('type', 'number')->where('level', $driver->level)->first()->value);

        $u = $trip->user;
        $user_image = getFirstMedia($u, $u->avatarCollection) ? 'https://api.lady-driver.com' . getFirstMedia($u, $u->avatarCollection) : null;

        $newTrip = [
            'id'                  => $trip->id,
            'code'                => $trip->code,
            'barcode'             => 'https://api.lady-driver.com' . getFirstMedia($trip, $trip->barcodeImageCollection),
            'user_id'             => intval($trip->user_id),
            'start_date'          => $trip->start_date,
            'end_date'            => null,
            'start_time'          => $trip->start_time,
            'end_time'            => null,
            'start_lat'           => floatval($trip->start_lat),
            'start_lng'           => floatval($trip->start_lng),
            'address1'            => $trip->address1,
            'total_price'         => (float) $trip->total_price,
            'app_rate'            => $application_commission == 'On' ? round(((($trip->total_price + $trip->discount) * $app_ratio) / 100) - $trip->discount, 2) : 0.00,
            'discount'            => (float) $trip->discount,
            'paid_amount'         => 0.00,
            'remaining_amount'    => (float) $trip->total_price,
            'distance'            => (float) $trip->distance,
            'duration'            => (int) $trip->duration,
            'scheduled'           => $trip->scheduled,
            'type'                => $trip->type,
            'status'              => $trip->status,
            'payment_method'      => $trip->payment_method,
            'air_conditioned'     => $trip->air_conditioned,
            'animals'             => $trip->animals,
            'bags'                => $trip->bags,
            'seen_count'          => ['count' => 0, 'images' => []],
            'client_stare_rate'   => 0,
            'client_comment'      => null,
            'cancelled_by_id'     => null,
            'trip_cancelling_reason_id' => null,
            'driver_stare_rate'   => 0,
            'student_trip'        => $trip->student_trip,
            'driver_comment'      => null,
            'driver_arrived'      => null,
            'payment_status'      => 'unpaid',
            'current_offer'       => null,
            'created_at'          => $trip->created_at,
            'updated_at'          => $trip->updated_at,
        ];

        $newTrip['driver_rate'] = (float) $trip->total_price - $newTrip['app_rate'];

        $newTrip['user'] = [
            'id'           => intval($trip->user_id),
            'name'         => $u->name,
            'country_code' => $u->country_code,
            'phone'        => $u->phone,
            'image'        => $user_image,
            'rate'         => Trip::where('user_id', $trip->user_id)
                                ->where('status', 'completed')
                                ->where('driver_stare_rate', '>', 0)
                                ->avg('driver_stare_rate') ?? 5.00,
        ];

        $newTrip['client_location_distance']  = $response['distance_in_km'];
        $newTrip['client_location_duration']  = $response['duration_in_M'];
        $newTrip['Price_increase_percentage'] = floatval(Setting::where('key', 'maximum_price_ratio')->where('category', $category)->where('type', 'number')->where('level', $driver->level)->first()->value);

        // مصايف نهايات الرحلة
        $destinations = TripDestination::where('trip_id', $trip->id)->orderBy('id', 'asc')->get();
        $xxx = 1;
        foreach ($destinations as $dest) {
            $newTrip['end_lat_' . $xxx] = $dest->lat;
            $newTrip['end_lng_' . $xxx] = $dest->lng;
            $newTrip['address' . ($xxx + 1)] = $dest->address;
            $xxx++;
        }

        $conn->send(json_encode(['type' => 'new_trip', 'data' => $newTrip], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

        $date_time = date('Y-m-d h:i:s a');
        echo sprintf('[ %s ] ⚡ Instant pending-trip push: Trip %d sent to newly-online driver %d' . "\n", $date_time, $trip->id, $driver->id);
    }
}
}

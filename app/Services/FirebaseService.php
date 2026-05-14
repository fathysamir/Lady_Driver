<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('firebase_credentials.json');
        $factory = (new Factory())->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($token, $title, $body, $data = [])
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => $title, 'body' => $body])
            ->withData($data);

        $this->messaging->send($message);
    }

    public function sendDataNotification($token, array $data, $androidPriority = 'high', $apnsSound = 'default')
    {
        $androidConfig = AndroidConfig::fromArray([
            'priority' => $androidPriority,
        ]);

        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'sound'             => $apnsSound,
                    'content-available' => 1, // needed for silent/data-only on iOS
                ],
            ],
        ]);

        $message = CloudMessage::withTarget('token', $token)
            // notification block intentionally omitted — data only
            ->withData($data)
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig);

        $this->messaging->send($message);
    }
}
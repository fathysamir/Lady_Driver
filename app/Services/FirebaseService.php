<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class FirebaseService
{
    protected $messaging = null;

    public function __construct()
    {
        try {
            $serviceAccountPath = storage_path('firebase_credentials.json');

            if (!file_exists($serviceAccountPath)) {
                echo "❌ Firebase credentials file not found at: {$serviceAccountPath}\n";
                return;
            }

            $factory = (new Factory())->withServiceAccount($serviceAccountPath);
            $this->messaging = $factory->createMessaging();
            echo "✅ Firebase initialized\n";

        } catch (\Exception $e) {
            echo "❌ Firebase initialization failed: " . $e->getMessage() . "\n";
            $this->messaging = null;
        }
    }

    public function sendNotification(string $token, string $title, string $body, array $data = []): void
    {
        if (!$this->isReady($token)) return;

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(['title' => $title, 'body' => $body])
                ->withData($data);

            $this->messaging->send($message);
            echo "✅ Notification sent to token: {$token}\n";

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $this->clearInvalidToken($token);

        } catch (\Exception $e) {
            echo "❌ sendNotification failed: " . $e->getMessage() . "\n";
        }
    }

    public function sendDataNotification(
        string $token,
        array $data,
        string $androidPriority = 'high',
        string $apnsSound = 'default'
    ): void {
        if (!$this->isReady($token)) return;

        try {
            $androidConfig = AndroidConfig::fromArray([
                'priority' => $androidPriority,
            ]);

            $apnsConfig = ApnsConfig::fromArray([
                'payload' => [
                    'aps' => [
                        'sound'             => $apnsSound,
                        'content-available' => 1,
                    ],
                ],
            ]);

            $message = CloudMessage::withTarget('token', $token)
                ->withData($data)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig);

            $this->messaging->send($message);
            echo "✅ Data notification sent to token: {$token}\n";

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Token is no longer valid
            $this->clearInvalidToken($token);

        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            echo "❌ Invalid FCM argument for token [{$token}]: " . $e->getMessage() . "\n";

        } catch (\Exception $e) {
            echo "❌ sendDataNotification failed for token [{$token}]: " . $e->getMessage() . "\n";
        }
    }

    // -----------------------------------------------------------------------

    private function isReady(string $token): bool
    {
        if ($this->messaging === null) {
            echo "⚠️ Firebase not initialized — skipping notification.\n";
            return false;
        }

        if (empty(trim($token))) {
            echo "⚠️ Empty FCM token — skipping notification.\n";
            return false;
        }

        return true;
    }

    private function clearInvalidToken(string $token): void
    {
        echo "🧹 FCM token no longer valid, clearing: {$token}\n";

        try {
            // Works for both User (drivers/clients) tokens
            \App\Models\User::where('device_token', $token)
                ->update(['device_token' => null]);

        } catch (\Exception $e) {
            echo "❌ Failed to clear invalid token from DB: " . $e->getMessage() . "\n";
        }
    }
}
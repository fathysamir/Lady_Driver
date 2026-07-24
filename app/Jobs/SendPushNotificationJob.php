<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 15;

    public function __construct(
        protected string $deviceToken,
        protected array $data
    ) {}

    public function handle(): void
    {
        if (empty(trim($this->deviceToken))) {
            return;
        }

        $accessToken = $this->getFirebaseAccessToken();
        if (!$accessToken) {
            \Log::error('Push failed: no access token');
            return;
        }

        $credentialsData = json_decode(
            file_get_contents(storage_path('firebase_credentials.json')),
            true
        );
        $projectId = $credentialsData['project_id'];

        $lang  = $this->data['lang'] ?? 'en';
        $title = $lang === 'ar' ? ($this->data['title_ar'] ?? 'رحلة جديدة') : ($this->data['title_en'] ?? 'New Trip Near You');
        $body  = $lang === 'ar' ? ($this->data['body_ar'] ?? 'يوجد رحلة جديدة بالقرب منك') : ($this->data['body_en'] ?? 'New Trip Near You');

        $stringData = array_map('strval', $this->data);

        $payload = [
            'message' => [
                'token' => $this->deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => $stringData,
                'android' => [
                    'priority' => 'high',
                    'notification' => ['channel_id' => 'high_importance_channel', 'sound' => 'default'],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => 'default', 'content-available' => 1]],
                ],
            ],
        ];

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

        if ($httpCode !== 200) {
            $errorMessage = $responseData['error']['message'] ?? $response;
            $errorCode = $responseData['error']['details'][0]['errorCode'] ?? null;
            \Log::warning("Push failed [{$httpCode}]: {$errorMessage}");

            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                \App\Models\User::where('device_token', $this->deviceToken)
                    ->update(['device_token' => null]);
            }
        }
    }

    private function getFirebaseAccessToken(): ?string
    {
        // Cache بدل الـ instance variable، لأن كل Job بيشتغل في process منفصل
        return Cache::remember('firebase_access_token', 3000, function () {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . storage_path('firebase_credentials.json'));
            $credentials = \Google\Auth\ApplicationDefaultCredentials::getCredentials(
                'https://www.googleapis.com/auth/firebase.messaging'
            );
            $token = $credentials->fetchAuthToken();
            return $token['access_token'] ?? null;
        });
    }
}
<?php

namespace App\Services;

class TripTrackingService
{
    public function calculate($lat, $lng, $trip)
    {
        $response = calculate_distance(
            $lat,
            $lng,
            $trip->start_lat,
            $trip->start_lng
        );

        if (!$response) return null;

        $distanceKm = round($response['distance_in_km'], 2);
        $meters     = $distanceKm * 1000;
        $duration   = intval($response['duration_in_M']);
        $eta        = now()->addMinutes($duration)->format('h:i A');

        $status = 'on_the_way';

        // ================= MESSAGES =================
        $messages = [
            'en' => [
                '500'    => 'Driver is 500 meters away',
                '400'    => 'Driver is 400 meters away',
                '300'    => 'Driver is 300 meters away',
                '200'    => 'Driver is 200 meters away',
                '100'    => 'Driver is 100 meters away',
                'arrived'=> 'Driver has arrived',
            ],
            'ar' => [
                '500'    => 'السائق على بعد 500 متر',
                '400'    => 'السائق على بعد 400 متر',
                '300'    => 'السائق على بعد 300 متر',
                '200'    => 'السائق على بعد 200 متر',
                '100'    => 'السائق على بعد 100 متر',
                'arrived'=> 'وصل السائق',
            ]
        ];

        $messageKey = null;

        if ($meters <= 500 && $meters > 400) $messageKey = '500';
        elseif ($meters <= 400 && $meters > 300) $messageKey = '400';
        elseif ($meters <= 300 && $meters > 200) $messageKey = '300';
        elseif ($meters <= 200 && $meters > 100) $messageKey = '200';
        elseif ($meters <= 100 && $meters > 0)   $messageKey = '100';

        if ($meters <= 100) {
            $messageKey = 'arrived';
            $status     = 'reached';
            $distanceKm = 0;
            $duration   = 0;
            $eta        = null;
        }

        return [
            'distance' => $distanceKm,
            'duration' => $duration,
            'eta'      => $eta,

            // 👇 bilingual message
            'message'  => [
                'en' => $messages['en'][$messageKey] ?? null,
                'ar' => $messages['ar'][$messageKey] ?? null,
            ],

            'status'   => $status,
        ];
    }
}
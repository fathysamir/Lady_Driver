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

        if (!$response) {
            return null;
        }

        $distanceKm = round($response['distance_in_km'], 2);
        $meters     = $distanceKm * 1000;
        $duration   = (int) $response['duration_in_M'];
        $eta        = now()->addMinutes($duration)->format('h:i A');

        $status = 'on_the_way';

        $messages = [
            'en' => [
                '500'     => 'Driver is 500 meters away',
                '400'     => 'Driver is 400 meters away',
                '300'     => 'Driver is 300 meters away',
                '200'     => 'Driver is 200 meters away',
                '100'     => 'Driver is 100 meters away',
                'arrived' => 'Driver has arrived',
                'far'     => 'Driver is on the way',   // ← add a fallback key
            ],
           'ar' => [
    '500'     => 'الكابتن على بعد 500 متر',
    '400'     => 'الكابتن على بعد 400 متر',
    '300'     => 'الكابتن على بعد 300 متر',
    '200'     => 'الكابتن على بعد 200 متر',
    '100'     => 'الكابتن على بعد 100 متر',
    'arrived' => 'الكابتن وصلت',
    'far'     => 'الكابتن في الطريق',
]
        ];

        // Single if/elseif chain — no double-execution bug
        if ($meters <= 40) {
            $key        = 'arrived';
            $status     = 'reached'; //
        } elseif ($meters <= 200) {
            $key = '100';
        } elseif ($meters <= 300) {
            $key = '200';
        } elseif ($meters <= 400) {
            $key = '300';
        } elseif ($meters <= 500) {
            $key = '400';
        } else {
            $key = 'far'; // > 500m, still on the way
        }

        return [
            'distance' => $distanceKm,
            'duration' => $duration,
            'eta'      => $eta,
            'status'   => $status,
            'message'  => [
                'en' => $messages['en'][$key],
                'ar' => $messages['ar'][$key],
            ],
        ];
    }
}
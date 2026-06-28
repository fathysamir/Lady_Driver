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

        // Bug 3 fix: validate response fields
        if (!$response ||
            !isset($response['distance_in_km']) ||
            !isset($response['duration_in_M']) ||
            $response['distance_in_km'] < 0 ||
            $response['duration_in_M'] < 0) {
            return null;
        }

        $distanceKm = round($response['distance_in_km'], 2);
        $meters     = $distanceKm * 1000;
        $duration   = (int) $response['duration_in_M'];

        $messages = [
            'en' => [
                '500'     => 'Driver is 500 meters away',
                '400'     => 'Driver is 400 meters away',
                '300'     => 'Driver is 300 meters away',
                '200'     => 'Driver is 200 meters away',
                '100'     => 'Driver is 100 meters away',
                'arrived' => 'Driver has arrived',
                'far'     => 'Driver is on the way',
            ],
            'ar' => [
                '500'     => 'الكابتن على بعد 500 متر',
                '400'     => 'الكابتن على بعد 400 متر',
                '300'     => 'الكابتن على بعد 300 متر',
                '200'     => 'الكابتن على بعد 200 متر',
                '100'     => 'الكابتن على بعد 100 متر',
                'arrived' => 'الكابتن وصلت',
                'far'     => 'الكابتن في الطريق',
            ],
        ];

        // Bug 1 fix: correct thresholds
        if ($meters <= 40) {
            $key    = 'arrived';
            $status = 'reached';
        } elseif ($meters <= 100) {
            $key    = '100';
            $status = 'on_the_way';
        } elseif ($meters <= 200) {
            $key    = '200';
            $status = 'on_the_way';
        } elseif ($meters <= 300) {
            $key    = '300';
            $status = 'on_the_way';
        } elseif ($meters <= 400) {
            $key    = '400';
            $status = 'on_the_way';
        } elseif ($meters <= 500) {
            $key    = '500';
            $status = 'on_the_way';
        } else {
            $key    = 'far';
            $status = 'on_the_way';
        }

        // Bug 2 fix: no ETA when already arrived
        $eta = ($status === 'reached')
            ? null
            : now()->addMinutes($duration)->format('h:i A');

        return [
            'distance' => $distanceKm,
            'duration' => ($status === 'reached') ? 0 : $duration,
            'eta'      => $eta,
            'status'   => $status,
            'message'  => [
                'en' => $messages['en'][$key],
                'ar' => $messages['ar'][$key],
            ],
        ];
    }
}
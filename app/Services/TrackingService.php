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

        $distance = round($response['distance_in_km'], 2);
        $meters   = $distance * 1000;
        $duration = intval($response['duration_in_M']);
        $eta      = now()->addMinutes($duration)->format('h:i A');

        $message = null;
        $status  = 'on_the_way';

        if ($meters <= 500 && $meters > 400) $message = '500m away';
        elseif ($meters <= 400 && $meters > 300) $message = '400m away';
        elseif ($meters <= 300 && $meters > 200) $message = '300m away';
        elseif ($meters <= 200 && $meters > 100) $message = '200m away';
        elseif ($meters <= 100 && $meters > 0)   $message = '100m away';

        if ($meters <= 100) {
            $message  = 'driver reached';
            $status   = 'reached';
            $distance = 0;
            $duration = 0;
            $eta      = null;
        }

        return [
            'distance' => $distance,
            'duration' => $duration,
            'eta'      => $eta,
            'message'  => $message,
            'status'   => $status,
        ];
    }
}
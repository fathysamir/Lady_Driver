<?php

namespace App\Services;

class TripTrackingService
{
    // هامش أمان بالمتر — لو المسافة الخط المستقيم أكبر منه، الرسالة أكيد هتكون "far" برضو
    private const FAR_THRESHOLD_METERS = 800;

    public function calculate($lat, $lng, $trip)
    {
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

        // Cost fix: Haversine gate — مجاني تمامًا، بيحسب مسافة خط مستقيم بدل نداء جوجل
        $airMeters = $this->haversineMeters($lat, $lng, $trip->start_lat, $trip->start_lng);

        if ($airMeters > self::FAR_THRESHOLD_METERS) {
            return [
                'distance' => round($airMeters / 1000, 2),
                'duration' => 0,
                'eta'      => null,
                'status'   => 'on_the_way',
                'message'  => [
                    'en' => $messages['en']['far'],
                    'ar' => $messages['ar']['far'],
                ],
            ];
        }

        // من هنا بس (السواق قرب فعلاً) بننادي جوجل عشان الدقة تفرق
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

    private function haversineMeters($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
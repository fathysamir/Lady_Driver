<?php

namespace App\Services;

/**
 * يبحث عن نقطة التقاء بديلة أفضل من نقطة الراكب الأصلية، في حالات
 * "الجانب الآخر من الطريق" أو الحاجة لـ U-turn طويل.
 *
 * المنطق:
 *  1) نولّد نقاط مرشحة قريبة من الراكب (offsets صغيرة حول موقعه — تقاطعات/أرصفة محتملة).
 *  2) لكل نقطة مرشحة نحسب: ETA السائق إليها + مسافة مشي الراكب إليها.
 *  3) نحسب "التكلفة الإجمالية" بصيغة موزونة، وناختار الأقل تكلفة.
 *  4) لا نقترح نقطة إلا إذا كانت تحسّن ETA السائق بشكل ملموس (وإلا لا فائدة من
 *     إجبار الراكب على المشي).
 */
class PickupPointOptimizerService
{
    /** أقصى مسافة مشي معقولة للراكب (متر) — لا نقترح أبعد من ذلك */
    const MAX_WALK_DISTANCE_M = 150;

    /** الحد الأدنى لتحسن ETA السائق (بالثواني) لتستحق النقطة البديلة الاقتراح */
    const MIN_ETA_IMPROVEMENT_SEC = 60;

    /** وزن كل ثانية مشي للراكب مقابل كل ثانية توفير للسائق، في معادلة التكلفة */
    const WALK_SECOND_WEIGHT = 1.4; // المشي "يكلّف" الراكب أكثر من انتظاره في عربيته

    /** متوسط سرعة مشي الإنسان (م/ث) لتحويل مسافة المشي لزمن */
    const WALK_SPEED_MPS = 1.2;

    /** نطاق البحث عن نقاط مرشحة حول الراكب (بالمتر) */
    const CANDIDATE_OFFSETS_M = [30, 60, 100, 150];

    /** عدد الاتجاهات المُجرّبة حول الراكب لكل مسافة (شمال/جنوب/شرق/غرب وبينهم) */
    const CANDIDATE_DIRECTIONS = 8;

    public function findBetterPoint(
        float $driverLat,
        float $driverLng,
        float $passengerLat,
        float $passengerLng,
        string $vehicleType = 'car'
    ): ?array {
        // المسار الحالي (للراكب في موقعه الأصلي) كخط أساس للمقارنة
        $baseline = calculate_route_distance_precise($driverLat, $driverLng, $passengerLat, $passengerLng, $vehicleType);

        if (!$baseline) {
            return null;
        }

        $candidates = $this->generateCandidatePoints($passengerLat, $passengerLng);

        $best = null;
        $bestCost = null;

        foreach ($candidates as $candidate) {
            $route = calculate_route_distance_precise(
                $driverLat,
                $driverLng,
                $candidate['lat'],
                $candidate['lng'],
                $vehicleType
            );

            if (!$route) {
                continue;
            }

            $walkDistanceM = calculate_air_distance_meters(
                $passengerLat,
                $passengerLng,
                $candidate['lat'],
                $candidate['lng']
            );

            if ($walkDistanceM > self::MAX_WALK_DISTANCE_M) {
                continue;
            }

            $etaImprovementSec = $baseline['duration_sec'] - $route['duration_sec'];

            // لا فائدة من نقطة بديلة لا تحسّن ETA السائق بشكل ملموس
            if ($etaImprovementSec < self::MIN_ETA_IMPROVEMENT_SEC) {
                continue;
            }

            $walkTimeSec = $walkDistanceM / self::WALK_SPEED_MPS;

            // التكلفة الإجمالية: زمن وصول السائق + (زمن مشي الراكب × وزنه) − ما تم توفيره من ETA
            // كلما قلت التكلفة كانت النقطة أفضل
            $totalCost = $route['duration_sec'] + ($walkTimeSec * self::WALK_SECOND_WEIGHT);

            if ($bestCost === null || $totalCost < $bestCost) {
                $bestCost = $totalCost;
                $best = [
                    'lat'              => $candidate['lat'],
                    'lng'              => $candidate['lng'],
                    'driver_eta_sec'   => $route['duration_sec'],
                    'walk_distance_m'  => round($walkDistanceM),
                    'walk_time_sec'    => round($walkTimeSec),
                    'eta_improvement_sec' => $etaImprovementSec,
                    'reason'           => $this->buildReason($etaImprovementSec, $walkDistanceM),
                ];
            }
        }

        return $best;
    }

    /**
     * يولّد نقاط مرشحة حول الراكب في عدة مسافات واتجاهات.
     * هذا تقريب مبسّط (offset بالدرجات) — كافٍ للمسافات الصغيرة (<200م) المستخدمة هنا.
     * لتحسين أدق لاحقًا: استخدام Roads API لإسقاط النقاط على أقرب طريق فعلي.
     */
    private function generateCandidatePoints(float $lat, float $lng): array
    {
        $points = [];

        // تحويل تقريبي: 1 درجة خط عرض ≈ 111,320 متر
        $metersPerDegreeLat = 111320;
        $metersPerDegreeLng = 111320 * cos(deg2rad($lat));

        foreach (self::CANDIDATE_OFFSETS_M as $offsetM) {
            for ($i = 0; $i < self::CANDIDATE_DIRECTIONS; $i++) {
                $angle = (2 * M_PI / self::CANDIDATE_DIRECTIONS) * $i;

                $dLat = ($offsetM * cos($angle)) / $metersPerDegreeLat;
                $dLng = ($offsetM * sin($angle)) / $metersPerDegreeLng;

                $points[] = [
                    'lat' => $lat + $dLat,
                    'lng' => $lng + $dLng,
                ];
            }
        }

        return $points;
    }

    private function buildReason(int $etaImprovementSec, float $walkDistanceM): array
    {
        $etaMinutes = round($etaImprovementSec / 60, 1);

        return [
            'en' => "Saves driver ~{$etaMinutes} min, requires {$walkDistanceM}m walk",
            'ar' => "يوفر على السائق ~{$etaMinutes} دقيقة، يتطلب مشي {$walkDistanceM} متر",
        ];
    }
}
<?php

namespace App\Services;

use App\Models\Trip;
use Carbon\Carbon;

class TripTrackingService
{
    // ===================== ثوابت القرار (مطابقة للملف المطلوب) =====================

    /** الحد الأقصى لـ Route Distance (بالمتر) لاعتبار السائق "وصل" */
    const ARRIVAL_ROUTE_DISTANCE_M = 30;

    /** الحد الأقصى لـ ETA (بالثواني) لاعتبار السائق "وصل" */
    const ARRIVAL_ETA_SECONDS = 20;

    /** الحد الأقصى لسرعة السائق (كم/س) لاعتبار السائق "وصل" */
    const ARRIVAL_MAX_SPEED_KMH = 5;

    /** المدة الزمنية المطلوبة لاستمرار تحقق الشروط الثلاثة معًا (بالثواني) */
    const ARRIVAL_SUSTAIN_SECONDS = 5;

    /**
     * إذا كان الفارق بين Route Distance و Air Distance أكبر من هذا الحد (بالمتر)
     * أو كانت النسبة بينهما أكبر من النسبة المحددة أدناه، نعتبر أننا في حالة
     * "جانب آخر من الطريق" تستدعي فحص U-turn / اقتراح نقطة التقاء بديلة.
     */
    const OPPOSITE_SIDE_ABSOLUTE_DIFF_M = 80;
    const OPPOSITE_SIDE_RATIO_THRESHOLD = 2.5; // route_distance >= 2.5x air_distance

    /** لا نعيد استدعاء Distance Matrix API أكثر من مرة كل X ثانية لكل رحلة */
    const ROUTE_CHECK_THROTTLE_SECONDS = 4;

    public function __construct(
        private ?PickupPointOptimizerService $pickupOptimizer = null
    ) {
        $this->pickupOptimizer ??= app(PickupPointOptimizerService::class);
    }

    /**
     * نقطة الدخول الرئيسية: تُستدعى من update_location() في كل تحديث موقع للسائق.
     *
     * @param float $lat   موقع السائق الحالي (lat)
     * @param float $lng   موقع السائق الحالي (lng)
     * @param Trip  $trip  الرحلة الحالية
     * @param float $speedKmh سرعة السائق الحالية القادمة من GPS (كم/س)
     */
    public function calculate($lat, $lng, Trip $trip, $speedKmh = 0)
    {
        // نقطة الالتقاء الفعلية: لو فيه نقطة مقترحة ومقبولة، نحسب عليها
        // وإلا نحسب على نقطة الراكب الأصلية (start_lat/start_lng)
        $targetLat = $trip->pickup_point_status === 'accepted' && $trip->suggested_pickup_lat
            ? $trip->suggested_pickup_lat
            : $trip->start_lat;

        $targetLng = $trip->pickup_point_status === 'accepted' && $trip->suggested_pickup_lng
            ? $trip->suggested_pickup_lng
            : $trip->start_lng;

        // ----------------------------------------------------------------
        // 1) حساب Air Distance (فوري، بدون API) للمقارنة وكشف "الجانب الآخر"
        // ----------------------------------------------------------------
        $airDistanceM = calculate_air_distance_meters($lat, $lng, $targetLat, $targetLng);

        // ----------------------------------------------------------------
        // 2) حساب Route Distance / ETA — مع throttling لتفادي إفراط استدعاء API
        // ----------------------------------------------------------------
        $routeData = $this->getRouteDataWithThrottle($lat, $lng, $targetLat, $targetLng, $trip);

        if (!$routeData) {
            // فشل الحصول على بيانات المسار (خطأ شبكة/API) — رجوع آمن:
            // لا نعتبر السائق "وصل" أبدًا بدون بيانات مسار موثوقة.
            return [
                'distance'  => round($airDistanceM / 1000, 2),
                'duration'  => null,
                'eta'       => null,
                'status'    => 'on_the_way',
                'message'   => null,
                'opposite_side_detected' => false,
            ];
        }

        $routeDistanceM = $routeData['distance_m'];
        $etaSeconds     = $routeData['duration_sec'];

        // ----------------------------------------------------------------
        // 3) اكتشاف حالة "الجانب الآخر من الطريق"
        // ----------------------------------------------------------------
        $oppositeSideDetected = $this->isOppositeSideOfRoad($airDistanceM, $routeDistanceM);

        if ($oppositeSideDetected && !$trip->opposite_side_detected) {
            $trip->update(['opposite_side_detected' => true]);
            $this->maybeSuggestBetterPickupPoint($trip, $lat, $lng);
        } elseif (!$oppositeSideDetected && $trip->opposite_side_detected) {
            $trip->update(['opposite_side_detected' => false]);
        }

        // ----------------------------------------------------------------
        // 4) فحص شروط الوصول الثلاثة + استمرارها 5 ثوانٍ متواصلة
        // ----------------------------------------------------------------
        $meetsArrivalConditions =
            $routeDistanceM <= self::ARRIVAL_ROUTE_DISTANCE_M &&
            $etaSeconds <= self::ARRIVAL_ETA_SECONDS &&
            $speedKmh <= self::ARRIVAL_MAX_SPEED_KMH;

        $hasArrived = $this->evaluateSustainedArrival($trip, $meetsArrivalConditions);

        // ----------------------------------------------------------------
        // 5) بناء الرسالة / الحالة للعرض على الراكب والسائق
        // ----------------------------------------------------------------
        if ($hasArrived) {

            $status = 'reached';

            $message = [
                'en' => 'Driver has arrived',
                'ar' => 'الكابتن وصلت',
            ];

            $distance = 0;
            $duration = 0;
            $eta = null;

        } else {

            $status = 'on_the_way';

            $message = $this->buildProximityMessage($routeDistanceM);

            $distance = round($routeDistanceM / 1000, 2);

            $duration = (int) ceil($etaSeconds / 60);

            $eta = Carbon::now()
                ->addSeconds($etaSeconds)
                ->format('h:i A');
        }

        return [
            'distance'               => $distance,
            'duration'                => $duration,
            'eta'                     => $eta,
            'eta_seconds'             => $etaSeconds,
            'route_distance_m'        => $routeDistanceM,
            'air_distance_m'          => round($airDistanceM),
            'status'                  => $status,
            'message'                 => $message,
            'opposite_side_detected'  => $oppositeSideDetected,
            'driver_arrived'          => $hasArrived,
        ];
    }

    /**
     * يجلب Route Distance/ETA من Google، مع تخزين مؤقت (throttle) على مستوى الرحلة
     * لتقليل عدد استدعاءات API — لأن موقع السائق يتحدث كل ثانية أو أقل عادة،
     * بينما لا نحتاج فعليًا دقة أعلى من كل 3-4 ثوانٍ لاتخاذ قرار الوصول.
     */
    private function getRouteDataWithThrottle($lat, $lng, $targetLat, $targetLng, Trip $trip): ?array
    {
        $now = Carbon::now();

        $canUseCache = $trip->last_route_check_at
            && $trip->last_route_check_at->diffInSeconds($now) < self::ROUTE_CHECK_THROTTLE_SECONDS
            && $trip->last_route_distance_m !== null
            && $trip->last_eta_seconds !== null;

        if ($canUseCache) {
            return [
                'distance_m'   => $trip->last_route_distance_m,
                'duration_sec' => $trip->last_eta_seconds,
            ];
        }

        $vehicleType = $trip->scooter_id ? 'scooter' : 'car';

        $result = calculate_route_distance_precise($lat, $lng, $targetLat, $targetLng, $vehicleType);

        if (!$result) {
            return null;
        }

        $trip->update([
            'last_route_distance_m' => $result['distance_m'],
            'last_eta_seconds'      => $result['duration_sec'],
            'last_route_check_at'   => $now,
        ]);

        return $result;
    }

    /**
     * يحدد إن كانت الحالة تستدعي اعتبار السائق "على الجانب الآخر من الطريق":
     * فرق مطلق كبير، أو نسبة Route/Air أكبر من الحد المسموح — أيهما يحدث أولًا.
     * لا نعتمد على نسبة وحيدة لأنها تكون غير دقيقة عند المسافات الصغيرة جدًا
     * (مثلاً 5 متر هوائي و 20 متر طريق = نسبة 4x لكن الفرق الفعلي تافه).
     */
    private function isOppositeSideOfRoad(float $airDistanceM, int $routeDistanceM): bool
    {
        $absoluteDiff = $routeDistanceM - $airDistanceM;

        if ($absoluteDiff < self::OPPOSITE_SIDE_ABSOLUTE_DIFF_M) {
            return false;
        }

        // تفادي القسمة على صفر/قيم تافهة
        if ($airDistanceM < 5) {
            return $routeDistanceM > self::OPPOSITE_SIDE_ABSOLUTE_DIFF_M;
        }

        $ratio = $routeDistanceM / $airDistanceM;

        return $ratio >= self::OPPOSITE_SIDE_RATIO_THRESHOLD;
    }

    /**
     * يدير منطق "الاستمرار 5 ثوانٍ متواصلة": يبدأ عدّاد عند أول لحظة تتحقق فيها
     * كل الشروط، ويُلغى العدّاد فورًا لو خرجت أي حالة عن الشروط (حتى لا تُحتسب
     * فترات متقطعة على أنها استمرار واحد)، ويعتبر "وصل" فقط بعد مرور 5 ثوانٍ
     * فعلية من بداية الاستمرار.
     */
    private function evaluateSustainedArrival(Trip $trip, bool $meetsConditions): bool
    {
        // لو وصل فعليًا من قبل في هذه الرحلة، يبقى "وصل" (لا رجوع للخلف)
        if ($trip->is_driver_arrived) {
            return true;
        }

        if (!$meetsConditions) {
            if ($trip->arrival_state_started_at !== null) {
                $trip->update(['arrival_state_started_at' => null]);
            }
            return false;
        }

        $now = Carbon::now();

        if ($trip->arrival_state_started_at === null) {
            $trip->update(['arrival_state_started_at' => $now]);
            return false;
        }

        $sustainedSeconds = $trip->arrival_state_started_at->diffInSeconds($now);

        if ($sustainedSeconds >= self::ARRIVAL_SUSTAIN_SECONDS) {
            $trip->update(['driver_arrived' => $now]);
            return true;
        }

        return false;
    }

    /**
     * عند اكتشاف "جانب آخر من الطريق" لأول مرة، نطلب من PickupPointOptimizerService
     * البحث عن نقطة التقاء بديلة أفضل، ونخزنها كاقتراح (status = suggested) دون
     * تطبيقها تلقائيًا — القرار النهائي يُترك للراكب عبر REST endpoint مخصص
     * (accept/reject)، لتفادي تغيير نقطة الالتقاء بدون علم الراكب.
     */
    private function maybeSuggestBetterPickupPoint(Trip $trip, float $driverLat, float $driverLng): void
    {
        if ($trip->pickup_point_status !== 'none') {
            // فيه اقتراح سابق بالفعل (مقبول/مرفوض/معلّق) — لا نكرر الاقتراح
            return;
        }

        $vehicleType = $trip->scooter_id ? 'scooter' : 'car';

        $suggestion = $this->pickupOptimizer->findBetterPoint(
            driverLat: $driverLat,
            driverLng: $driverLng,
            passengerLat: $trip->start_lat,
            passengerLng: $trip->start_lng,
            vehicleType: $vehicleType
        );

        if (!$suggestion) {
            return;
        }

        $trip->update([
            'suggested_pickup_lat' => $suggestion['lat'],
            'suggested_pickup_lng' => $suggestion['lng'],
            'pickup_point_status'  => 'suggested',
        ]);

        event(new \App\Events\PickupPointSuggested([
            'trip_id'        => $trip->id,
            'lat'            => $suggestion['lat'],
            'lng'            => $suggestion['lng'],
            'driver_eta_sec' => $suggestion['driver_eta_sec'],
            'walk_distance_m'=> $suggestion['walk_distance_m'],
            'reason'         => $suggestion['reason'],
        ], $trip->user_id));
    }

    private function buildProximityMessage(int $routeDistanceM): array
    {
        if ($routeDistanceM <= 100) {
            return [
                'en' => 'Driver is 100 meters away',
                'ar' => 'الكابتن على بعد 100 متر',
            ];
        }

        if ($routeDistanceM <= 200) {
            return [
                'en' => 'Driver is 200 meters away',
                'ar' => 'الكابتن على بعد 200 متر',
            ];
        }

        if ($routeDistanceM <= 300) {
            return [
                'en' => 'Driver is 300 meters away',
                'ar' => 'الكابتن على بعد 300 متر',
            ];
        }

        if ($routeDistanceM <= 400) {
            return [
                'en' => 'Driver is 400 meters away',
                'ar' => 'الكابتن على بعد 400 متر',
            ];
        }

        if ($routeDistanceM <= 500) {
            return [
                'en' => 'Driver is 500 meters away',
                'ar' => 'الكابتن على بعد 500 متر',
            ];
        }

        return [
            'en' => 'Driver is on the way',
            'ar' => 'الكابتن في الطريق',
        ];
    }
}
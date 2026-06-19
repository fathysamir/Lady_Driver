<?php
/**
 * =========================================================================
 *  دالتان جديدتان تُضافان إلى TripController (أو ما يماثله) الحالي
 *  Endpoints مقترحة:
 *    POST /api/trips/{trip}/pickup-point/accept
 *    POST /api/trips/{trip}/pickup-point/reject
 * =========================================================================
 */

use App\Models\Trip;
use Illuminate\Http\Request;

class TripPickupPointController
{
    /**
     * الراكب يوافق على نقطة الالتقاء البديلة المقترحة.
     * بعد القبول، يبدأ TripTrackingService في حساب Route Distance/ETA
     * على هذه النقطة الجديدة بدلًا من نقطة الراكب الأصلية.
     */
    public function accept(Request $request, Trip $trip)
    {
        if ($trip->pickup_point_status !== 'suggested') {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد اقتراح نقطة التقاء بانتظار الرد عليه.',
            ], 422);
        }

        $trip->update(['pickup_point_status' => 'accepted']);

        return response()->json([
            'success' => true,
            'data' => [
                'pickup_lat' => $trip->suggested_pickup_lat,
                'pickup_lng' => $trip->suggested_pickup_lng,
            ],
        ]);
    }

    /**
     * الراكب يرفض النقطة البديلة ويفضّل الانتظار في نقطته الأصلية
     * (حتى لو تطلب الأمر U-turn أطول من السائق).
     */
    public function reject(Request $request, Trip $trip)
    {
        if ($trip->pickup_point_status !== 'suggested') {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد اقتراح نقطة التقاء بانتظار الرد عليه.',
            ], 422);
        }

        $trip->update(['pickup_point_status' => 'rejected']);

        return response()->json(['success' => true]);
    }
}
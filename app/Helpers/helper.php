<?php
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use App\Models\Setting;


function uploadMedia($request_file, $collection_name, $model)
{
    ini_set('post_max_size', '500M');
    ini_set('upload_max_filesize', '500M');
    ini_set('memory_limit', '500M');
    set_time_limit(10000000);
    $directory = public_path('images');

    if (! File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }
    $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
    $image           = $model->id . '' . $invitation_code . '' . time() . '.' . $request_file->extension();

    $request_file->move(public_path('images/'), $image);
    $path = ('/images/') . $image;
    DB::table('media')->insert([
        'attachmentable_type' => get_class($model),
        'attachmentable_id'   => $model->id,
        'collection_name'     => $collection_name,
        'Path'                => $path,
    ]);
    return $path;
}

function uploadMediaByURL($path, $collection_name, $model)
{
    DB::table('media')->insert([
        'attachmentable_type' => get_class($model),
        'attachmentable_id'   => $model->id,
        'collection_name'     => $collection_name,
        'Path'                => $path,
    ]);
    return $path;
}

function getMediaUrl($model, $collection_name)
{
    // $attachment = $model->attachment()
    //     ->where('collection_name', $collection_name)
    //     ->first();
    $attachments = DB::table('media')->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->where('attachmentable_type', get_class($model))->pluck('path')->toArray();

    if (count($attachments) == 0) {
        return null;
    } else {

        foreach ($attachments as $attachment) {
            $attachment->path = url($attachment->path);
        }
        return $attachments;
    }

}

function getFirstMediaUrl($model, $collection_name)
{
    // $attachment = $model->attachment()
    //     ->where('collection_name', $collection_name)
    //     ->first();
    $attachment = DB::table('media')->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->where('attachmentable_type', get_class($model))->orderBy('id', 'desc')->first();
    if (! $attachment || $attachment->path == null) {
        return null;
    }
    return url($attachment->path);
}
function getFirstMedia($model, $collection_name)
{
    // $attachment = $model->attachment()
    //     ->where('collection_name', $collection_name)
    //     ->first();
    $attachment = DB::table('media')->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->where('attachmentable_type', get_class($model))->orderBy('id', 'desc')->first();
    if (! $attachment || $attachment->path == null) {
        return null;
    }
    return $attachment->path;
}

function deleteMedia($model, $collection_name = null)
{
    $med = DB::table('media')
        ->where('attachmentable_type', get_class($model))
        ->where('attachmentable_id', $model->id)
        ->where('collection_name', $collection_name)
        ->first();

    if ($med) {
        $path = public_path(ltrim($med->path, '/'));
        if (file_exists($path)) {
            unlink($path);
        }
        if (File::exists($path)) {
            File::delete($path);
        }

        DB::table('media')->where('id', $med->id)->delete(); // ✅
        return true;
    }

    return true;

}

function generateOTP()
{
    return rand(100000, 999999);
}

function calculate_distance($lat1, $lng1, $lat2, $lng2, $vehicleType = 'car')
{

    //$api_key = 'AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0';
    $api_key = 'AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es'; // New Key

    switch (strtolower($vehicleType)) {
        case 'scooter':
        case 'motorbike':
        case 'bike':
            $mode = 'two_wheeler'; // special mode for scooters / motorbikes
            break;
        case 'walking':
            $mode = 'walking';
            break;
        case 'transit':
            $mode = 'transit';
            break;
        default:
            $mode = 'driving';
    }
    //$base_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $request_url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=$lat1,$lng1&destinations=$lat2,$lng2&mode=$mode&departure_time=now&traffic_model=best_guess&key=$api_key";
    //$request_url = $base_url . '?origins=' . floatval($lat1) . ',' . floatval($lng1) . '&destinations=' . floatval($lat2) . ',' . floatval($lng2) . '&key=' . $api_key;

    // $response = file_get_contents($request_url);
    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$response = file_get_contents($request_url, false, $context);
    $data     = json_decode($response, true);

    if ($data['status'] == 'OK') {

        $element  = $data['rows'][0]['elements'][0];
        $distance = $element['distance']['value'] ?? 0;                                             // meters
        $duration = $element['duration_in_traffic']['value'] ?? $element['duration']['value'] ?? 0; // seconds

       // $response2['distance_in_km'] = ceil($distance / 1000); // Convert distance to kilometers
       // $response2['duration_in_M']  = ceil($duration / 60);


       $response2['distance_in_km'] = round(($distance / 1000), 2); // Convert distance to kilometers
       $response2['duration_in_M']  = (int) ceil($duration / 60);


        return $response2;
    } else {
        return 'Error: Unable to calculate the distance.';
    }
}

function barcodeImage($id)
{
    $trip = Trip::findOrFail($id);

    $dns2d    = new DNS2D();
    $qrBase64 = $dns2d->getBarcodePNG($trip->barcode, 'QRCODE');
    $qrData   = base64_decode($qrBase64);

    return saveQrToMedia($qrData, $trip->barcodeImageCollection, $trip);
}
function saveQrToMedia($qrData, $collection_name, $model)
{
    $directory = public_path('images');

    if (! File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }

    $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
    $image           = $model->id . '_' . $invitation_code . '_' . time() . '.png'; // دايمًا PNG للـ QR

    file_put_contents(public_path('images/' . $image), $qrData);

    $path = '/images/' . $image;

    DB::table('media')->insert([
        'attachmentable_type' => get_class($model),
        'attachmentable_id'   => $model->id,
        'collection_name'     => $collection_name,
        'Path'                => $path,
    ]);

    return $path;
}
function getRouteWithToll($lat1, $lng1, $lat2, $lng2, $api_key)
{
    // بناء رابط API الخاص بالـ Google Directions
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$lat1},{$lng1}&destination={$lat2},{$lng2}&key={$api_key}";

    // إجراء الطلب إلى Google Directions API
   // $response = file_get_contents($url);
   $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$response = file_get_contents($url, false, $context);
    $data     = json_decode($response, true);

    // التحقق من حالة الطلب
    if ($data['status'] != 'OK') {
        return "Error fetching route: " . $data['status'];
    }

    // التحقق من التحذيرات على الطريق لمعرفة وجود بوابات دفع
    $warnings = $data['routes'][0]['warnings'];

    // عرض التحذيرات المتعلقة ببوابات الدفع
    if (! empty($warnings)) {
        foreach ($warnings as $warning) {
            if (strpos(strtolower($warning), 'toll') !== false) {
                echo "Warning: " . $warning . "\n";
            }
        }
    } else {
        echo "No toll warnings on the route.\n";
    }

    // استعراض مسار الطريق (للمزيد من المعلومات حول النقاط المختلفة على الطريق)
    $steps = $data['routes'][0]['legs'][0]['steps'];

    foreach ($steps as $step) {
        echo "Start: " . $step['start_location']['lat'] . "," . $step['start_location']['lng'] . "\n";
        echo "End: " . $step['end_location']['lat'] . "," . $step['end_location']['lng'] . "\n";
        echo "Instructions: " . $step['html_instructions'] . "\n";
        echo "-----------------\n";
    }
}

function highlight($text, $search)
{
    if ($search) {
        return str_ireplace($search, "<mark style='background-color:rgb(143, 118, 9); padding:0px;'>$search</mark>", $text);
    }
    return $text;
}

function uploadImage($request_file, $registration_id = null)
{
    ini_set('post_max_size', '500M');
    ini_set('upload_max_filesize', '500M');
    ini_set('memory_limit', '500M');
    set_time_limit(10000000);
    $directory = public_path('images');

    if (! File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }
    $invitation_code  = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
    $invitation_code2 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
    $image            = $invitation_code2 . '' . $invitation_code . '' . time() . '.' . $request_file->extension();

    $request_file->move(public_path('images/'), $image);
    $path = '/images/' . $image;
    if (! $registration_id) {
        do {
            $re_id = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 5);
        } while (DB::table('registration_images')->where('registration_id', $re_id)->exists());

        $registration_id = $re_id;
    }

    DB::table('registration_images')->insert([
        'registration_id' => $registration_id,
        'path'            => $path,
    ]);
    $response['registration_id'] = $registration_id;
    $response['path']            = $path;

    return $response;
}

function deleteUnusedRegistrationImages($registration_id, $used_paths = [])
{
    // Get all images linked to that registration_id
    $images = DB::table('registration_images')
        ->where('registration_id', $registration_id)
        ->get();

    foreach ($images as $image) {
        $path = public_path($image->path);

        // Delete if not in used list and file exists
        if (! in_array($image->path, $used_paths) && File::exists($path)) {
            File::delete($path);
        }
    }

    DB::table('registration_images')
        ->where('registration_id', $registration_id)
        ->delete();
}

function username_Generation($name)
{
    $base = Str::slug($name, '');

    if (! $base) {
        $base = 'user';
    }

    do {
        if (Str::contains($base, '_')) {
            $suffix = rand(100, 9999);
        } else {
            $suffix = '_' . rand(100, 9999);
        }

        $username = strtolower($base . $suffix);
    } while (User::where('username', $username)->exists());

    return $username;
}

function getTripSettings($category, $level = 1)
{
    // 🔹 GLOBAL SETTINGS (VAT + Income Tax)
    $global = Setting::whereIn('key', [
        'vat_percentage',
        'income_tax_percentage'
    ])->get()->keyBy('key');

    // 🔹 COMMISSION (dynamic)
    $commission = Setting::where('key', 'app_ratio')
        ->where('category', $category)
        ->where(function ($q) use ($level) {
            $q->whereNull('level')
              ->orWhere('level', $level);
        })
        ->first();

        return [
            'vat_percentage' => (float) ($global['vat_percentage']->value ?? 0),
            'income_tax_percentage' => (float) ($global['income_tax_percentage']->value ?? 0),
            'application_commission' => (float) ($commission->value ?? 0),
        ];
}

function getUserFcmTokens($user): array
{
    return $user->tokens()
        ->where('name', 'like', 'fcm::%')
        ->pluck('name')
        ->map(fn($name) => str_replace('fcm::', '', $name))
        ->filter(fn($token) => $token && $token !== 'no-device')
        ->values()
        ->toArray();
}

/**
 * حساب المسافة المباشرة (Air Distance) بين نقطتين بالمتر باستخدام معادلة Haversine.
 * لا يستدعي أي API خارجي — سريع ومجاني، يُستخدم للمقارنة مع Route Distance
 * لاكتشاف حالات "الجانب الآخر من الطريق" / الحاجة لـ U-turn.
 */
function calculate_air_distance_meters($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000; // meters

    $lat1Rad = deg2rad((float) $lat1);
    $lat2Rad = deg2rad((float) $lat2);
    $dLat    = deg2rad((float) $lat2 - (float) $lat1);
    $dLng    = deg2rad((float) $lng2 - (float) $lng1);

    $a = sin($dLat / 2) ** 2 +
         cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // meters
}

/**
 * نسخة محسّنة من calculate_distance() الحالية تُرجع المسافة بالمتر والزمن بالثواني
 * (بدون تقريب لأعلى دقيقة) — مطلوبة لأن منطق الوصول الجديد يحتاج دقة بالثواني والمتر،
 * وليس بالدقائق والكيلومترات كما في calculate_distance() الأصلية.
 *
 * هذه الدالة لا تلغي calculate_distance() القديمة (تبقى مستخدمة في باقي النظام
 * لحساب تكلفة/مسافة الرحلة)، بل تُضاف بجانبها خصيصًا لمنطق "وصول السائق".
 */
function calculate_route_distance_precise($lat1, $lng1, $lat2, $lng2, $vehicleType = 'car')
{
    $api_key = 'AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es'; // نفس المفتاح المستخدم حاليًا

    switch (strtolower($vehicleType)) {
        case 'scooter':
        case 'motorbike':
        case 'bike':
            $mode = 'two_wheeler';
            break;
        case 'walking':
            $mode = 'walking';
            break;
        default:
            $mode = 'driving';
    }

    $request_url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=$lat1,$lng1&destinations=$lat2,$lng2&mode=$mode&departure_time=now&traffic_model=best_guess&key=$api_key";

    $context  = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($request_url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['status']) || $data['status'] !== 'OK') {
        return null;
    }

    $element = $data['rows'][0]['elements'][0] ?? null;

    if (!$element || ($element['status'] ?? '') !== 'OK') {
        return null;
    }

    return [
        'distance_m'   => (int) ($element['distance']['value'] ?? 0),
        'duration_sec' => (int) ($element['duration_in_traffic']['value'] ?? $element['duration']['value'] ?? 0),
    ];
}

/**
 * يستدعي Google Directions API ويرجع بيانات مبسطة عن المسار:
 * - عدد خطوات المسار (steps) كمؤشر على التعقيد
 * - وجود تعليمات "U-turn" نصيًا في أي خطوة
 * - نقاط بداية/نهاية كل خطوة (تصلح لاحقًا لاختيار نقاط بديلة قريبة من المسار)
 *
 * يُستخدم فقط عند الاشتباه في "جانب آخر من الطريق" (Air vs Route تختلف كثيرًا)
 * تفاديًا لاستدعاء API إضافي في كل تحديث موقع.
 */
function get_directions_route($lat1, $lng1, $lat2, $lng2, $api_key = null)
{
    $api_key = $api_key ?: 'AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es';

    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$lat1},{$lng1}&destination={$lat2},{$lng2}&key={$api_key}";

    $context  = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['status']) || $data['status'] !== 'OK' || empty($data['routes'])) {
        return null;
    }

    $route = $data['routes'][0];
    $leg   = $route['legs'][0] ?? null;

    if (!$leg) {
        return null;
    }

    $steps          = $leg['steps'] ?? [];
    $requiresUturn  = false;
    $stepPoints     = [];

    foreach ($steps as $step) {
        $instructions = strtolower($step['html_instructions'] ?? '');

        if (str_contains($instructions, 'u-turn') || str_contains($instructions, 'uturn')) {
            $requiresUturn = true;
        }

        $stepPoints[] = [
            'start' => $step['start_location'] ?? null,
            'end'   => $step['end_location'] ?? null,
        ];
    }

    return [
        'distance_m'      => (int) ($leg['distance']['value'] ?? 0),
        'duration_sec'    => (int) ($leg['duration']['value'] ?? 0),
        'requires_uturn'  => $requiresUturn,
        'steps_count'     => count($steps),
        'steps'           => $stepPoints,
        'overview_points' => $route['overview_polyline']['points'] ?? null,
    ];
}
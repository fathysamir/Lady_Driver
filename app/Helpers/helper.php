<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Trip;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;

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
    $attachment = DB::table('media')->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->where('attachmentable_type', get_class($model))->first();

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
    $attachment = DB::table('media')->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->where('attachmentable_type', get_class($model))->first();

    if (! $attachment || $attachment->path == null) {
        return null;
    }
    return $attachment->path;
}

function deleteMedia($model, $collection_name = null)
{

    return DB::table('media')->where('attachmentable_type', get_class($model))->where('attachmentable_id', $model->id)->where('collection_name', $collection_name)->delete();

}

function generateOTP()
{
    return rand(100000, 999999);
}

function calculate_distance($lat1, $lng1, $lat2, $lng2)
{

    $api_key = 'AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0';
    //$base_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $request_url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=$lat1,$lng1&destinations=$lat2,$lng2&departure_time=now&traffic_model=best_guess&key=$api_key";
    //$request_url = $base_url . '?origins=' . floatval($lat1) . ',' . floatval($lng1) . '&destinations=' . floatval($lat2) . ',' . floatval($lng2) . '&key=' . $api_key;

    $response = file_get_contents($request_url);
    $data     = json_decode($response, true);

    if ($data['status'] == 'OK') {

        $distance                    = $data['rows'][0]['elements'][0]['distance']['value']; // Distance in meters
        $duration                    = $data['rows'][0]['elements'][0]['duration_in_traffic']['value'];
        $response2['distance_in_km'] = ceil($distance / 1000); // Convert distance to kilometers
        $response2['duration_in_M']  = ceil($duration / 60);

        return $response2;
    } else {
        return 'Error: Unable to calculate the distance.';
    }
}

function barcodeImage($id)
{
    $trip = Trip::findOrFail($id);

    $barcodePng = DNS1D::getBarcodePNG($trip->barcode, 'C128');

    return response($barcodePng)->header('Content-Type', 'image/png');
}
function getRouteWithToll($lat1, $lng1, $lat2, $lng2, $api_key)
{
    // بناء رابط API الخاص بالـ Google Directions
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$lat1},{$lng1}&destination={$lat2},{$lng2}&key={$api_key}";

    // إجراء الطلب إلى Google Directions API
    $response = file_get_contents($url);
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

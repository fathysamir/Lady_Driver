<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;


function uploadMedia($request_file, $collection_name, $model)
{
    ini_set('post_max_size', '500M');
    ini_set('upload_max_filesize', '500M');
    ini_set('memory_limit', '1000M');
    set_time_limit(10000000);
    $directory = public_path('images');

    if (!File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }
    $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
    $image = $model->id.''.$invitation_code.''.time() . '.' . $request_file->extension();

    $request_file->move(public_path('images/'), $image);
    $path = ('/images/') . $image;
    DB::table('media')->insert([
        'attachmentable_type' => get_class($model),
        'attachmentable_id' => $model->id,
        'collection_name' => $collection_name,
        'Path' => $path
    ]);
    return $path;
}

function getMediaUrl($model, $collection_name)
{  
    // $attachment = $model->attachment()
    //     ->where('collection_name', $collection_name)
    //     ->first();
     $attachments=DB::table('media')->where('attachmentable_id',$model->id)->where('collection_name',$collection_name)->where('attachmentable_type',get_class($model))->select('path')->get();
    
    if (count($attachments)==0) {
        return null;
    }else{
       
        foreach($attachments as $attachment){
            $attachment->path=url($attachment->path);
        }
        return $attachments;
    }

   
    
}

function getFirstMediaUrl($model, $collection_name)
{  
    // $attachment = $model->attachment()
    //     ->where('collection_name', $collection_name)
    //     ->first();
     $attachment=DB::table('media')->where('attachmentable_id',$model->id)->where('collection_name',$collection_name)->where('attachmentable_type',get_class($model))->first();
    
    if (!$attachment || $attachment->path==null) {
        return null;
    }
    return url($attachment->path);
}

function deleteMedia($model, $collection_name = null)
{    
               
        return DB::table('media')->where('attachmentable_type',get_class($model))->where('attachmentable_id',$model->id)->where('collection_name',$collection_name)->delete();
     
            
}

function generateOTP() {
    return rand(100000, 999999);
}

function calculate_distance($lat1,$lng1,$lat2,$lng2){
   
    $api_key = 'AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0';
    $base_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    
    $request_url = $base_url . '?origins=' . floatval($lat1) . ',' . floatval($lng1) . '&destinations=' . floatval($lat2) . ',' . floatval($lng2) . '&key=' . $api_key;
    
    $response = file_get_contents($request_url);
    $data = json_decode($response, true);
    
    if ($data['status'] == 'OK') {
        $distance = $data['rows'][0]['elements'][0]['distance']['value']; // Distance in meters
        $distance_in_km = round($distance / 1000, 2); // Convert distance to kilometers
        return  $distance_in_km ;
    } else {
        return 'Error: Unable to calculate the distance.';
    }
}
<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ScheduledDashboardMessage;
use Illuminate\Support\Facades\DB;
use App\Models\DashboardMessage;
use Illuminate\Support\Facades\File;
class ChatController extends ApiController
{
    public function send_message_view(){
        return view('dashboard.chats.send_message');
    }

    public function send_message(Request $request){
        //dd($request->all());
        ini_set('post_max_size', '500M');
        ini_set('upload_max_filesize', '500M');
        ini_set('memory_limit', '1000M');
        set_time_limit(10000000);
        if($request->image!=null){
            $image=$request->image;
            $directory = public_path('images');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
            $invitation_code2 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
            $imageName = $invitation_code2 . $invitation_code . time() . '.' . $image->extension();

            $image->move(public_path('images/'), $imageName);
            $imagePath = ('/images/') . $imageName;
        }else{
            $imagePath = null;
        }

        if($request->video!=null){
            $video=$request->video;
            $directory = public_path('images');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
            $invitation_code2 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
            $videoName = $invitation_code2 . $invitation_code . time() . '.' . $video->extension();

            $video->move(public_path('images/'), $videoName);
            $videoPath = ('/images/') . $videoName;
        }else{
            $videoPath = null;
        }
        if($request->date == null){
            if($request->users){
                foreach($request->users as $user){
                    $message=DashboardMessage::create(['receiver_id'=>$user,'message'=>$request->message]);
                    if($imagePath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->imageCollection,
                                    'Path' => $imagePath
                                ]);
                    }
                    if($videoPath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->videoCollection,
                                    'Path' => $videoPath
                                ]);
                    }
                }
            }elseif($request->receivers_type=='clients'){
                $users=User::where('mode','client')->where('status','confirmed')->get();
                foreach($users as $user){
                    $message=DashboardMessage::create(['receiver_id'=>$user->id,'message'=>$request->message]);
                    if($imagePath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->imageCollection,
                                    'Path' => $imagePath
                                ]);
                    }
                    if($videoPath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->videoCollection,
                                    'Path' => $videoPath
                                ]);
                    }
                }
            }elseif($request->receivers_type=='drivers'){
                $users=User::where('mode','driver')->where('status','confirmed')->get();
                foreach($users as $user){
                    $message=DashboardMessage::create(['receiver_id'=>$user->id,'message'=>$request->message]);
                    if($imagePath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->imageCollection,
                                    'Path' => $imagePath
                                ]);
                    }
                    if($videoPath != null){
                         DB::table('media')->insert([
                                    'attachmentable_type' => get_class($message),
                                    'attachmentable_id' => $message->id,
                                    'collection_name' => $message->videoCollection,
                                    'Path' => $videoPath
                                ]);
                    }
                }
            }
        }else{
             if($request->users){
                $users=json_encode($request->users);
                ScheduledDashboardMessage::create(['receivers'=>'users','users'=>$users,'message'=>$request->message,'sending_date'=>$request->date,'image_path'=>$imagePath,'video_path'=>$videoPath]);
             }elseif($request->receivers_type=='clients'){
                ScheduledDashboardMessage::create(['receivers'=>'clients','message'=>$request->message,'sending_date'=>$request->date,'image_path'=>$imagePath,'video_path'=>$videoPath]);
             }elseif($request->receivers_type=='drivers'){
                ScheduledDashboardMessage::create(['receivers'=>'drivers','message'=>$request->message,'sending_date'=>$request->date,'image_path'=>$imagePath,'video_path'=>$videoPath]);
             }
        }
        return redirect('/admin-dashboard/chats/send-message')->with('success', 'Message Sent Successfully.');
    }

    public function get_users(Request $request){
        if($request->mode=='users'){
            $users=User::where('status','confirmed')->role('Client')->get(['id', 'name']);
        }else{
            $users=User::where('mode',$request->mode)->where('status','confirmed')->get(['id', 'name']);
        }
        return $this->sendResponse($users, null, 200);

    }
}
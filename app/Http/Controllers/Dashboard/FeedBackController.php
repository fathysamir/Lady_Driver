<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\CarModel;
use App\Models\FeedBack;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\SendReply;
use Image;
use Str;
use File;

class FeedBackController extends ApiController
{
    public function index(Request $request){  
        $all_feed_back = FeedBack::orderBy('id', 'desc');

        if($request->has('user')&& $request->user!=null) {
            $all_feed_back->where('user_id', $request->user);
        }
        
        $all_feed_back = $all_feed_back->paginate(12);
        $search=$request->search;
        return view('dashboard.feed_back.index',compact('all_feed_back','search'));

    }

    public function view($id){
        $feed_back=FeedBack::where('id',$id)->first();
        return view('dashboard.feed_back.view',compact('feed_back'));
    }

    

   
}
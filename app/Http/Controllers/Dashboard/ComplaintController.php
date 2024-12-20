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
use App\Models\Complaint;
use App\Models\ContactUs;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\SendReply;
use Image;
use Str;
use File;

class ComplaintController extends ApiController
{
    public function index(Request $request){  
       
        return view('dashboard.complaints.index',compact('all_complaints','search'));

    }

    public function view($id){
        $complaint=Complaint::where('id',$id)->first();
        $complaint->seen='1';
        $complaint->save();
        return view('dashboard.complaints.view',compact('complaint'));
    }
}  
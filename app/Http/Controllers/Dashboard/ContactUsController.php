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
use App\Models\ContactUs;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\SendReply;
use Image;
use Str;
use File;

class ContactUsController extends ApiController
{
    public function index(Request $request){  
        $all_contact_us = ContactUs::orderBy('id', 'desc');

       
        if ($request->has('search') && $request->search!=null ) {
            $all_contact_us->where(function ($query) use ($request) {
                $query->where('subject', 'LIKE', '%' . $request->search . '%')
                ->orWhere('name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
                    
            });
        }
        $all_contact_us = $all_contact_us->paginate(12);
        $search=$request->search;
        return view('dashboard.contact_us.index',compact('all_contact_us','search'));

    }

    public function view($id){
        $contact_us=ContactUs::where('id',$id)->first();
        $contact_us->seen='1';
        $contact_us->save();
        return view('dashboard.contact_us.view',compact('contact_us'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'reply' => ['required', 'string'],
        ]);

       
        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
   
        ContactUs::where('id',$id)->update([ 'reply' => $request->reply
                
            ]);
        $contact_us=ContactUs::find($id);
        Mail::to($contact_us->email)->send(new SendReply($contact_us->name,$request->reply,$contact_us->subject));
    
        return redirect('/admin-dashboard/contact_us')->with('success', 'The message has been answered successfully.');

    }

   
}
<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Car;
use App\Models\CarMark;
use App\Models\TripCancellingReason;
use App\Models\Setting;
use Image;
use Str;
use File;
use App\Models\AboutUs;
class SettingController extends Controller
{
    public function index(Request $request){
        $all_settings=Setting::orderBy('id', 'desc');
        if($request->has('search') && $request->search!=null ) {
            $all_settings->where(function ($query) use ($request) {
                $query->where('label', 'LIKE', '%' . $request->search . '%');
            });
        }
        if($request->has('category')&& $request->category!=null) {
            $all_settings->where('category', $request->category);
        }
        $all_settings = $all_settings->paginate(12);
        return view('dashboard.settings.index',compact('all_settings'));
    }
    public function edit($id){
        $setting=Setting::where('id',$id)->first();
        return view('dashboard.settings.edit',compact('setting'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'label' => ['required','string','max:255'],
            'value' => ['required']
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        
        Setting::where('id',$id)->update([ 'label' => $request->label,
                                            'value'=>floatval($request->value)]);
        return redirect('/admin-dashboard/settings');

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function reasons_cancelling_trips(Request $request){
        $all_reasons=TripCancellingReason::orderBy('id', 'desc');
        if($request->has('search') && $request->search!=null ) {
            $all_reasons->where(function ($query) use ($request) {
                $query->where('en_reason', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ar_reason', 'LIKE', '%' . $request->search . '%');
            });
        }
        if($request->has('type')&& $request->type!=null) {
            $all_reasons->where('type', $request->type);
        }
        if($request->has('value_type')&& $request->value_type!=null) {
            $all_reasons->where('value_type', $request->value_type);
        }
        $all_reasons = $all_reasons->paginate(12);
        return view('dashboard.cancelling_reasons.index',compact('all_reasons'));
    }
    public function create_reason(){
        return view('dashboard.cancelling_reasons.create');
    }
    public function store_reason(Request $request){

        $rules = [
            'en_reason' => ['required', 'string', 'max:191'],
            'ar_reason' => ['required', 'string', 'max:191'],
            'category' => ['required'],
            'value_type' => ['nullable'],
            'value' => ['nullable'],
        ];
        
        // Check if 'value_type' is present in the request and not null
        if ($request->has('value_type') && $request->input('value_type') !== null) {
            $rules['value'] = ['required']; // Set 'value' field as required
        } else {
            $rules['value'] = ['nullable']; // Set 'value' field as nullable
        }
        
        $validator = Validator::make($request->all(), $rules);

       
        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        
        
        $reason=TripCancellingReason::create([
            'en_reason' => $request->en_reason,
            'ar_reason' => $request->ar_reason,
            'type' => $request->category
        ]);
        if($request->value_type!=null){
            $reason->value_type=$request->value_type;
            $reason->value=$request->value;
        }else{
            $reason->value_type='fixed';
            $reason->value=0;
        }
        $reason->save();
      return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason created successfully.');

    }
    public function edit_reason($id){
        $reason=TripCancellingReason::where('id',$id)->first();
        return view('dashboard.cancelling_reasons.edit',compact('reason'));
    }
    public function update_reason(Request $request,$id){
        $rules = [
            'en_reason' => ['required', 'string', 'max:191'],
            'ar_reason' => ['required', 'string', 'max:191'],
            'category' => ['required'],
            'value_type' => ['nullable'],
            'value' => ['nullable'],
        ];
        
        // Check if 'value_type' is present in the request and not null
        if ($request->has('value_type') && $request->input('value_type') !== null) {
            $rules['value'] = ['required','numeric', 'max:99']; // Set 'value' field as required
        } else {
            $rules['value'] = ['nullable']; // Set 'value' field as nullable
        }
        
        $validator = Validator::make($request->all(), $rules);

       
        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        
        TripCancellingReason::where('id',$id)->update([  'en_reason' => $request->en_reason,
                                                        'ar_reason' => $request->ar_reason,
                                                        'type' => $request->category,
                                                        
            ]);
        $reason=TripCancellingReason::find($id);
        if($request->value_type!=null){
            $reason->value_type=$request->value_type;
            $reason->value=$request->value;
        }else{
            $reason->value_type='fixed';
            $reason->value=0;
        }
        $reason->save();
        return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason updated successfully.');

    }
    public function delete_reason($id){
        $reason = TripCancellingReason::findOrFail($id);
    
        // If no employees are assigned to the department, proceed with deleting the department
        $reason->delete();
    
        return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason deleted successfully.');
    }
    /////////////////////////////////////////////////////about us///////////////////////////////////
    public function about_us(){
       
        $description=AboutUs::where('key','description')->first()->value;
        $phone=AboutUs::where('key','phone')->first()->value;
        $email=AboutUs::where('key','email')->first()->value;
        $facebook=AboutUs::where('key','facebook')->first()->value;
        $instagram=AboutUs::where('key','instagram')->first()->value;
        $twitter=AboutUs::where('key','twitter')->first()->value;
        return view('dashboard.about_us.view',compact('description','phone','email','facebook','twitter','instagram'));
    }

    public function update_about_us(Request $request){
        AboutUs::where('key','description')->update(['value'=>$request->description]);
        AboutUs::where('key','email')->update(['value'=>$request->email]);
        AboutUs::where('key','phone')->update(['value'=>$request->phone]);
        AboutUs::where('key','facebook')->update(['value'=>$request->facebook]);
        AboutUs::where('key','instagram')->update(['value'=>$request->instagram]);
        AboutUs::where('key','twitter')->update(['value'=>$request->twitter]);
        return redirect('/admin-dashboard/about_us/view')->with('success', 'About Us updated successfully.');
    }
}
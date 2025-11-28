<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use App\Models\Setting;
use App\Models\TripCancellingReason;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $all_settings = Setting::orderBy('id', 'desc');
        if ($request->has('search') && $request->search != null) {
            $all_settings->where(function ($query) use ($request) {
                $query->where('label', 'LIKE', '%' . $request->search . '%');
            });
        }
        // if ($request->has('category') && $request->category != null) {
        //     $all_settings->where('category', $request->category);
        // }
        $all_settings = $all_settings->get();
        $search       = $request->search;
        return view('dashboard.settings.index', compact('all_settings', 'search'));
    }
    public function edit($id)
    {
        $setting = Setting::where('id', $id)->first();
        return view('dashboard.settings.edit', compact('setting'));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:255'],

        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        $set = Setting::where('id', $id)->first();
        if ($set->type == 'options') {
            if ($request->value) {
                $value = json_encode($request->value);
            } else {
                $value = null;
            }

        } else if ($set->type == 'boolean') {
            $value = $request->value ? 'On' : 'Off';
        } else {
            $value = floatval($request->value);
        }
        $set->update(['label' => $request->label,
            'value'               => $value]);
        return redirect('/admin-dashboard/settings');

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function reasons_cancelling_trips(Request $request)
    {
        $all_reasons = TripCancellingReason::orderBy('id', 'desc');
        if ($request->has('search') && $request->search != null) {
            $all_reasons->where(function ($query) use ($request) {
                $query->where('en_reason', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ar_reason', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->has('type') && $request->type != null) {
            $all_reasons->where('type', $request->type);
        }
        if ($request->has('value_type') && $request->value_type != null) {
            $all_reasons->where('value_type', $request->value_type);
        }
        $all_reasons = $all_reasons->paginate(12);
        $search      = $request->search;
        return view('dashboard.cancelling_reasons.index', compact('all_reasons', 'search'));
    }
    public function create_reason()
    {
        return view('dashboard.cancelling_reasons.create');
    }
    public function store_reason(Request $request)
    {

        $rules = [
            'en_reason'  => ['required', 'string', 'max:191'],
            'ar_reason'  => ['required', 'string', 'max:191'],
            'category'   => ['required'],
            'status'     => ['required'],
            'value_type' => ['nullable'],
            'value'      => ['nullable', 'numeric', 'required_with:value_type'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $reason = TripCancellingReason::create([
            'en_reason' => $request->en_reason,
            'ar_reason' => $request->ar_reason,
            'type'      => $request->category,
            'status'    => $request->status,
        ]);
        if ($request->value_type != null) {
            $reason->value_type = $request->value_type;
            $reason->value      = $request->value;
        } else {
            $reason->value_type = 'fixed';
            $reason->value      = 0;
        }
        $reason->save();
        return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason created successfully.');

    }
    public function edit_reason($id)
    {
        $reason = TripCancellingReason::where('id', $id)->first();
        return view('dashboard.cancelling_reasons.edit', compact('reason'));
    }
    public function update_reason(Request $request, $id)
    {
        $rules = [
            'en_reason'  => ['required', 'string', 'max:191'],
            'ar_reason'  => ['required', 'string', 'max:191'],
            'category'   => ['required'],
            'status'     => ['required'],
            'value_type' => ['nullable'],
            'value'      => ['nullable', 'numeric', 'required_with:value_type'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        TripCancellingReason::where('id', $id)->update(['en_reason' => $request->en_reason,
            'ar_reason'                                                 => $request->ar_reason,
            'type'                                                      => $request->category,
            'status'                                                    => $request->status,

        ]);
        $reason = TripCancellingReason::find($id);
        if ($request->value_type != null) {
            $reason->value_type = $request->value_type;
            $reason->value      = $request->value;
        } else {
            $reason->value_type = 'fixed';
            $reason->value      = 0;
        }
        $reason->save();
        return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason updated successfully.');

    }
    public function delete_reason($id)
    {
        $reason = TripCancellingReason::findOrFail($id);

        // If no employees are assigned to the department, proceed with deleting the department
        $reason->delete();

        return redirect('/admin-dashboard/reasons-cancelling-trips')->with('success', 'Reason deleted successfully.');
    }
    /////////////////////////////////////////////////////about us///////////////////////////////////
    public function about_us()
    {

        $description      = AboutUs::where('key', 'description')->first()->value;
        $phone1           = AboutUs::where('key', 'phone1')->first()->value;
        $email1           = AboutUs::where('key', 'email1')->first()->value;
        $phone2           = AboutUs::where('key', 'phone2')->first()->value;
        $email2           = AboutUs::where('key', 'email2')->first()->value;
        $phone3           = AboutUs::where('key', 'phone3')->first()->value;
        $email3           = AboutUs::where('key', 'email3')->first()->value;
        $phone4           = AboutUs::where('key', 'phone4')->first()->value;
        $email4           = AboutUs::where('key', 'email4')->first()->value;
        $facebook         = AboutUs::where('key', 'facebook')->first()->value;
        $instagram        = AboutUs::where('key', 'instagram')->first()->value;
        $twitter          = AboutUs::where('key', 'twitter')->first()->value;
        $tiktok           = AboutUs::where('key', 'tiktok')->first()->value;
        $linked_in        = AboutUs::where('key', 'linked-in')->first()->value;
        $app_link_android = AboutUs::where('key', 'app-link-android')->first()->value;
        $app_link_IOS     = AboutUs::where('key', 'app-link-IOS')->first()->value;
        $website          = AboutUs::where('key', 'website')->first()->value;
        return view('dashboard.about_us.view', compact('app_link_IOS', 'website', 'description', 'phone1', 'email1', 'phone2', 'email2', 'phone3', 'email3', 'phone4', 'email4', 'facebook', 'twitter', 'instagram', 'tiktok', 'linked_in', 'app_link_android'));
    }

    public function update_about_us(Request $request)
    {
        AboutUs::where('key', 'description')->update(['value' => $request->description]);
        AboutUs::where('key', 'email1')->update(['value' => $request->email1]);
        AboutUs::where('key', 'phone1')->update(['value' => $request->phone1]);
        AboutUs::where('key', 'email2')->update(['value' => $request->email2]);
        AboutUs::where('key', 'phone2')->update(['value' => $request->phone2]);
        AboutUs::where('key', 'email3')->update(['value' => $request->email3]);
        AboutUs::where('key', 'phone3')->update(['value' => $request->phone3]);
        AboutUs::where('key', 'email4')->update(['value' => $request->email4]);
        AboutUs::where('key', 'phone4')->update(['value' => $request->phone4]);
        AboutUs::where('key', 'facebook')->update(['value' => $request->facebook]);
        AboutUs::where('key', 'instagram')->update(['value' => $request->instagram]);
        AboutUs::where('key', 'twitter')->update(['value' => $request->twitter]);
        AboutUs::where('key', 'linked-in')->update(['value' => $request->linked_in]);
        AboutUs::where('key', 'tiktok')->update(['value' => $request->tiktok]);
        AboutUs::where('key', 'app-link-android')->update(['value' => $request->app_link_android]);
        AboutUs::where('key', 'app-link-IOS')->update(['value' => $request->app_link_IOS]);
        AboutUs::where('key', 'website')->update(['value' => $request->website]);

        return redirect('/admin-dashboard/about_us/view')->with('success', 'About Us updated successfully.');
    }

    public function restartWebsocket()
    {
        try {
            $process = new Process(['sudo', 'supervisorctl', 'restart', 'ladydriver-websockets']);
            $process->run();

            if (! $process->isSuccessful()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $process->getErrorOutput(),
                ], 500);
            }

            return response()->json([
                'status'  => 'success',
                'message' => $process->getOutput(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

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
use App\Models\CarMark;
use Illuminate\Validation\Rule;
use Image;
use Str;
use File;

class CarMarkController extends ApiController
{
    public function index(Request $request)
    {
        $all_marks = CarMark::orderBy('id', 'desc');


        if ($request->has('search') && $request->search != null) {
            $all_marks->where(function ($query) use ($request) {
                $query->where('en_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ar_name', 'LIKE', '%' . $request->search . '%');
            });
        }
        $all_marks = $all_marks->paginate(12);
        $search = $request->search;
        return view('dashboard.car_marks.index', compact('all_marks', 'search'));

    }

    public function create()
    {
        return view('dashboard.car_marks.create');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'en_name' => ['required', 'string', 'max:191'],
            'ar_name' => ['required', 'string', 'max:191'],

        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }


        $mark=CarMark::create([
            'en_name' => $request->en_name,
            'ar_name' => $request->ar_name

        ]);
        if($request->new_models&&count($request->new_models)>0){
            foreach($request->new_models as $model){
                CarModel::create([
                    'en_name' => $model,'ar_name' => $model,
                    'car_mark_id' => $mark->id
                ]);
            }
        }

        return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark created successfully.');

    }

    public function edit($id)
    {
        $mark = CarMark::where('id', $id)->first();
        $models=CarModel::where('car_mark_id',$id)->get();
        return view('dashboard.car_marks.edit', compact('mark','models'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'en_name' => ['required', 'string', 'max:191'],
            'ar_name' => ['required', 'string', 'max:191'],

        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        CarMark::where('id', $id)->update([ 'en_name' => $request->en_name,
                'ar_name' => $request->ar_name
            ]);
        $oldArrayIDs=[];
        if($request->old_models&&count($request->old_models)>0){
            foreach($request->old_models as $key=> $old_model){
                CarModel::where('id', $key)->update([ 'en_name' => $old_model,'ar_name' => $old_model]);
                $oldArrayIDs[]=$key;
            }
            CarModel::whereNotIn('id', $oldArrayIDs)->delete();
        }else{
            CarModel::where('car_mark_id', $id)->delete();
        }

        if($request->new_models&&count($request->new_models)>0){
            foreach($request->new_models as $model){
                CarModel::create([
                    'en_name' => $model,'ar_name' => $model,
                    'car_mark_id' => $id
                ]);
            }
        }
        return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark updated successfully.');

    }

    public function delete($id)
    {
        $mark = CarMark::findOrFail($id);
        CarModel::where('car_mark_id', $id)->delete();
        // If no employees are assigned to the department, proceed with deleting the department
        $mark->delete();

        return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark deleted successfully.');
    }
}

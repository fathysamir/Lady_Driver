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
use App\Models\MotorcycleModel;
use App\Models\MotorcycleMark;
use Illuminate\Validation\Rule;
use Image;
use Str;
use File;

class MotorcycleController extends ApiController
{
    public function index(Request $request)
    {
        $all_marks = MotorcycleMark::orderBy('id', 'desc');


        if ($request->has('search') && $request->search != null) {
            $all_marks->where(function ($query) use ($request) {
                $query->where('en_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ar_name', 'LIKE', '%' . $request->search . '%');
            });
        }
        $all_marks = $all_marks->paginate(12);
        $search = $request->search;
        return view('dashboard.motorcycles.index', compact('all_marks', 'search'));

    }

    public function create()
    {
        return view('dashboard.motorcycles.create');
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


        $mark=MotorcycleMark::create([
            'en_name' => $request->en_name,
            'ar_name' => $request->ar_name

        ]);
        if($request->new_models&&count($request->new_models)>0){
            foreach($request->new_models as $model){
                MotorcycleModel::create([
                    'en_name' => $model,'ar_name' => $model,
                    'motorcycle_mark_id' => $mark->id
                ]);
            }
        }

        return redirect('/admin-dashboard/motorcycles')->with('success', 'Motorcycle Mark created successfully.');

    }

    public function edit($id)
    {
        $mark = MotorcycleMark::where('id', $id)->first();
        $models=MotorcycleModel::where('motorcycle_mark_id',$id)->get();
        return view('dashboard.motorcycles.edit', compact('mark','models'));
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

        MotorcycleMark::where('id', $id)->update([ 'en_name' => $request->en_name,
                'ar_name' => $request->ar_name
            ]);
        $oldArrayIDs=[];
        if($request->old_models&&count($request->old_models)>0){
            foreach($request->old_models as $key=> $old_model){
                MotorcycleModel::where('id', $key)->update([ 'en_name' => $old_model,'ar_name' => $old_model]);
                $oldArrayIDs[]=$key;
            }
            MotorcycleModel::whereNotIn('id', $oldArrayIDs)->where('motorcycle_mark_id', $id)->delete();
        }else{
            MotorcycleModel::where('motorcycle_mark_id', $id)->delete();
        }

        if($request->new_models&&count($request->new_models)>0){
            foreach($request->new_models as $model){
                MotorcycleModel::create([
                    'en_name' => $model,'ar_name' => $model,
                    'motorcycle_mark_id' => $id
                ]);
            }
        }
        return redirect('/admin-dashboard/motorcycles')->with('success', 'Motorcycle Mark updated successfully.');

    }

    public function delete($id)
    {
        $mark = MotorcycleMark::findOrFail($id);
        MotorcycleModel::where('motorcycle_mark_id', $id)->delete();
        // If no employees are assigned to the department, proceed with deleting the department
        $mark->delete();

        return redirect('/admin-dashboard/motorcycles')->with('success', 'Motorcycle Mark deleted successfully.');
    }
}

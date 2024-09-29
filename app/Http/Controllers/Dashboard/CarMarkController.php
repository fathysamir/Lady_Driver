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

        if ($request->has('search') && $request->search!=null) {
            $all_marks->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $all_marks = $all_marks->paginate(12);
        return view('dashboard.car_marks.index',compact('all_marks'));

    }

    public function create(){
        return view('dashboard.car_marks.create');
    }

    public function store(Request $request){

            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:191'],

            ]);

           
            if ($validator->fails()) {
                return Redirect::back()->withInput()->withErrors($validator);
            }
            
            
            CarMark::create([
                'name' => $request->name

            ]);
           
          return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark created successfully.');

    }

    public function edit($id){
        $mark=CarMark::where('id',$id)->first();
        return view('dashboard.car_marks.edit',compact('mark'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],

        ]);

       
        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
   
        CarMark::where('id',$id)->update([ 'name' => $request->name
            ]);
        return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark updated successfully.');

    }

    public function delete($id){
        $mark = CarMark::findOrFail($id);
        CarModel::where('car_mark_id',$id)->delete();
        // If no employees are assigned to the department, proceed with deleting the department
        $mark->delete();
        
        return redirect('/admin-dashboard/car-marks')->with('success', 'Car Mark deleted successfully.');
    }
}
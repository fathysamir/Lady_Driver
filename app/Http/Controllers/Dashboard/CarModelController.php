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
use App\Models\User;
use App\Models\CarMark;
use App\Models\CarModel;
use Illuminate\Validation\Rule;
use Image;
use Str;
use File;

class CarModelController extends ApiController
{
    public function index(Request $request)
    {
        $all_models = CarModel::orderBy('id', 'desc');

        if ($request->has('search') && $request->search != null) {
            $all_models->where(function ($query) use ($request) {
                $query->where('en_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ar_name', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->has('mark') && $request->mark != null) {
            $all_models->where('car_mark_id', $request->mark);
        }
        $all_models = $all_models->paginate(12);
        $marks = CarMark::all();
        $search = $request->search;
        return view('dashboard.car_models.index', compact('all_models', 'marks', 'search'));

    }

    public function create()
    {
        $marks = CarMark::all();
        return view('dashboard.car_models.create', compact('marks'));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'en_name' => ['required', 'string', 'max:191'],
            //'ar_name' => ['required', 'string', 'max:191'],
            'car_mark' => ['required',Rule::in(CarMark::pluck('id'))]
        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }


        CarModel::create([
            'en_name' => $request->en_name,'ar_name' => $request->en_name,
            'car_mark_id' => $request->car_mark
        ]);

        return redirect('/admin-dashboard/car-models')->with('success', 'Car Model created successfully.');

    }

    public function edit($id)
    {
        $model = CarModel::where('id', $id)->first();
        $marks = CarMark::all();
        return view('dashboard.car_models.edit', compact('model', 'marks'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
           'en_name' => ['required', 'string', 'max:191'],
            //'ar_name' => ['required', 'string', 'max:191'],
            'car_mark' => ['required',Rule::in(CarMark::pluck('id'))]
        ]);


        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        CarModel::where('id', $id)->update([ 'en_name' => $request->en_name,'ar_name' => $request->en_name,'car_mark_id' => $request->car_mark
            ]);
        return redirect('/admin-dashboard/car-models')->with('success', 'Car Model updated successfully.');

    }

    public function delete($id)
    {
        $model = CarModel::findOrFail($id);

        // If no employees are assigned to the department, proceed with deleting the department
        $model->delete();

        return redirect('/admin-dashboard/car-models')->with('success', 'Car Model deleted successfully.');
    }
}

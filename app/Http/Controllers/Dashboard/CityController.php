<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $cities = City::orderBy('id', 'desc');
        if ($request->has('search') && $request->search != null) {
            $cities->where('name', 'LIKE', '%' . $request->search . '%');
        }
        $cities = $cities->paginate(1);
        $search = $request->search;

        return view('dashboard.cities.index', compact('cities', 'search'));
    }

    public function create(Request $request)
    {
        $queryString = $request->query();
        return view('dashboard.cities.create', compact('queryString'));

    }
    public function edit(Request $request, $id)
    {
        $city        = City::findOrFail($id);
        $queryString = $request->query();

        return view('dashboard.cities.edit', compact('city', 'queryString'));

    }
    public function store(Request $request)
    {
        City::create(['name'=> $request->name]);
        $queryParams = $request->except(['_token', '_method', 'name']);
        return redirect()->route('cities', $queryParams)->with('success', 'City created successfully!');

    }
    public function update(Request $request, $id)
    {
        City::where('id', $id)->update(['name'=> $request->name]);
        $queryParams = $request->except(['_token', '_method', 'name']);
        return redirect()->route('cities', $queryParams)->with('success', 'City updated successfully!');

    }
}

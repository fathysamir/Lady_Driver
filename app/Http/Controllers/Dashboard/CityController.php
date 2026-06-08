<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
{
    $cities = City::select('cities.*')
        ->selectSub(
            \DB::table('users')
                ->selectRaw('COUNT(*)')
                ->whereColumn('users.city_id', 'cities.id')
                ->where('users.mode', 'client')
                ->where('users.is_verified', '1')
                ->whereNull('users.student_code')
                ->whereNull('users.deleted_at'),
            'clients_count'
        )
        ->selectSub(
            \DB::table('users')
                ->selectRaw('COUNT(*)')
                ->whereColumn('users.city_id', 'cities.id')
                ->where('users.mode', 'driver')
                ->where('users.is_verified', '1')
                ->whereNull('users.deleted_at'),
            'drivers_count'
        )
        ->whereNull('cities.deleted_at')
        ->orderBy('cities.id', 'desc');

    if ($request->has('search') && $request->search != null) {
        $cities->where('cities.name', 'LIKE', '%' . $request->search . '%');
    }

    $cities = $cities->paginate(10);
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
        $request->validate([
            'name' => 'required|string|max:255|unique:cities,name',
        ]);

        City::create(['name' => $request->name]);

        $queryParams = $request->except(['_token', '_method', 'name']);
        return redirect()->route('cities', $queryParams)->with('success', 'City created successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:cities,name,' . $id,
        ]);

        City::where('id', $id)->update(['name' => $request->name]);

        $queryParams = $request->except(['_token', '_method', 'name']);
        return redirect()->route('cities', $queryParams)->with('success', 'City updated successfully!');
    }

    public function delete($id, Request $request)
    {
        City::where('id', $id)->delete();

        $queryParams = $request->except(['_token', '_method']);
        return redirect()->route('cities', $queryParams)->with('success', 'City deleted successfully!');
    }
}
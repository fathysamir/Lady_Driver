<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RateTripSetting;

class RatingTripSettingsController extends Controller
{
    public function index(Request $request)
{
    $page = $request->get('page', 1);
    session(['rating_current_page' => $page]);

    $query = RateTripSetting::query();

    // Searching
    if ($search = request('search')) {
        $query->where('label', 'like', "%{$search}%");
        $query->orWhere('category', 'like', "%{$search}%");
    }

    // Filtering
    if ($category = request('category')) {
        $query->where('category', $category);
    }
    if ($star_count = request('star_count')) {
        $query->where('star_count', $star_count);
    }

    $settings = $query->paginate(10);

    return view('dashboard.rating_trip_settings.index', compact('settings', 'page', 'search', 'category', 'star_count'));
}

    public function create()
    {
        return view('dashboard.rating_trip_settings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'star_count' => 'required|integer|min:1|max:5',
            'category' => 'required|in:driver,client',
        ]);

        RateTripSetting::create($request->only('label', 'star_count', 'category'));

        // Get the current page
        $returnPage = session('rating_current_page', 1);

        return redirect()->route('ratingtripsettings', ['page' => $returnPage])
                         ->with('success', 'Setting created successfully!');
    }

    public function edit($id)
    {
        $setting = RateTripSetting::findOrFail($id);

        session(['rating_return_page' => session('rating_current_page', 1)]);

        return view('dashboard.rating_trip_settings.edit', compact('setting'));
    }

    public function update(Request $request, $id)
    {
        $setting = RateTripSetting::findOrFail($id);

        $request->validate([
            'label' => 'required|string|max:255',
            'star_count' => 'required|integer|min:1|max:5',
            'category' => 'required|in:driver,client',
        ]);

        $setting->update($request->only('label', 'star_count', 'category'));

        $returnPage = session('rating_return_page', 1);

        session()->forget('rating_return_page');

        return redirect()->route('ratingtripsettings', ['page' => $returnPage])
                         ->with('success', 'Setting updated successfully!');
    }

    public function destroy($id)
    {
        $setting = RateTripSetting::findOrFail($id);
        $setting->delete();

        $returnPage = session('rating_current_page', 1);

        return redirect()->route('ratingtripsettings', ['page' => $returnPage])
                         ->with('success', 'Setting deleted successfully!');
    }
}
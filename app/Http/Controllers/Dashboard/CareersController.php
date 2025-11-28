<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Careers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class CareersController extends Controller
{
    public function index(Request $request)
{
    $careers = Careers::query();


    // Search
    if ($request->filled('search')) {
        $search = $request->search;
        $keywords = explode(' ', $search);
        $careers->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $q->where(function ($q2) use ($word) {
                    $q2->where('first_name', 'LIKE', '%' . $word . '%')
                       ->orWhere('last_name', 'LIKE', '%' . $word . '%')
                       ->orWhere('email', 'LIKE', '%' . $word . '%')
                       ->orWhere('phone', 'LIKE', '%' . $word . '%')
                       ->orWhere('position', 'LIKE', '%' . $word . '%');
                });
            }
        });
    }

    // Filter by position
    if ($request->filled('position')) {
        $careers->where('position', $request->position);
    }

    // Filter by status
    if ($request->filled('status')) {
        $careers->where('status', $request->status);
    }

    // Pagination
    $careers = $careers->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

    // Positions for filter dropdown
    $positions = Careers::select('position')->distinct()->pluck('position');

    $count = $careers->total();
    $search = $request->search;
    $status = $request->status;


    return view('dashboard.careers.index', compact('careers', 'count', 'positions', 'search', 'status'));
}



    public function show(Request $request, $id)
    {
        $career = Careers::findOrFail($id);
        $career->cv = getFirstMediaUrl($career, $career->CvCollection);
        $queryString = $request->query();

        return view('dashboard.careers.show', compact('career', 'queryString'));
    }


    public function update(Request $request, $id)
{

    $career = Careers::findOrFail($id);
    $request->validate([
        'status' => 'required|in:pending,confirmed,banned,blocked',
    ]);

    $career->status = $request->status;
    $career->save();

    return redirect()->route('view.career', $id)
                     ->with('success', 'Status updated successfully.');
}


public function index_archives(Request $request)
{

    $query = Careers::onlyTrashed();
    $type = 'archives';
    $title = 'Archived Careers';
    if ($request->has('search') && $request->search != null) {
        $query->where(function ($q) use ($request) {
            $q->where('first_name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('last_name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('email', 'LIKE', '%' . $request->search . '%');
        });
    }
    $query->orderBy('created_at', 'desc');
    $all_users = $query->paginate(12)->withQueryString();
    $count = $all_users->total();
    $search = $request->search;

    return view('dashboard.careers.index_archives', compact('all_users', 'count', 'search', 'type', 'title'));
}


public function delete($id)
{
    $career = Careers::findOrFail($id);
    $career->delete();
    return redirect()->back()->with('success', 'Career deleted successfully.');
}


    public function restore($id)
    {
        $career = Careers::onlyTrashed()->findOrFail($id);
        $career->restore();
        return redirect()->back()->with('success', 'Career restored successfully.');
    }
}

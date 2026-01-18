<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;

class FAQController extends Controller
{
    public function index(Request $request)
    {
        $FAQs = FAQ::orderBy('id', 'desc');
        if ($request->has('search') && $request->search != null) {
            $search = $request->search;

            $FAQs->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                    ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }
        $FAQs   = $FAQs->paginate(10);
        $search = $request->search;

        return view('dashboard.FAQs.index', compact('FAQs', 'search'));
    }

    public function create(Request $request)
    {
        $queryString = $request->query();
        return view('dashboard.FAQs.create', compact('queryString'));

    }
    public function edit(Request $request, $id)
    {
        $FAQ         = FAQ::findOrFail($id);
        $queryString = $request->query();

        return view('dashboard.FAQs.edit', compact('FAQ', 'queryString'));

    }
    public function store(Request $request)
    {
        $FAQ = FAQ::create(['question' => $request->question, 'answer' => $request->answer, 'type' => $request->category]);
        if ($request->is_active) {
            $FAQ->is_active = '1';
        } else {
            $FAQ->is_active = '0';
        }
        $FAQ->save();
        $queryParams = $request->except(['_token', '_method', 'question','answer','type','is_active']);
        return redirect()->route('FAQs', $queryParams)->with('success', 'FAQ created successfully!');

    }
    public function update(Request $request, $id)
    {
        FAQ::where('id', $id)->update(['question' => $request->question, 'answer' => $request->answer, 'type' => $request->category]);
        $FAQ = FAQ::findOrFail($id);
        if ($request->is_active) {
            $FAQ->is_active = '1';
        } else {
            $FAQ->is_active = '0';
        }
        $FAQ->save();
        $queryParams = $request->except(['_token', '_method', 'question','answer','type','is_active']);
        return redirect()->route('FAQs', $queryParams)->with('success', 'FAQ updated successfully!');

    }
    public function delete($id, Request $request)
    {
        FAQ::where('id', $id)->delete();
        $queryParams = $request->except(['_token', '_method']);
        return redirect()->route('FAQs', $queryParams)->with('success', 'FAQ deleted successfully!');

    }
}

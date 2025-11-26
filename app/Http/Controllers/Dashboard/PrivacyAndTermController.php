<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PrivacyAndTerm;

class PrivacyAndTermController extends Controller
{
    /////////////////////////////////////////// Privacy Policy ///////////////////////////////////////////

    public function edit()
    {
        return view('dashboard.privacy_policy');
    }

    public function getByLang($lang)
    {
        $privacy = PrivacyAndTerm::firstOrCreate([
            'type' => 'privacy',
            'lang' => $lang
        ]);

        return response()->json($privacy);
    }

    public function update(Request $request)
    {
        $request->validate([
            'lang' => 'required|string|in:en,ar',
            'value' => 'required|string',
        ]);

        PrivacyAndTerm::updateOrCreate(
            ['type' => 'privacy', 'lang' => $request->lang],
            ['value' => $request->value]
        );

        return response()->json(['success' => true, 'message' => 'Updated successfully!']);
    }

/////////////////////////////////////////////// Terms and Conditions ///////////////////////////////////////////


public function termsedit()
{
   
    return view('dashboard.terms_conditions');
}

public function termsgetByLang($lang)
{
    $privacy = PrivacyAndTerm::firstOrCreate([
        'type' => 'terms',
        'lang' => $lang
    ]);

    return response()->json($privacy);
}

public function termsupdate(Request $request)
{
    $request->validate([
        'lang' => 'required|string|in:en,ar',
        'value' => 'required|string',
    ]);

    PrivacyAndTerm::updateOrCreate(
        ['type' => 'terms', 'lang' => $request->lang],
        ['value' => $request->value]
    );

    return response()->json(['success' => true, 'message' => 'Updated successfully!']);
}
}

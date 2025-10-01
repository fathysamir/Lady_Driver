<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\LiveLocation;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveLocationController extends ApiController
{

    public function create(Request $request)
    {
        $user = auth()->user();

        $token = null;

        do {
            $token = Str::random(32);
        } while (LiveLocation::where('token', $token)->exists());

        $live = LiveLocation::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => now()->addHours(2),
        ]);

        $link = url("/live-location/{$token}");

        return $this->sendResponse([
            'link'       => $link,
            'expires_at' => $live->expires_at,
        ], null, 200);
    }

    // تحديث الإحداثيات
    public function update(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $live = LiveLocation::where('user_id', auth()->id())
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $live) {
            return $this->sendError(null, 'No active share found', 404);
        }

        $live->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);
        $user = auth()->user();
        $user->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return $this->sendResponse(null, 'Location updated', 200);
    }

    // API: get location JSON
    public function getLocation($token)
    {
        $live = LiveLocation::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $live) {
            return response()->json(['error' => 'Expired or invalid link'], 404);
        }
        $user = User::findOrFail($live->user_id);
        $trip = Trip::where('user_id', $live->user_id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['finalDestination' => function ($q) {
                $q->orderBy('id'); // أو order by ترتيب النقاط
            }])->first();

        return response()->json([
            'lat'  => $live->lat,
            'lng'  => $live->lng,
            'user' => $live->user->name,
            'trip' => $trip,
        ]);
    }

    // صفحة الخريطة العامة
    public function viewPage2($token)
    {
        return view('live-location', ['token' => $token]);
    }
    public function viewPage($token)
    {
        $link = LiveLocation::where('token', $token)->firstOrFail();

        // optional: check expiration
        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410, 'Link expired');
        }
        $lat         = $link->lat ?? null;
        $lng         = $link->lng ?? null;
        $fallbackUrl = $lat && $lng
            ? "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}"
            : 'https://lady-driver.com/';

        return view('live_location2', compact('fallbackUrl', 'link'));
    }
}

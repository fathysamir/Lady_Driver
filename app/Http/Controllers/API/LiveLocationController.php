<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Models\LiveLocation;
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
        ],null,200);
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
            return $this->sendError(null,'No active share found', 404);
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

        return $this->sendResponse(null,'Location updated',200);
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

        return response()->json([
            'lat'  => $live->lat,
            'lng'  => $live->lng,
            'user' => $live->user->name,
        ]);
    }

    // صفحة الخريطة العامة
    public function viewPage($token)
    {
        return view('live-location', ['token' => $token]);
    }
}

<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Car;
use App\Models\CarMark;
use App\Models\Trip;
use App\Models\CarModel;
use App\Models\MotorcycleMark;
use App\Models\MotorcycleModel;
use App\Models\Scooter;
use Image;
use Str;
use File;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $all_trips = Trip::orderBy('id', 'desc')
            ->whereIn('status', [
                'scheduled',
                'pending',
                'in_progress',
                'completed',
                'cancelled'
            ]);


        if ($request->filled('type')) {
            $all_trips->where('type', $request->type);
        }

        if ($request->filled('time_filter')) {
            if ($request->time_filter === 'scheduled') {
                $all_trips->whereIn('status', ['pending', 'scheduled']);
            } elseif ($request->time_filter === 'current') {
                $all_trips->where('status', 'in_progress');
            } elseif ($request->time_filter === 'past') {
                $all_trips->whereIn('status', ['completed', 'cancelled']);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $all_trips->where(function ($q) use ($search, $request) {
                $q->where('code', 'LIKE', "%$search%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'LIKE', "%$search%");
                  });

                if ($request->type === 'scooter') {
                    $q->orWhereHas('scooter.owner', function ($s) use ($search) {
                        $s->where('name', 'LIKE', "%$search%");
                    });
                } else {
                    $q->orWhereHas('car.owner', function ($c) use ($search) {
                        $c->where('name', 'LIKE', "%$search%");
                    });
                }
            });
        }

        if ($request->filled('user')) {
            $all_trips->where('user_id', $request->user);
        }

        if ($request->filled('status')) {
            $all_trips->where('status', $request->status);
        }

        if ($request->filled('driver')) {
            if ($request->type === 'scooter') {
                $all_trips->whereHas('scooter', function ($q) use ($request) {
                    $q->where('user_id', $request->driver);
                });
            } else {
                $all_trips->whereHas('car', function ($q) use ($request) {
                    $q->where('user_id', $request->driver);
                });
            }
        }


        $all_trips = $all_trips
            ->paginate(12)
            ->appends($request->query());

        $drivers = User::whereHas('roles', fn($q) =>
            $q->where('name', 'Driver')
        )->get();

        $users = User::whereHas('roles', fn($q) =>
            $q->where('name', 'Client')
        )->get();

        return view('dashboard.trips.index', compact(
            'all_trips',
            'drivers',
            'users'
        ));
    }

    public function view($id)
    {
        $trip = Trip::where('id', $id)->first();
        $trip->user->image = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);

        // Handle both car and scooter
        if ($trip->type === 'scooter' && $trip->scooter) {
            $trip->scooter->image = getFirstMediaUrl($trip->scooter, $trip->scooter->avatarCollection);
            $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
        } else if ($trip->car) {
            $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
            $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
        }
        $destinations=$trip->finalDestination;

        return view('dashboard.trips.view', compact('trip','destinations'));
    }


    public function getMotorcycleModels(Request $request)
    {
        $models = MotorcycleModel::where('motorcycle_mark_id', $request->markId)->get();
        return response()->json($models);
    }
    public function getScooterLocation($id)
{
    $scooter = Scooter::find($id);
    if ($scooter) {
        return response()->json([
            'lat' => $scooter->lat,
            'lng' => $scooter->lng
        ]);
    }
    return response()->json(['error' => 'Scooter not found'], 404);
}
public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:created,scheduled,pending,in_progress,completed,cancelled,expired'
    ]);

    $trip = Trip::findOrFail($id);
    $trip->status = $request->status;
    $trip->save();
    $trip->load(['user', 'car', 'scooter']);

    $driverUserId = null;
    if ($trip->car_id && $trip->car) {
        $driverUserId = $trip->car->user_id;
    } elseif ($trip->scooter_id && $trip->scooter) {
        $driverUserId = $trip->scooter->user_id;
    }

    switch ($request->status) {

        //Laravel Broadcast Events
        case 'in_progress':
            event(new \App\Events\TripStarted($trip, $trip->user_id));
            if ($driverUserId) event(new \App\Events\TripStarted($trip, $driverUserId));
            break;

        case 'completed':
            event(new \App\Events\TripEnded($trip, $trip->user_id));
            if ($driverUserId) event(new \App\Events\TripEnded($trip, $driverUserId));
            break;

        //  Redis directly like Chat.php
        default:
            $payload = [
                'type'    => $this->resolveSocketType($request->status),
                'data'    => $trip,
                'message' => 'Trip status updated by admin',
            ];
            $this->publishToRedis($trip->user_id, $payload);
            if ($driverUserId) $this->publishToRedis($driverUserId, $payload);
            break;
    }

    return back()->with('success', 'Status updated and socket sent.');
}

private function resolveSocketType(string $status): string
{
    return match($status) {
        'cancelled' => 'canceled_trip',
        'pending'   => 'accepted_offer',
        'expired'   => 'expired_trip',
        default     => 'trip_status_updated',
    };
}

private function publishToRedis(int $userId, array $payload): void
{
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $channel = "trip.status.{$userId}";
    $redis->publish($channel, json_encode([
        'event' => $payload['type'],
        'data'  => $payload,
    ]));
}
}
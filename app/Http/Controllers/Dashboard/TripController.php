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
        // ── Resolve active tab state ──────────────────────────────
        $defaultType = 'car';
if (auth()->user()->hasRole('Moderator Comfort')) {
    $defaultType = 'comfort_car';
} elseif (auth()->user()->hasRole('Moderator Scooter')) {
    $defaultType = 'scooter';
}
$type = $request->input('type', $defaultType);
        $time_filter = $request->input('time_filter', 'current'); // scheduled | current | past

        // ── Base query ────────────────────────────────────────────
        $query = Trip::orderBy('id', 'desc')
            ->where('type', $type);

        // ── Time filter maps to status ────────────────────────────
        if ($time_filter === 'scheduled') {
            $query->whereIn('status', ['pending', 'scheduled']);
        } elseif ($time_filter === 'current') {
            $query->where('status', 'in_progress');
        } elseif ($time_filter === 'past') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } else {
            $query->whereIn('status', ['scheduled', 'pending', 'in_progress', 'completed', 'cancelled']);
        }

        // ── Search ────────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $type) {
                $q->where('code', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('phone', 'LIKE', '%' . $search . '%');
                  });

                if ($type === 'scooter') {
                    $q->orWhereHas('scooter.owner', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', '%' . $search . '%')
                           ->orWhere('phone', 'LIKE', '%' . $search . '%');
                    });
                } else {
                    $q->orWhereHas('car.owner', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', '%' . $search . '%')
                           ->orWhere('phone', 'LIKE', '%' . $search . '%');
                    });
                }
            });
        }

        // ── Extra filters ─────────────────────────────────────────
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('air_conditioned') && $type === 'car') {
            $query->where('air_conditioned', '1');
        }

        if ($request->filled('driver')) {
            if ($type === 'scooter') {
                $query->whereHas('scooter', fn($q) => $q->where('user_id', $request->driver));
            } else {
                $query->whereHas('car', fn($q) => $q->where('user_id', $request->driver));
            }
        }

        if ($request->filled('mark')) {
            if ($type === 'scooter') {
                $query->whereHas('scooter', fn($q) => $q->where('motorcycle_mark_id', $request->mark));
            } else {
                $query->whereHas('car', fn($q) => $q->where('car_mark_id', $request->mark));
            }
        }

        if ($request->filled('model')) {
            if ($type === 'scooter') {
                $query->whereHas('scooter', fn($q) => $q->where('motorcycle_model_id', $request->model));
            } else {
                $query->whereHas('car', fn($q) => $q->where('car_model_id', $request->model));
            }
        }

        // ── Paginate (25 per page, preserve all query params) ─────
        $all_trips = $query->paginate(25)->withQueryString();

      // ── Supporting data for filters ───────────────────────────
$drivers = User::whereHas('roles', fn($q) => $q->where('roles.name', 'Driver'))->get();
$users   = User::whereHas('roles', fn($q) => $q->where('roles.name', 'Client'))->get();

$car_marks        = CarMark::all();
$motorcycle_marks = MotorcycleMark::all();
$search           = $request->search;

$driverName = null;
if ($request->filled('driver')) {
    $driverName = User::find($request->driver)?->name;
}

return view('dashboard.trips.index', compact(
    'all_trips',
    'drivers',
    'users',
    'car_marks',
    'motorcycle_marks',
    'search',
    'type',
    'time_filter',
    'driverName'
));
    }

    public function view($id)
    {
        $trip = Trip::where('id', $id)->first();
        $trip->user->image = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);

        if ($trip->type === 'scooter' && $trip->scooter) {
            $trip->scooter->image = getFirstMediaUrl($trip->scooter, $trip->scooter->avatarCollection);
            $trip->scooter->owner->image = getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection);
        } else if ($trip->car) {
            $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
            $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
        }
        $destinations = $trip->finalDestination;

        return view('dashboard.trips.view', compact('trip', 'destinations'));
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
        switch ($request->status) {
            case 'in_progress':
                $trip->start_date = now()->toDateString();
                $trip->start_time = now()->toTimeString();
                $trip->driver_arrived = now();
                break;

            case 'completed':
                $trip->end_date = now()->toDateString();
                $trip->end_time = now()->toTimeString();
                break;

            case 'cancelled':
                $trip->end_date = now()->toDateString();
                $trip->end_time = now()->toTimeString();
                $trip->cancelled_by_id = Auth::id();
                break;

            case 'expired':
                $trip->end_date = now()->toDateString();
                $trip->end_time = now()->toTimeString();
                break;


            // created, scheduled, pending → no timestamp needed
        }

        $trip->save();
        $trip->load(['user', 'car', 'scooter']);

        $driverUserId = null;
        if ($trip->car_id && $trip->car) {
            $driverUserId = $trip->car->user_id;
        } elseif ($trip->scooter_id && $trip->scooter) {
            $driverUserId = $trip->scooter->user_id;
        }

        switch ($request->status) {
            case 'in_progress':
                event(new \App\Events\TripStarted($trip, $trip->user_id));
                if ($driverUserId) event(new \App\Events\TripStarted($trip, $driverUserId));
                break;

            case 'completed':
                event(new \App\Events\TripEnded($trip, $trip->user_id));
                if ($driverUserId) event(new \App\Events\TripEnded($trip, $driverUserId));
                break;

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
        $redis   = \Illuminate\Support\Facades\Redis::connection();
        $channel = "trip.status.{$userId}";
        $redis->publish($channel, json_encode([
            'event' => $payload['type'],
            'data'  => $payload,
        ]));
    }
}
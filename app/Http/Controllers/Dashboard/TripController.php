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
use Image;
use Str;
use File;

class TripController extends Controller
{//done
    public function index(Request $request)
    {
        $all_trips = Trip::orderBy('id', 'desc')
            ->whereIn('status', ['pending', 'in_progress', 'completed', 'cancelled']);

        // Filter by trip type (standard, comfort, scooter)
        $tripType = $request->input('trip_type', 'standard');
        $all_trips->where('trip_type', $tripType);

        // Filter by time category (scheduled, current, past)
        $timeFilter = $request->input('time_filter', 'current');
        if ($timeFilter === 'scheduled') {
            $all_trips->where('status', 'pending');
        } elseif ($timeFilter === 'current') {
            $all_trips->where('status', 'in_progress');
        } elseif ($timeFilter === 'past') {
            $all_trips->whereIn('status', ['completed', 'cancelled']);
        }

        // Search by code, client name, or driver name
        if ($request->filled('search')) {
            $search = $request->search;
            $all_trips->where(function($query) use ($search) {
                $query->where('code', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'LIKE', '%' . $search . '%');
                      })
                      ->orWhereHas('car.owner', function($q) use ($search) {
                          $q->where('name', 'LIKE', '%' . $search . '%');
                      });
            });
        }

        // Filter by client
        if ($request->filled('user')) {
            $all_trips->where('user_id', $request->user);
        }

        // Filter by created date
        if ($request->filled('created_date')) {
            $all_trips->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('type')) {
            $all_trips->where('type', $request->type);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $all_trips->where('payment_status', $request->payment_status);
        }

        // Filter by status dropdown (overrides time_filter if set)
        if ($request->filled('status')) {
            $all_trips->where('status', $request->status);
        }

        // Filter by air_conditioned
        if ($request->filled('air_conditioned')) {
            $all_trips->where('air_conditioned', '1');
        }

        // Filter by driver (car owner)
        if ($request->filled('driver')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('user_id', $request->driver);
            });
        }

        // Filter by car mark
        if ($request->filled('mark')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_mark_id', $request->mark);
            });
        }

        // Filter by car model
        if ($request->filled('model')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_model_id', $request->model);
            });
        }

        // Paginate results
        $all_trips = $all_trips->paginate(12)->withQueryString();

        // Get drivers (users with role Client and mode driver)
        $drivers = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->where('mode', 'driver')->get();

        // Get clients
        $users = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->get();

        // Car marks
        $car_marks = CarMark::all();

        // Search term
        $search = $request->search;

        return view('dashboard.trips.index', compact('all_trips', 'drivers', 'users', 'car_marks', 'search'));
    }


    public function view($id)
    {
        $trip = Trip::where('id', $id)->first();
        $trip->user->image = getFirstMediaUrl($trip->user, $trip->user->avatarCollection);
        $trip->car->image = getFirstMediaUrl($trip->car, $trip->car->avatarCollection);
        $trip->car->owner->image = getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection);
        return view('dashboard.trips.view', compact('trip'));
    }


}

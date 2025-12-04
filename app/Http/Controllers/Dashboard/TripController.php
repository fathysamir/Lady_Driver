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
            ->whereIn('status', ['scheduled', 'pending', 'in_progress', 'completed', 'cancelled']);

        if ($request->filled('type')) {
            $all_trips->where('type', $request->type);
        }

        if ($request->filled('time_filter')) {
            $time = $request->time_filter;

            if ($time === 'scheduled') {
                $all_trips->whereIn('status', ['pending', 'scheduled']);
            } elseif ($time === 'current') {
                $all_trips->where('status', 'in_progress');
            } elseif ($time === 'past') {
                $all_trips->whereIn('status', ['completed', 'cancelled']);
            }
        }

        //search
        if ($request->filled('search')) {
            $search = $request->search;

            $all_trips->where(function ($query) use ($search) {
                $query->where('code', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('car.owner', function ($q) use ($search) {
                        $q->where('name', 'LIKE', '%' . $search . '%');
                    });
            });
        }


        // Client filter
        if ($request->filled('user')) {
            $all_trips->where('user_id', $request->user);
        }

        // Created date
        if ($request->filled('created_date')) {
            $all_trips->whereDate('created_at', $request->created_date);
        }

        // Payment status
        if ($request->filled('payment_status')) {
            $all_trips->where('payment_status', $request->payment_status);
        }

        // Status dropdown
        if ($request->filled('status')) {
            $all_trips->where('status', $request->status);
        }

        // Air conditioned
        if ($request->filled('air_conditioned')) {
            $all_trips->where('air_conditioned', '1');
        }

        // Driver filter
        if ($request->filled('driver')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('user_id', $request->driver);
            });
        }

        // Car mark
        if ($request->filled('mark')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_mark_id', $request->mark);
            });
        }

        // Car model
        if ($request->filled('model')) {
            $all_trips->whereHas('car', function ($query) use ($request) {
                $query->where('car_model_id', $request->model);
            });
        }

        $all_trips = $all_trips->paginate(12)->withQueryString();

        $drivers = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Driver');
        })->get();

        $users = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->get();

        $car_marks = CarMark::all();
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

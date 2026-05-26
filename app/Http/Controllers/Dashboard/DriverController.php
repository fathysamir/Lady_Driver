<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\City;
use App\Models\DriverLicense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Image;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $all_users = User::where('mode', 'driver')->where('is_verified', '1');

        if ($request->type == 'cars') {
            $all_users->where('driver_type', 'car');
        } elseif ($request->type == 'comfort_cars') {
            $all_users->where('driver_type', 'comfort_car');
        } elseif ($request->type == 'scooters') {
            $all_users->where('driver_type', 'scooter');
        }

        $all_users->orderBy('created_at', 'desc')->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

        if ($request->has('search') && $request->search != null) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status != null) {
            $all_users->where('status', $request->status);
        }

        if ($request->has('city') && $request->city != null) {
            $all_users->where('city_id', $request->city);
        }

        if ($request->filled('online') && $request->online !== null) {
            $all_users->where('is_online', $request->online);
        }

        $count     = $all_users->count();
        $all_users = $all_users->paginate(25);

        $all_users->getCollection()->transform(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });

        $cities = City::all();
        $search = $request->search;
        $status = $request->status;
        $city   = $request->city;
        $online = $request->online;
        $type   = $request->type;

        return view('dashboard.drivers.index', compact('all_users', 'cities', 'status', 'count', 'city', 'search', 'online', 'type'));
    }

    public function index_archives(Request $request)
    {
        $all_users = User::withTrashed()->where('mode', 'driver');

        if ($request->type == 'cars') {
            $all_users->whereHas('car', function ($q) {
                $q->where('is_comfort', '0');
            });
        } elseif ($request->type == 'comfort_cars') {
            $all_users->whereHas('car', function ($q) {
                $q->where('is_comfort', '1');
            });
        } elseif ($request->type == 'scooters') {
            $all_users->whereHas('scooter');
        }

        $all_users->orderBy('created_at', 'desc')->orderByRaw("LOWER(name) COLLATE utf8mb4_general_ci");

        if ($request->has('search') && $request->search != null) {
            $all_users->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('id', 'LIKE', '%' . $request->search . '%');
            });
        }

        $all_users->whereNotNull('deleted_at');
        $count     = $all_users->count();
        $all_users = $all_users->paginate(25);

        $all_users->getCollection()->transform(function ($user) {
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });

        $search = $request->search;
        $type   = $request->type;

        return view('dashboard.drivers.index_archives', compact('all_users', 'count', 'search', 'type'));
    }

    public function edit($id, Request $request)
    {
        $user                          = User::where('id', $id)->first();
        $user->seen                    = '1';
        $user->save();
        $user->image                   = getFirstMediaUrl($user, $user->avatarCollection);
        $user->IDfrontImage            = getFirstMediaUrl($user, $user->IDfrontImageCollection);
        $user->IDbackImage             = getFirstMediaUrl($user, $user->IDbackImageCollection);
        $user->PassportImage           = getFirstMediaUrl($user, $user->passportImageCollection);
        $user->medicalExaminationImage = getFirstMediaUrl($user, $user->medicalExaminationImageCollection);
        $user->criminalRecordImage     = getFirstMediaUrl($user, $user->criminalRecordImageCollection);
        $user->driving_license         = DriverLicense::where('user_id', $user->id)->first();
        $user->car                     = Car::where('user_id', $user->id)->first();

        if ($user->driving_license) {
            $user->driving_license->front_image = getFirstMediaUrl($user->driving_license, $user->driving_license->LicenseFrontImageCollection);
            $user->driving_license->back_image  = getFirstMediaUrl($user->driving_license, $user->driving_license->LicenseBackImageCollection);
        }

        $driverTrips = Trip::where(function ($query) use ($user) {
            $query->whereHas('car', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orWhereHas('scooter', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->where('status', 'completed');

        $user->rate        = round($driverTrips->clone()->where('client_stare_rate', '>', 0)->avg('client_stare_rate')) ?? 5.00;
        $user->trips_count = $driverTrips->count();

        $cities      = City::all();
        $queryString = $request->query();

        return view('dashboard.drivers.edit', compact('user', 'queryString', 'cities'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'       => ['required'],
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'country_code' => 'required',
            'phone'        => [
                'required',
                Rule::unique('users')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                        ->whereNull('deleted_at');
                }),
            ],
            'birth_date' => 'nullable|date',
            'address'    => 'nullable',
            'city'       => [
                'required',
                'exists:cities,id',
            ],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        User::where('id', $id)->update([
            'status'       => $request->status,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country_code' => $request->country_code,
            'birth_date'   => $request->birth_date,
            'national_id'  => $request->national_id,
            'city_id'      => $request->city,
        ]);

        $car = Car::where('user_id', $id)->first();
        if ($car) {
            $car->status = $request->status;
            $car->save();
        }

        $queryParams = $request->except(['_token', '_method', 'status', 'email', 'phone', 'country_code', 'birth_date', 'national_id']);
        return redirect()->route('drivers', $queryParams)->with('success', 'Driver updated successfully!');
    }

    public function delete($id, Request $request)
    {
        $user = User::where('id', $id)->first();
        $user->tokens()->delete();
        $user->delete();
        return redirect()->route('drivers', $request->query())
            ->with('success', 'Driver deleted successfully.');
    }

    public function restore($id, Request $request)
    {
        User::withTrashed()->where('id', $id)->update(['deleted_at' => null]);
        return redirect('/admin-dashboard/archived-drivers?type=' . $request->type);
    }

    public function exportCsv(Request $request)
    {
        // FIX: Removed the default 'cars' fallback so it exports ALL drivers if type is empty
        $type     = $request->query('type');
        $search   = $request->query('search');
        $status   = $request->query('status');
        $city     = $request->query('city');
        $online   = $request->query('online');
        $scope    = $request->query('export_scope', 'all');
        $page     = $request->query('page', 1);
        $perPage  = 25;
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        // Only verified drivers
        $query = User::where('mode', 'driver')->where('is_verified', '1');

        // Apply vehicle type filter ONLY if a specific type was requested
        if ($type === 'cars') {
            $query->where('driver_type', 'car');
        } elseif ($type === 'comfort_cars') {
            $query->where('driver_type', 'comfort_car');
        } elseif ($type === 'scooters') {
            $query->where('driver_type', 'scooter');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($city)) {
            $query->where('city_id', $city);
        }

        if ($online !== null && $online !== '') {
            $query->where('is_online', $online);
        }

        $query->orderBy('created_at', 'desc');

        if ($scope === 'page') {

            $users = $query->forPage($page, $perPage)->get();

        } elseif ($scope === 'date_range') {

            if (empty($dateFrom) || empty($dateTo)) {
                return redirect()->back()->with('error', 'Please provide both a start and end date for the date range export.');
            }

            $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $to   = \Carbon\Carbon::parse($dateTo)->endOfDay();

            if ($from->gt($to)) {
                return redirect()->back()->with('error', '"From" date must be before or equal to "To" date.');
            }

            $users = $query->whereBetween('created_at', [$from, $to])->get();

        } else {

            $users = $query->get();

        }

        $typeLabel = $type ? $type : 'all';
        $datePart  = $scope === 'date_range'
            ? "_{$dateFrom}_to_{$dateTo}"
            : ($scope === 'page' ? "_page{$page}" : '');

        $filename = "drivers_{$typeLabel}_{$scope}{$datePart}_" . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM — makes Excel open Arabic correctly
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['#', 'Name', 'Email', 'Phone', 'Status', 'Online', 'Join Date']);

            $counter = 1;
            foreach ($users as $user) {
                fputcsv($file, [
                    $counter++,
                    $user->name,
                    $user->email,
                    "\t" . $user->country_code . $user->phone,
                    $user->status,
                    $user->is_online ? 'Online' : 'Offline',
                    $user->created_at->format('d.M.Y h:i a'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
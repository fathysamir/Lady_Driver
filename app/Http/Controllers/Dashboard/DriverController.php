<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\City;
use App\Models\DriverLicense;
use App\Models\Trip;
use App\Models\User;
use App\Models\Scooter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\MotorcycleMark;
use App\Models\MotorcycleModel;
use App\Models\Setting;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Image;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index(Request $request)
{
    $all_users = User::where('mode', 'driver')->where('is_verified', '1');

    // Alexandria (city_id = 3)
    if (auth()->user()->hasRole('Supervisor')) {
        $all_users->where('city_id', 3);
    }

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

    // Supervisor cannot change city filter — always locked to Alexandria
    if (!auth()->user()->hasRole('Supervisor')) {
        if ($request->has('city') && $request->city != null) {
            $all_users->where('city_id', $request->city);
        }
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
    $city   = auth()->user()->hasRole('Supervisor') ? 3 : $request->city;
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

    $query = User::where('mode', 'driver')->where('is_verified', '1');

    // Supervisor: force Alexandria only
    if (auth()->user()->hasRole('Supervisor')) {
        $query->where('city_id', 3);
    }

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

    if (!empty($status)) $query->where('status', $status);

    // Supervisor city filter is already forced above, skip request city
    if (!auth()->user()->hasRole('Supervisor') && !empty($city)) {
        $query->where('city_id', $city);
    }

    if ($online !== null && $online !== '') $query->where('is_online', $online);

    $query->orderBy('created_at', 'desc');

    if ($scope === 'page') {
        $users = $query->forPage($page, $perPage)->get();
    } elseif ($scope === 'date_range') {
        if (empty($dateFrom) || empty($dateTo)) {
            return redirect()->back()->with('error', 'Please provide both a start and end date.');
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
public function bulkDestroy(Request $request)
{
    $request->validate([
        'ids'   => 'required|array',
        'ids.*' => 'integer|exists:users,id',
    ]);

    User::whereIn('id', $request->ids)->delete();

    return redirect()
        ->route('drivers', ['type' => $request->type])
        ->with('success', count($request->ids) . ' driver(s) deleted successfully.');
}

public function create()
{
    $cities        = City::orderBy('name')->get();
    $carMarks      = CarMark::orderBy('en_name')->get();
    $carModels     = CarModel::orderBy('en_name')->get();
    $scooterMarks  = MotorcycleMark::orderBy('en_name')->get();
    $scooterModels = MotorcycleModel::orderBy('en_name')->get();
    $comfort_year  = Setting::where('key', 'comfort_car_start_from_year')
                        ->where('category', 'General')
                        ->where('type', 'number')
                        ->first()?->value ?? 2020;

    return view('dashboard.drivers.create', compact(
        'cities',
        'carMarks',
        'carModels',
        'scooterMarks',
        'scooterModels',
        'comfort_year',
    ));
}
public function store(Request $request)
{
    // ── Temporarily store uploaded files so they survive validation failure ──
    $tempFields = [
        'image', 'ID_front_image', 'ID_back_image', 'passport_image',
        'license_front_image', 'license_back_image',
        'vehicle_image', 'plate_image',
        'vehicle_license_front_image', 'vehicle_license_back_image',
    ];

    foreach ($tempFields as $field) {
        if ($request->hasFile($field)) {
            $path = $request->file($field)->store('temp_uploads', 'public');
            session()->put("temp_upload_{$field}", $path);
        }
    }

    // ── Validation ───────────────────────────────────────────────────────────
    $validator = Validator::make($request->all(), [
        'name'                        => 'required|string|max:255',
        'email'                       => [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users')->whereNull('deleted_at'),
        ],
        'password'                    => 'required|string|min:8',
        'country_code'                => 'required|string|max:10',
        'phone'                       => [
            'required',
            Rule::unique('users')->where(function ($query) use ($request) {
                return $query->where('country_code', $request->country_code)
                    ->whereNull('deleted_at');
            }),
        ],
        'birth_date'                  => [
            'required',
            'date',
            'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
            'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
        ],
        'city_id'                     => ['required', Rule::exists('cities', 'id')->whereNull('deleted_at')],

        'national_id'                 => 'nullable|digits:14|required_without:passport_id',
        'national_id_expire_date'     => 'nullable|date',
        'ID_front_image'              => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_ID_front_image',
        'ID_back_image'               => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_ID_back_image',

        'passport_id'                 => 'nullable|required_without:national_id',
        'passport_expire_date'        => 'nullable|date',
        'passport_image'              => 'nullable|image|mimes:jpg,jpeg,png|max:5120',

        'image'                       => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_image',

        'driving_license_number'      => 'required|string|max:50',
        'license_expire_date'         => [
            'required',
            'date_format:Y-m-d',
            'after_or_equal:today',
            'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
        ],
        'license_front_image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_license_front_image',
        'license_back_image'          => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_license_back_image',

        'vehicle_type'                => ['required', Rule::in(['car', 'scooter'])],
        'car_mark_id'                 => [
            'required_if:vehicle_type,car',
            'nullable',
            Rule::exists('car_marks', 'id'),
        ],
        'car_model_id'                => [
            'required_if:vehicle_type,car',
            'nullable',
            Rule::exists('car_models', 'id'),
        ],
        'scooter_mark_id'             => [
            'required_if:vehicle_type,scooter',
            'nullable',
            Rule::exists('motorcycle_marks', 'id'),
        ],
        'scooter_model_id'            => [
            'required_if:vehicle_type,scooter',
            'nullable',
            Rule::exists('motorcycle_models', 'id'),
        ],

        'color'                       => 'required|string|max:255',
        'year'                        => 'required|integer|min:1990|max:' . date('Y'),
        'plate_num'                   => 'required|string|max:255',
        'vehicle_license_expire_date' => [
            'required',
            'date_format:Y-m-d',
            'after_or_equal:today',
            'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
        ],
        'vehicle_image'               => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_vehicle_image',
        'plate_image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_plate_image',
        'vehicle_license_front_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_vehicle_license_front_image',
        'vehicle_license_back_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:5120|required_without:temp_vehicle_license_back_image',

        'status'                      => ['required', Rule::in(['pending', 'confirmed', 'banned', 'blocked'])],
    ]);

    if ($validator->fails()) {
        return Redirect::back()->withInput()->withErrors($validator);
    }

    // ── Age ──────────────────────────────────────────────────────────────────
    $age = $request->birth_date ? Carbon::parse($request->birth_date)->age : null;

    // ── Driver / vehicle type ────────────────────────────────────────────────
    if ($request->vehicle_type == 'car') {
        $comfort_year = Setting::where('key', 'comfort_car_start_from_year')
            ->where('category', 'General')
            ->where('type', 'number')
            ->first()?->value ?? 2020;

        if (intval($request->year) >= intval($comfort_year)) {
            $driver_type = 'comfort_car';
            $is_comfort  = '1';
        } else {
            $driver_type = 'car';
            $is_comfort  = '0';
        }
    } else {
        $driver_type = 'scooter';
    }

    // ── Username & invitation code ───────────────────────────────────────────
    $username = username_Generation($request->name);

    do {
        $invitation_code = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
    } while (User::where('invitation_code', $invitation_code)->exists());

    // ── Create user ──────────────────────────────────────────────────────────
    $user = User::create([
        'name'            => $request->name,
        'username'        => $username,
        'email'           => $request->email,
        'password'        => Hash::make($request->password),
        'phone'           => $request->phone,
        'country_code'    => $request->country_code,
        'mode'            => 'driver',
        'invitation_code' => $invitation_code,
        'gendor'          => 'Female',
        'birth_date'      => $request->birth_date,
        'age'             => $age,
        'city_id'         => $request->city_id,
        'national_id'     => $request->national_id,
        'passport_id'     => $request->passport_id,
        'driver_type'     => $driver_type,
        'level'           => '1',
        'status'          => $request->status,
        'is_verified'     => '1',
    ]);

    $role = Role::where('name', 'Driver')->first();
    $user->assignRole([$role->id]);

    // ── User images ──────────────────────────────────────────────────────────
    $this->uploadFileOrTemp($request, $user, 'image', $user->avatarCollection);
    $this->uploadFileOrTemp($request, $user, 'ID_front_image', $user->IDfrontImageCollection);
    $this->uploadFileOrTemp($request, $user, 'ID_back_image', $user->IDbackImageCollection);
    $this->uploadFileOrTemp($request, $user, 'passport_image', $user->passportImageCollection);

    // ── Driving license ──────────────────────────────────────────────────────
    $license = DriverLicense::create([
        'user_id'     => $user->id,
        'license_num' => $request->driving_license_number,
        'expire_date' => $request->license_expire_date,
    ]);

    $this->uploadFileOrTemp($request, $license, 'license_front_image', $license->LicenseFrontImageCollection);
    $this->uploadFileOrTemp($request, $license, 'license_back_image', $license->LicenseBackImageCollection);

    // ── Vehicle ──────────────────────────────────────────────────────────────
    if ($request->vehicle_type == 'car') {
        $lastCar = Car::orderBy('id', 'desc')->first();

        if ($lastCar) {
            $lastCode = $lastCar->code;
            $code     = 'CAR-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
        } else {
            $code = 'CAR-000000001';
        }

        $car = Car::create([
            'user_id'             => $user->id,
            'code'                => $code,
            'car_mark_id'         => $request->car_mark_id,
            'car_model_id'        => $request->car_model_id,
            'color'               => $request->color,
            'year'                => $request->year,
            'car_plate'           => $request->plate_num,
            'passenger_type'      => 'female',
            'license_expire_date' => $request->vehicle_license_expire_date,
            'is_comfort'          => $is_comfort,
            'air_conditioned'     => '0',
            'animals'             => '0',
        ]);

        $this->uploadFileOrTemp($request, $car, 'vehicle_image', $car->avatarCollection);
        $this->uploadFileOrTemp($request, $car, 'plate_image', $car->PlateImageCollection);
        $this->uploadFileOrTemp($request, $car, 'vehicle_license_front_image', $car->LicenseFrontImageCollection);
        $this->uploadFileOrTemp($request, $car, 'vehicle_license_back_image', $car->LicenseBackImageCollection);

    } elseif ($request->vehicle_type == 'scooter') {
        $lastScooter = Scooter::orderBy('id', 'desc')->first();

        if ($lastScooter) {
            $lastCode = $lastScooter->code;
            $code     = 'SCO-' . str_pad((int) substr($lastCode, 4) + 1, 9, '0', STR_PAD_LEFT);
        } else {
            $code = 'SCO-000000001';
        }

        $scooter = Scooter::create([
            'user_id'             => $user->id,
            'code'                => $code,
            'motorcycle_mark_id'  => $request->scooter_mark_id,
            'motorcycle_model_id' => $request->scooter_model_id,
            'color'               => $request->color,
            'year'                => $request->year,
            'scooter_plate'       => $request->plate_num,
            'license_expire_date' => $request->vehicle_license_expire_date,
        ]);

        $this->uploadFileOrTemp($request, $scooter, 'vehicle_image', $scooter->avatarCollection);
        $this->uploadFileOrTemp($request, $scooter, 'plate_image', $scooter->PlateImageCollection);
        $this->uploadFileOrTemp($request, $scooter, 'vehicle_license_front_image', $scooter->LicenseFrontImageCollection);
        $this->uploadFileOrTemp($request, $scooter, 'vehicle_license_back_image', $scooter->LicenseBackImageCollection);
    }

    // ── Clear all temp session files ─────────────────────────────────────────
    foreach ($tempFields as $field) {
        session()->forget("temp_upload_{$field}");
    }

    return redirect()->route('drivers', request()->query())
        ->with('success', 'Driver account created successfully.');
}

private function uploadFileOrTemp(Request $request, $model, string $field, string $collection): void
{
    if ($request->hasFile($field)) {
        $model->addMedia($request->file($field))
              ->toMediaCollection($collection);
        session()->forget("temp_upload_{$field}");

    } elseif ($request->input("temp_{$field}") && Storage::disk('public')->exists($request->input("temp_{$field}"))) {
        $tempPath = storage_path('app/public/' . $request->input("temp_{$field}"));
        $model->addMedia($tempPath)
              ->toMediaCollection($collection);
        session()->forget("temp_upload_{$field}");
    }
}
}
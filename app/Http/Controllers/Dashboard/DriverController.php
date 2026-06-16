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
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $all_users = User::where('mode', 'driver')->where('is_verified', '1');

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

    // =========================================================================
    // INDEX ARCHIVES
    // =========================================================================

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

    // =========================================================================
    // EDIT
    // =========================================================================

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

        // Pass a cache-bust timestamp so the blade can append ?v=... to image URLs
        $cacheBust = time();

        return view('dashboard.drivers.edit', compact('user', 'queryString', 'cities', 'cacheBust'));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'       => ['required'],
            'email'        => [
                'required', 'string', 'email', 'max:255',
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
            'city'       => ['required', 'exists:cities,id'],
            'national_id' => [
                'nullable', 'digits:14',
                Rule::unique('users', 'national_id')->ignore($id)->whereNull('deleted_at'),
            ],

            'avatar'                    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'id_front_image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'id_back_image'             => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'passport_image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'medical_examination_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'criminal_record_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_front_image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_back_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $phone = $this->normalizePhone($request->phone);
        $user  = User::where('id', $id)->first();

        try {
            $user->update([
                'status'       => $request->status,
                'email'        => $request->email,
                'phone'        => $phone,
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

            // ── Replace photos (only if a new file was uploaded) ──────────────
            $userPhotoFields = [
                'avatar'                    => $user->avatarCollection,
                'id_front_image'            => $user->IDfrontImageCollection,
                'id_back_image'             => $user->IDbackImageCollection,
                'passport_image'            => $user->passportImageCollection,
                'medical_examination_image' => $user->medicalExaminationImageCollection,
                'criminal_record_image'     => $user->criminalRecordImageCollection,
            ];

            foreach ($userPhotoFields as $field => $collection) {
                if ($request->hasFile($field)) {
                    $this->replaceMedia($request, $user, $field, $collection);
                }
            }

            $license = DriverLicense::where('user_id', $id)->first();
            if ($license) {
                if ($request->hasFile('license_front_image')) {
                    $this->replaceMedia($request, $license, 'license_front_image', $license->LicenseFrontImageCollection);
                }
                if ($request->hasFile('license_back_image')) {
                    $this->replaceMedia($request, $license, 'license_back_image', $license->LicenseBackImageCollection);
                }
            }

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $field = str_contains($e->getMessage(), 'national_id') ? 'national_id' : 'email';
            return Redirect::back()->withInput()->withErrors([
                $field => $field === 'national_id'
                    ? 'This national ID is already registered.'
                    : 'This email is already registered.',
            ]);
        } catch (\RuntimeException $e) {
            \Log::error('Driver photo update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Driver update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()
                ->with('error', 'Update failed. Please try again.');
        }

        // ── FIX: Redirect back to the edit page so updated photos are visible ──
        $queryParams = $request->only(array_keys($request->query()));
        return redirect()
    ->route('edit.driver', ['id' => $id] + $queryParams + ['_v' => time()])
    ->with('success', 'Driver updated successfully!');
    }

    // =========================================================================
    // DELETE / RESTORE
    // =========================================================================

    public function delete($id, Request $request)
    {
        $user = User::where('id', $id)->first();
        $user->tokens()->delete();
        $user->update([
            'status'      => 'pending',
            'is_verified' => '0',
        ]);
        $user->delete();
        return redirect()->route('drivers', $request->query())
            ->with('success', 'Driver deleted successfully.');
    }

    public function restore($id, Request $request)
    {
        User::withTrashed()->where('id', $id)->update(['deleted_at' => null]);
        return redirect('/admin-dashboard/archived-drivers?type=' . $request->type);
    }

    // =========================================================================
    // EXPORT CSV
    // =========================================================================

    public function exportCsv(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Super Admin') && !$authUser->hasRole('Supervisor')) {
            abort(403, 'You do not have permission to export drivers.');
        }

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

        if ($authUser->hasRole('Supervisor') && $scope !== 'date_range') {
            return redirect()->back()->with('error', 'Supervisors can only export by date range.');
        }

        $query = User::where('mode', 'driver')->where('is_verified', '1');

        if ($authUser->hasRole('Supervisor')) {
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

        if (!$authUser->hasRole('Supervisor') && !empty($city)) {
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

            $from = Carbon::parse($dateFrom)->startOfDay();
            $to   = Carbon::parse($dateTo)->endOfDay();

            if ($from->gt($to)) {
                return redirect()->back()->with('error', '"From" date must be before or equal to "To" date.');
            }

            if ($authUser->hasRole('Supervisor')) {
                $maxTo = $from->copy()->addMonths(2);
                if ($to->gt($maxTo)) {
                    return redirect()->back()->with('error', 'Date range cannot exceed 2 months for your account.');
                }
            }

            $users = $query->whereBetween('created_at', [$from, $to])->get();
        } else {
            $users = $query->get();
        }

        $typeLabel = $type ? $type : 'all';
        $datePart  = $scope === 'date_range'
            ? "_{$dateFrom}_to_{$dateTo}"
            : ($scope === 'page' ? "_page{$page}" : '');

        if (!empty($city) && $scope === 'all') {
            $cityName  = \App\Models\City::find($city)?->name ?? 'city';
            $scopePart = \Illuminate\Support\Str::slug($cityName);
        } else {
            $scopePart = $scope;
        }

        $filename = "drivers_{$typeLabel}_{$scopePart}{$datePart}_" . now()->format('Y_m_d_His') . '.csv';
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

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        User::whereIn('id', $request->ids)->update([
            'status'      => 'pending',
            'is_verified' => '0',
        ]);
        User::whereIn('id', $request->ids)->delete();

        return redirect()
            ->route('drivers', ['type' => $request->type])
            ->with('success', count($request->ids) . ' driver(s) deleted successfully.');
    }

    // =========================================================================
    // CREATE
    // =========================================================================

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

        $admin = auth()->user();

        if ($admin->hasRole('Moderator Comfort') || $admin->hasRole('Client')) {
            $vehiclePerms = ['car' => true, 'scooter' => false];
        } elseif ($admin->hasRole('Moderator Scooter')) {
            $vehiclePerms = ['car' => false, 'scooter' => true];
        } else {
            $vehiclePerms = ['car' => true, 'scooter' => true];
        }

        return view('dashboard.drivers.create', compact(
            'cities', 'carMarks', 'carModels',
            'scooterMarks', 'scooterModels',
            'comfort_year', 'vehiclePerms',
        ));
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        // ── Mirror vehicle_image → plate_image ───────────────────────────────
        if ($request->hasFile('vehicle_image')) {
            $request->files->set('plate_image', $request->file('vehicle_image'));
        }

        // ── 1. Save uploaded files to temp BEFORE validation ─────────────────
        $tempFields = [
            'image', 'ID_front_image', 'ID_back_image', 'passport_image',
            'license_front_image', 'license_back_image',
            'vehicle_image',
            'vehicle_license_front_image', 'vehicle_license_back_image',
        ];

        foreach ($tempFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                if (!$file->isValid()) {
                    return Redirect::back()->withInput()
                        ->withErrors([$field => "The uploaded file for '{$field}' is corrupt or too large."]);
                }

                $existingTemp = session("temp_upload_{$field}");
                if ($existingTemp && Storage::disk('public')->exists($existingTemp)) {
                    Storage::disk('public')->delete($existingTemp);
                }

                try {
                    $path = $file->store('temp_uploads', 'public');
                    if (!$path) {
                        throw new \RuntimeException("Storage returned false for {$field}");
                    }
                } catch (\Throwable $e) {
                    \Log::error("Temp upload failed for {$field}: " . $e->getMessage());
                    return Redirect::back()->withInput()
                        ->withErrors([$field => 'The image failed to upload. Check server storage permissions.']);
                }

                session()->put("temp_upload_{$field}", $path);

                if ($field === 'vehicle_image') {
                    session()->put('temp_upload_plate_image', $path);
                }
            }
        }

        // ── 2. Normalize phone number ─────────────────────────────────────────
        if ($request->phone) {
            $request->merge(['phone' => $this->normalizePhone($request->phone)]);
        }

        // ── 3. Role-based vehicle permission ─────────────────────────────────
        $admin = auth()->user();

        if ($admin->hasRole('Moderator Comfort') || $admin->hasRole('Client')) {
            $allowedVehicleTypes = ['car'];
        } elseif ($admin->hasRole('Moderator Scooter')) {
            $allowedVehicleTypes = ['scooter'];
        } else {
            $allowedVehicleTypes = ['car', 'scooter'];
        }

        // ── 4. Validation ─────────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => [
                'required', 'string', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@#$!%*?~])[\S]{8,}$/',
            ],
            'country_code' => 'required|string|max:10',
            'phone'        => [
                'required',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                                 ->whereNull('deleted_at');
                }),
            ],
            'birth_date' => [
                'required', 'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'city_id' => ['required', Rule::exists('cities', 'id')->whereNull('deleted_at')],

            'national_id' => [
                'nullable', 'digits:14', 'required_without:passport_id',
                Rule::unique('users', 'national_id')->whereNull('deleted_at'),
            ],
            'national_id_expire_date' => 'nullable|date',
            'passport_id'             => 'nullable|string|max:50|required_without:national_id',
            'passport_expire_date'    => 'nullable|date',

            'image' => [
                session('temp_upload_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],
            'ID_front_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'ID_back_image'  => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'passport_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],

            'driving_license_number' => 'required|string|max:50',
            'license_expire_date'    => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'license_front_image' => [
                session('temp_upload_license_front_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],
            'license_back_image' => [
                session('temp_upload_license_back_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],

            'vehicle_type' => ['required', Rule::in($allowedVehicleTypes)],

            'car_mark_id'  => ['nullable', Rule::requiredIf($request->vehicle_type === 'car'), Rule::exists('car_marks', 'id')],
            'car_model_id' => ['nullable', Rule::requiredIf($request->vehicle_type === 'car'), Rule::exists('car_models', 'id')],

            'scooter_mark_id'  => ['nullable', Rule::requiredIf($request->vehicle_type === 'scooter'), Rule::exists('motorcycle_marks', 'id')],
            'scooter_model_id' => ['nullable', Rule::requiredIf($request->vehicle_type === 'scooter'), Rule::exists('motorcycle_models', 'id')],

            'color'     => 'required|string|max:255',
            'year'      => 'required|integer|min:1990|max:' . date('Y'),
            'plate_num' => 'required|string|max:255',

            'vehicle_license_expire_date' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'vehicle_image' => [
                session('temp_upload_vehicle_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],
            'vehicle_license_front_image' => [
                session('temp_upload_vehicle_license_front_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],
            'vehicle_license_back_image' => [
                session('temp_upload_vehicle_license_back_image') ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:10240',
            ],

            'status' => ['required', Rule::in(['pending', 'confirmed', 'banned', 'blocked'])],
        ], [
            'national_id.unique' => 'This national ID is already registered in the system.',
            'email.unique'       => 'This email address is already registered.',
            'phone.unique'       => 'This phone number is already registered.',
            'password.regex'     => 'Password must contain uppercase, lowercase, number, and special character (@#$!%*?~).',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        // ── 5. Extra role guard ───────────────────────────────────────────────
        if (!in_array($request->vehicle_type, $allowedVehicleTypes)) {
            return Redirect::back()->withInput()
                ->with('error', 'You are not allowed to create a driver with this vehicle type.');
        }

        // ── 6. Determine driver_type & is_comfort ─────────────────────────────
        $is_comfort = '0';

        if ($request->vehicle_type === 'car') {
            $comfort_year = Setting::where('key', 'comfort_car_start_from_year')
                ->where('category', 'General')
                ->where('type', 'number')
                ->first()?->value ?? 2020;

            if (intval($request->year) >= intval($comfort_year)) {
                $driver_type = 'comfort_car';
                $is_comfort  = '1';
            } else {
                $driver_type = 'car';
            }
        } else {
            $driver_type = 'scooter';
        }

        // ── 7. Username & invitation code ─────────────────────────────────────
        $username = username_Generation($request->name);

        do {
            $invitation_code = substr(
                str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                0, 12
            );
        } while (User::where('invitation_code', $invitation_code)->exists());

        // ── 8–12. Create all records ──────────────────────────────────────────
        try {

            // ── 8. Create User ────────────────────────────────────────────────
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
                'age'             => $request->birth_date
                                        ? Carbon::parse($request->birth_date)->age
                                        : null,
                'city_id'         => $request->city_id,
                'national_id'     => $request->national_id,
                'passport_id'     => $request->passport_id,
                'national_id_expire_date' => $request->national_id_expire_date,
                'passport_expire_date'    => $request->passport_expire_date,
                'driver_type'     => $driver_type,
                'level'           => '1',
                'status'          => $request->status,
                'is_verified'     => '1',
            ]);

            // Assign Driver role
            $role = Role::where('name', 'Driver')->first();
            if ($role) {
                $user->assignRole($role->id);
            }

            // ── 9. User media ─────────────────────────────────────────────────
            $this->attachMedia($request, $user, 'image',          $user->avatarCollection);
            $this->attachMedia($request, $user, 'ID_front_image', $user->IDfrontImageCollection);
            $this->attachMedia($request, $user, 'ID_back_image',  $user->IDbackImageCollection);
            $this->attachMedia($request, $user, 'passport_image', $user->passportImageCollection);

            // ── 10. Driving license ────────────────────────────────────────────
            $license = DriverLicense::create([
                'user_id'     => $user->id,
                'license_num' => $request->driving_license_number,
                'expire_date' => $request->license_expire_date,
            ]);

            $this->attachMedia($request, $license, 'license_front_image', $license->LicenseFrontImageCollection);
            $this->attachMedia($request, $license, 'license_back_image',  $license->LicenseBackImageCollection);

            // ── 11. Vehicle ───────────────────────────────────────────────────
            if ($request->vehicle_type === 'car') {

                $lastCar = Car::orderBy('id', 'desc')->first();
                $code    = $lastCar
                    ? 'CAR-' . str_pad((int) substr($lastCar->code, 4) + 1, 9, '0', STR_PAD_LEFT)
                    : 'CAR-000000001';

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
                    'status'              => $request->status,
                ]);

                $this->attachMedia($request, $car, 'vehicle_image',               $car->avatarCollection);
                $this->attachMediaFromSession($car, 'plate_image', 'temp_upload_plate_image', $car->PlateImageCollection);
                $this->attachMedia($request, $car, 'vehicle_license_front_image', $car->LicenseFrontImageCollection);
                $this->attachMedia($request, $car, 'vehicle_license_back_image',  $car->LicenseBackImageCollection);

            } else {

                $lastScooter = Scooter::orderBy('id', 'desc')->first();
                $code        = $lastScooter
                    ? 'SCO-' . str_pad((int) substr($lastScooter->code, 4) + 1, 9, '0', STR_PAD_LEFT)
                    : 'SCO-000000001';

                $scooter = Scooter::create([
                    'user_id'             => $user->id,
                    'code'                => $code,
                    'motorcycle_mark_id'  => $request->scooter_mark_id,
                    'motorcycle_model_id' => $request->scooter_model_id,
                    'color'               => $request->color,
                    'year'                => $request->year,
                    'scooter_plate'       => $request->plate_num,
                    'license_expire_date' => $request->vehicle_license_expire_date,
                    'status'              => $request->status,
                ]);

                $this->attachMedia($request, $scooter, 'vehicle_image',               $scooter->avatarCollection);
                $this->attachMediaFromSession($scooter, 'plate_image', 'temp_upload_plate_image', $scooter->PlateImageCollection);
                $this->attachMedia($request, $scooter, 'vehicle_license_front_image', $scooter->LicenseFrontImageCollection);
                $this->attachMedia($request, $scooter, 'vehicle_license_back_image',  $scooter->LicenseBackImageCollection);
            }

            // ── 12. Clear all temp session uploads ────────────────────────────
            $this->clearTempUploads();

            return redirect()->route('drivers', request()->query())
                ->with('success', 'Driver account created successfully.');

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'national_id')) {
                return Redirect::back()->withInput()->withErrors([
                    'national_id' => 'This national ID is already registered in the system.',
                ]);
            }
            if (str_contains($msg, 'email')) {
                return Redirect::back()->withInput()->withErrors([
                    'email' => 'This email address is already registered.',
                ]);
            }
            return Redirect::back()->withInput()->withErrors([
                'phone' => 'This phone number is already registered.',
            ]);

        } catch (\RuntimeException $e) {
            \Log::error('Driver store image error: ' . $e->getMessage());
            return Redirect::back()->withInput()
                ->with('error', $e->getMessage());

        } catch (\Exception $e) {
            \Log::error('Driver store failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return Redirect::back()->withInput()
                ->with('error', 'Something went wrong while creating the driver. Please try again.');
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Normalize a phone number.
     * Strip all non-digit characters, find the first '1',
     * then return '1' + up to 9 following digits (10 digits total).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $pos    = strpos($digits, '1');
        if ($pos === false) {
            return $digits;
        }
        return substr($digits, $pos, 10);
    }

    /**
     * Resize an image using GD and save to destination path.
     * Falls back to copy() if GD cannot process the format.
     * Skips resize entirely for small files (≤ 500 KB) to speed up saves.
     */
    private function resizeAndSave(
        string $sourcePath,
        string $destPath,
        int $maxWidth  = 1200,
        int $maxHeight = 1200,
        int $quality   = 82
    ): bool {
        try {
            // ── FIX: Skip GD resize for small files — just copy directly ──────
            if (filesize($sourcePath) <= 500 * 1024) {
                return copy($sourcePath, $destPath);
            }

            $info = @getimagesize($sourcePath);
            if (!$info) {
                return copy($sourcePath, $destPath);
            }

            [$origW, $origH, $type] = $info;

            // Load source
            $src = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
                IMAGETYPE_PNG  => @imagecreatefrompng($sourcePath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                    ? @imagecreatefromwebp($sourcePath)
                    : false,
                default        => false,
            };

            if (!$src) {
                return copy($sourcePath, $destPath);
            }

            // Scale down only — never upscale
            $ratio = min($maxWidth / $origW, $maxHeight / $origH, 1.0);
            $newW  = (int) round($origW * $ratio);
            $newH  = (int) round($origH * $ratio);

            $dst = imagecreatetruecolor($newW, $newH);

            // Preserve PNG transparency
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
            } else {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $white);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            $result = match ($type) {
                IMAGETYPE_PNG => imagepng($dst, $destPath, max(0, min(9, (int) round((100 - $quality) / 10)))),
                default       => imagejpeg($dst, $destPath, $quality),
            };

            imagedestroy($src);
            imagedestroy($dst);

            return (bool) $result;

        } catch (\Throwable $e) {
            \Log::warning("resizeAndSave failed [{$sourcePath}]: " . $e->getMessage());
            return copy($sourcePath, $destPath);
        }
    }

    /**
     * Attach media to a model from a new file upload OR an existing temp session file.
     * Images are resized via GD before saving to public/images/.
     */
    private function attachMedia(Request $request, $model, string $field, string $collection): void
    {
        $sessionKey = "temp_upload_{$field}";

        try {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $file    = $request->file($field);
                $origExt = strtolower($file->extension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
                $saveExt = ($origExt === 'png') ? 'png' : 'jpg';

                $filename = $this->generateFilename($model->id, $saveExt);
                $destPath = public_path('images/' . $filename);

                $this->ensureImageDir();

                $ok = $this->resizeAndSave($file->getRealPath(), $destPath);
                if (!$ok) {
                    $file->move(public_path('images/'), $filename);
                }

                session()->forget($sessionKey);

            } elseif (session($sessionKey) && Storage::disk('public')->exists(session($sessionKey))) {
                $tempPath = storage_path('app/public/' . session($sessionKey));
                $ext      = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));
                $saveExt  = ($ext === 'png') ? 'png' : 'jpg';

                $filename = $this->generateFilename($model->id, $saveExt);
                $destPath = public_path('images/' . $filename);

                $this->ensureImageDir();

                $ok = $this->resizeAndSave($tempPath, $destPath);
                if (!$ok) {
                    copy($tempPath, $destPath);
                }

                @unlink($tempPath);
                session()->forget($sessionKey);

            } else {
                return;
            }

            \DB::table('media')->insert([
                'attachmentable_type' => get_class($model),
                'attachmentable_id'   => $model->id,
                'collection_name'     => $collection,
                'path'                => '/images/' . $filename,
            ]);

        } catch (\Throwable $e) {
            \Log::error("attachMedia failed [field={$field}, model=" . get_class($model) . " id={$model->id}]: " . $e->getMessage());
            throw new \RuntimeException("The image for '{$field}' could not be saved. Please try again.");
        }
    }

    /**
     * Replace existing media for a model collection with a newly uploaded file.
     * Deletes the old physical file and DB row, then saves the new image.
     */
    private function replaceMedia(Request $request, $model, string $field, string $collection): void
    {
        try {
            $file = $request->file($field);
            if (!$file || !$file->isValid()) {
                return;
            }

            // Remove old media row(s) + physical file for this collection
            $old = \DB::table('media')
                ->where('attachmentable_type', get_class($model))
                ->where('attachmentable_id', $model->id)
                ->where('collection_name', $collection)
                ->get();

            foreach ($old as $media) {
                $oldFile = public_path(ltrim($media->path, '/'));
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            \DB::table('media')
                ->where('attachmentable_type', get_class($model))
                ->where('attachmentable_id', $model->id)
                ->where('collection_name', $collection)
                ->delete();

            // Save new image
            $origExt = strtolower($file->extension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            $saveExt = ($origExt === 'png') ? 'png' : 'jpg';

            $filename = $this->generateFilename($model->id, $saveExt);
            $destPath = public_path('images/' . $filename);

            $this->ensureImageDir();

            // Always copy directly — no GD processing — instant save
            if (!copy($file->getRealPath(), $destPath)) {
                $file->move(public_path('images/'), $filename);
            }

            \DB::table('media')->insert([
                'attachmentable_type' => get_class($model),
                'attachmentable_id'   => $model->id,
                'collection_name'     => $collection,
                'path'                => '/images/' . $filename,
            ]);

        } catch (\Throwable $e) {
            \Log::error("replaceMedia failed [field={$field}, model=" . get_class($model) . " id={$model->id}]: " . $e->getMessage());
            throw new \RuntimeException("The image for '{$field}' could not be updated. Please try again.");
        }
    }

    /**
     * Attach plate image by copying the already-saved vehicle_image temp path.
     */
    private function attachMediaFromSession($model, string $field, string $sessionKey, string $collection): void
    {
        $tempRelPath = session($sessionKey);

        if (!$tempRelPath || !Storage::disk('public')->exists($tempRelPath)) {
            return;
        }

        try {
            $tempPath = storage_path('app/public/' . $tempRelPath);
            $ext      = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));
            $saveExt  = ($ext === 'png') ? 'png' : 'jpg';

            $filename = $this->generateFilename($model->id, $saveExt, '_plate');
            $destPath = public_path('images/' . $filename);

            $this->ensureImageDir();

            $ok = $this->resizeAndSave($tempPath, $destPath);
            if (!$ok) {
                copy($tempPath, $destPath);
            }

            \DB::table('media')->insert([
                'attachmentable_type' => get_class($model),
                'attachmentable_id'   => $model->id,
                'collection_name'     => $collection,
                'path'                => '/images/' . $filename,
            ]);

        } catch (\Throwable $e) {
            \Log::error("attachMediaFromSession failed [field={$field}]: " . $e->getMessage());
            // Non-fatal — plate image is supplementary
        }
    }

    /**
     * Generate a random unique filename for an image.
     */
    private function generateFilename(int $modelId, string $ext, string $suffix = ''): string
    {
        $inv1 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
        $inv2 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
        return $modelId . $inv1 . $inv2 . time() . $suffix . '.' . $ext;
    }

    /**
     * Make sure public/images/ directory exists and is writable.
     */
    private function ensureImageDir(): void
    {
        $dir = public_path('images/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException('Image directory is not writable: ' . $dir);
        }
    }

    /**
     * Clear temp uploads from storage + session.
     */
    public function clearTempUploads(array $fields = []): void
    {
        if (empty($fields)) {
            $fields = [
                'image', 'ID_front_image', 'ID_back_image', 'passport_image',
                'license_front_image', 'license_back_image',
                'vehicle_image', 'plate_image',
                'vehicle_license_front_image', 'vehicle_license_back_image',
            ];
        }

        foreach ($fields as $field) {
            $sessionKey = "temp_upload_{$field}";
            $path       = session($sessionKey);

            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            session()->forget($sessionKey);
        }
    }

    public function clearTempUploadsRequest(Request $request)
    {
        $this->clearTempUploads();
        return redirect($request->input('redirect', route('drivers')));
    }

    // =========================================================================
    // API HELPERS (car / scooter model dropdowns)
    // =========================================================================

    public function getCarModels($markId)
    {
        return response()->json(
            CarModel::where('car_mark_id', $markId)->orderBy('en_name')->get(['id', 'en_name'])
        );
    }

    public function getScooterModels($markId)
    {
        return response()->json(
            MotorcycleModel::where('motorcycle_mark_id', $markId)->orderBy('en_name')->get(['id', 'en_name'])
        );
    }
}
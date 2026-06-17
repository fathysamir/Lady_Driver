<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMark;
use App\Models\CarModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Image;

class CarController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $all_cars = Car::orderBy('id', 'desc');

        if ($request->has('search') && $request->search != null) {
            $all_cars->where(function ($query) use ($request) {
                $query->where('car_plate', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('color', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('code', 'LIKE', '%' . $request->search . '%');
            });
        }
        if ($request->has('user') && $request->user != null) {
            $all_cars->where('user_id', $request->user);
        }
        if ($request->has('mark') && $request->mark != null) {
            $all_cars->where('car_mark_id', $request->mark);
        }
        if ($request->has('model') && $request->model != null) {
            $all_cars->where('car_model_id', $request->model);
        }
        if ($request->has('year') && $request->year != null) {
            $all_cars->where('year', $request->year);
        }
        if ($request->has('status') && $request->status != null) {
            $all_cars->where('status', $request->status);
        }
        if ($request->has('air_conditioned') && $request->air_conditioned != null) {
            $all_cars->where('air_conditioned', '1');
        }

        $all_cars = $all_cars->paginate(12);
        $users    = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Client');
        })->where('mode', 'driver')->get();
        $car_marks = CarMark::all();
        $search    = $request->search;

        return view('dashboard.cars.index', compact('all_cars', 'users', 'car_marks', 'search'));
    }

    // =========================================================================
    // GET MODELS (dropdown AJAX)
    // =========================================================================

    public function getModels(Request $request)
    {
        $markId = $request->input('markId');
        $models = CarModel::where('car_mark_id', $markId)->get();

        return response()->json($models);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        $car                      = Car::where('id', $id)->first();
        $car->image               = getFirstMediaUrl($car, $car->avatarCollection);
        $car->plate_image         = getFirstMediaUrl($car, $car->PlateImageCollection);
        $car->license_front_image = getFirstMediaUrl($car, $car->LicenseFrontImageCollection);
        $car->license_back_image  = getFirstMediaUrl($car, $car->LicenseBackImageCollection);
        $car->CarInspectionImage  = getFirstMediaUrl($car, $car->CarInspectionImageCollection);

        return view('dashboard.cars.edit', compact('car'));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'                 => ['required'],
            'car_image'              => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'plate_image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_front_image'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_back_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'car_inspection_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $car = Car::where('id', $id)->first();

        try {
            $car->status = $request->status;
            $car->save();

            $photoFields = [
                'car_image'            => $car->avatarCollection,
                'plate_image'          => $car->PlateImageCollection,
                'license_front_image'  => $car->LicenseFrontImageCollection,
                'license_back_image'   => $car->LicenseBackImageCollection,
                'car_inspection_image' => $car->CarInspectionImageCollection,
            ];

            foreach ($photoFields as $field => $collection) {
                if ($request->hasFile($field)) {
                    $this->replaceMedia($request, $car, $field, $collection);
                }
            }

        } catch (\RuntimeException $e) {
            \Log::error('Car photo update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Car update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', 'Update failed. Please try again.');
        }

        return redirect()
            ->route('edit.car', ['id' => $id])
            ->with('success', 'Car updated successfully!');
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        Car::where('id', $id)->delete();
        return redirect('/admin-dashboard/cars');
    }

    // =========================================================================
    // GET LOCATION (live map AJAX)
    // =========================================================================

    public function getLocation($id)
    {
        $car = Car::find($id);

        return response()->json([
            'lat' => $car->lat,
            'lng' => $car->lng,
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

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
}
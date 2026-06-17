<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Scooter;
use App\Models\MotorcycleModel;
use App\Models\MotorcycleMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class ScooterController extends Controller
{
    // =========================================================================
    // GET MODELS (dropdown AJAX)
    // =========================================================================

    public function getModels(Request $request)
    {
        $markId = $request->input('markId');
        $models = MotorcycleModel::where('motorcycle_mark_id', $markId)->get();

        return response()->json($models);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        $scooter                      = Scooter::where('id', $id)->first();
        $scooter->image               = getFirstMediaUrl($scooter, $scooter->avatarCollection);
        $scooter->plate_image         = getFirstMediaUrl($scooter, $scooter->PlateImageCollection);
        $scooter->license_front_image = getFirstMediaUrl($scooter, $scooter->LicenseFrontImageCollection);
        $scooter->license_back_image  = getFirstMediaUrl($scooter, $scooter->LicenseBackImageCollection);

        return view('dashboard.scooters.edit', compact('scooter'));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'               => ['required'],
            'scooter_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'plate_image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_front_image'  => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'license_back_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $scooter = Scooter::where('id', $id)->first();

        try {
            $scooter->status = $request->status;
            $scooter->save();

            $photoFields = [
                'scooter_image'       => $scooter->avatarCollection,
                'plate_image'         => $scooter->PlateImageCollection,
                'license_front_image' => $scooter->LicenseFrontImageCollection,
                'license_back_image'  => $scooter->LicenseBackImageCollection,
            ];

            foreach ($photoFields as $field => $collection) {
                if ($request->hasFile($field)) {
                    $this->replaceMedia($request, $scooter, $field, $collection);
                }
            }

        } catch (\RuntimeException $e) {
            \Log::error('Scooter photo update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Scooter update failed: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', 'Update failed. Please try again.');
        }

        return redirect()
            ->route('edit.scooter', ['id' => $id])
            ->with('success', 'Scooter updated successfully!');
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        Scooter::where('id', $id)->delete();
        return redirect('/admin-dashboard/scooters');
    }

    // =========================================================================
    // GET LOCATION (live map AJAX)
    // =========================================================================

    public function getLocation($id)
    {
        $scooter = Scooter::find($id);

        return response()->json([
            'lat' => $scooter->lat,
            'lng' => $scooter->lng,
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
    private function generateFilename(int $modelId, string $ext): string
    {
        $inv1 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
        $inv2 = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_'), 0, 12);
        return $modelId . $inv1 . $inv2 . time() . '.' . $ext;
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
<?php

namespace App\Jobs;

use App\Models\Car;
use App\Models\DriverLicense;
use App\Models\Scooter;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteUnverifiedUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $users = User::where('is_verified', '0')
            ->where('otp_expires_at', '<', Carbon::now())
            ->where('created_at', '>=', '2026-05-25 06:51:45')
            ->get();

        if ($users->isEmpty()) {
            Log::info('DeleteUnverifiedUsers: no expired unverified users found.');
            return;
        }

        foreach ($users as $user) {
            try {
                // Student
                Student::where('user_id', $user->id)->delete();

                // Driver License
                $lis = DriverLicense::where('user_id', $user->id)->first();
                if ($lis) {
                    deleteMedia($lis, $lis->LicenseFrontImageCollection);
                    deleteMedia($lis, $lis->LicenseBackImageCollection);
                    $lis->delete();
                }

                // Car
                if ($user->car) {
                    $car = Car::where('user_id', $user->id)->first();
                    deleteMedia($car, $car->avatarCollection);
                    deleteMedia($car, $car->PlateImageCollection);
                    deleteMedia($car, $car->LicenseFrontImageCollection);
                    deleteMedia($car, $car->LicenseBackImageCollection);
                    deleteMedia($car, $car->CarInspectionImageCollection);
                    $car->delete();
                }

                // Scooter
                if ($user->scooter) {
                    $scooter = Scooter::where('user_id', $user->id)->first();
                    deleteMedia($scooter, $scooter->avatarCollection);
                    deleteMedia($scooter, $scooter->PlateImageCollection);
                    deleteMedia($scooter, $scooter->LicenseFrontImageCollection);
                    deleteMedia($scooter, $scooter->LicenseBackImageCollection);
                    $scooter->delete();
                }

                // User media
                deleteMedia($user, $user->avatarCollection);
                deleteMedia($user, $user->IDfrontImageCollection);
                deleteMedia($user, $user->IDbackImageCollection);
                deleteMedia($user, $user->passportImageCollection);
                deleteMedia($user, $user->medicalExaminationImageCollection);
                deleteMedia($user, $user->criminalRecordImageCollection);

                // Tokens
                $user->tokens()->delete();

                // Delete user
                $user->forceDelete();

                Log::info("DeleteUnverifiedUsers: deleted user [{$user->id}] email={$user->email}");

            } catch (\Exception $e) {
                Log::error("DeleteUnverifiedUsers: failed to delete user [{$user->id}] - " . $e->getMessage());
            }
        }

        Log::info("DeleteUnverifiedUsers: job finished.");
    }
}
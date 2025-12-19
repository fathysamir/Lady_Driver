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

class DeleteUnverifiedUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('is_verified', '0')
            ->where('created_at', '<=', Carbon::now()->subHour())
            ->first();
        Student::where('user_id', $user->id)->delete();
        $lis = DriverLicense::where('user_id', $user->id)->first();
        if ($lis) {
            deleteMedia($lis, $lis->LicenseFrontImageCollection);
            deleteMedia($lis, $lis->LicenseBackImageCollection);
            $lis->delete();
        }
        if ($user->car) {
            $car = Car::where('user_id', $user->id)->first();
            deleteMedia($car, $car->avatarCollection);
            deleteMedia($car, $car->PlateImageCollection);
            deleteMedia($car, $car->LicenseFrontImageCollection);
            deleteMedia($car, $car->LicenseBackImageCollection);
            deleteMedia($car, $car->CarInspectionImageCollection);
            $car->delete();
        } elseif ($user->scooter) {
            $scooter = Scooter::where('user_id', $user->id)->first();
            deleteMedia($lis, $lis->avatarCollection);
            deleteMedia($scooter, $scooter->PlateImageCollection);
            deleteMedia($scooter, $scooter->LicenseFrontImageCollection);
            deleteMedia($scooter, $scooter->LicenseBackImageCollection);
            $scooter->delete();
        }
        $user->delete();
    }
}

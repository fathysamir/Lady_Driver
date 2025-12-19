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
        DriverLicense::where('user_id', $user->id)->delete();
        Car::where('user_id', $user->id)->delete();
        Scooter::where('user_id', $user->id)->delete();
        $user->delete();
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ContactUs;
use App\Models\User;
use App\Models\Car;
use App\Models\Careers;
use App\Models\Complaint;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Log;
class CustomLogoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('theme', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $theme=auth()->user()->theme;
            return $theme;
        });
        $this->app->bind('new_contact_us_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_contact_us_count=ContactUs::where('seen','0')->count();
            return $new_contact_us_count;
        });
        $this->app->bind('new_clients_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_clients_count=User::where('seen','0')->where('mode','client')->count();
            return $new_clients_count;
        });
        $this->app->bind('new_drivers_count', function () {
            $user = auth()->user();

            if (!$user) return 0;

            $query = User::where('mode', 'driver')
                ->where('status', 'pending')
                ->where('is_verified', '1')
                ->whereNull('deleted_at')
                ->whereNotNull('driver_type')
                ->where('created_at', '>', now()->subDays(15)->startOfDay());

            switch ($user->role) {
                case 'Moderator Standard':
                    $query->where('driver_type', 'car');
                    break;
                case 'Moderator Comfort':
                    $query->where('driver_type', 'comfort_car');
                    break;
                case 'Moderator Scooter':
                    $query->where('driver_type', 'scooter');
                    break;
            }

            return $query->count();
        });
        $this->app->bind('new_cars_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_cars_count=Car::where('status','pending')->count();
            return $new_cars_count;
        });
        $this->app->bind('new_careers_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_careers_count=Careers::where('status','pending')->count();
            return $new_careers_count;
        });
        $this->app->bind('new_complaints_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_complaints_count=Complaint::where('seen','0')->count();
            return $new_complaints_count;
        });
        $this->app->bind('new_suggestions_count', function () {
            // Logic to determine the path to the logo image
            //$logo=url(Setting::where('key','logo')->where('category','website')->where('type','file')->first()->value);
            $new_suggestions_count=Suggestion::where('seen','0')->count();
            return $new_suggestions_count;
        });
    }

    public function boot()
    {
        //
    }
}
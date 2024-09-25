<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
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
    }

    public function boot()
    {
        //
    }
}
<?php
namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('*', function ($view) {
            $total_clients_count = User::whereNull('student_code')->count();
            
            //Students 
            $students_count = User::whereNotNull('student_code')->count();

            $new_clients_count = User::whereDate('created_at', today())->count();
    
            $view->with([
                'total_clients_count' => $total_clients_count,
                'students_count'      => $students_count,
                'new_clients_count'   => $new_clients_count,
            ]);
        });
    }
}

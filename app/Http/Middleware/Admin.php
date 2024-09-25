<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (!Auth::check()) {


            return redirect('admin-dashboard/login');

        }
        if(count(auth()->user()->roles)==0)
        {
            return redirect('admin-dashboard/login');
        }
        if(!is_null(auth()->user()->roles))
        {
            if(auth()->user()->roles->first()->name!='Admin')
            {   
                 Auth::logout();

                return redirect('admin-dashboard/login')->withErrors(['msg' => 'Please verify that your information is correct']);
            }
        }

    return $next($request);
       
    }
}
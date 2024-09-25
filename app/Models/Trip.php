<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Trip extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'trips';
    protected $fillable = [
        'user_id',
        'car_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'start_lat',
        'start_lng',
        'address1',
        'end_lat',
        'end_lng',
        'address2',
        'total_price',
        'app_rate',
        'driver_rate',
        'distance',
        'client_stare_rate',
        'client_comment',
        'driver_stare_rate',
        'driver_comment',
        'status',
        'cancelled_by_id',
        'payment_status',
        'type',
        'air_conditioned'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function offers()
    {
        return $this->hasMany(Offer::class,'trip_id');
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function car(){
        return $this->belongsTo(Car::class,'car_id','id');
    }
    public function cancelled_by(){
        return $this->belongsTo(User::class,'cancelled_by_id','id');
    }
   
   
    
}

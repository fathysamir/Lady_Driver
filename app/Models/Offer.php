<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Offer extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'offers';
    protected $fillable = [
        'user_id',
        'car_id',
        'trip_id',
        'status',
        'offer'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function car(){
        return $this->belongsTo(Car::class,'car_id','id');
    }
    public function trip(){
        return $this->belongsTo(Trip::class,'trip_id','id');
    }
   
    
}

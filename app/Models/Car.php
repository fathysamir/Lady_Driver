<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Car extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'cars';
    
    public $avatarCollection = 'image';
    public $PlateImageCollection = 'plate_image';
    public $LicenseFrontImageCollection = 'license_front_image';
    public $LicenseBackImageCollection = 'license_back_image';
    public $CarInspectionImageCollection = 'car-inspection_image';

    protected $fillable = [
        'code',
        'user_id',
        'car_mark_id',
        'car_model_id',
        'color',
        'year',
        'car_plate',
        'lat',
        'lng',
        'air_conditioned',
        'status',
        'car_inspection_date',
        'passenger_type',
        'license_expire_date',
        'animals',
        'is_comfort',
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function trips()
    {
        return $this->hasMany(Trip::class,'car_id');
    }
    public function owner(){
        return $this->belongsTo(User::class,'user_id','id')->withTrashed();
    }
    public function mark(){
        return $this->belongsTo(CarMark::class,'car_mark_id','id')->withTrashed();
    }
    public function model(){
        return $this->belongsTo(CarModel::class,'car_model_id','id')->withTrashed();
    }
   
    
}

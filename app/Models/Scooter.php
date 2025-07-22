<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Scooter extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'scooters';
    
    public $avatarCollection = 'image';
    public $PlateImageCollection = 'plate_image';
    public $LicenseFrontImageCollection = 'license_front_image';
    public $LicenseBackImageCollection = 'license_back_image';

    protected $fillable = [
        'code',
        'user_id',
        'motorcycle_mark_id',
        'motorcycle_model_id',
        'color',
        'year',
        'scooter_plate',
        'lat',
        'lng',
        'status',
        'license_expire_date',
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function trips()
    {
        return $this->hasMany(Trip::class,'scooter_id');
    }
    public function owner(){
        return $this->belongsTo(User::class,'user_id','id')->withTrashed();
    }
    public function mark(){
        return $this->belongsTo(MotorcycleMark::class,'motorcycle_mark_id','id')->withTrashed();
    }
    public function model(){
        return $this->belongsTo(MotorcycleModel::class,'motorcycle_model_id','id')->withTrashed();
    }
   
    
}

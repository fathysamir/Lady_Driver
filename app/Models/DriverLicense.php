<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class DriverLicense extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'driver_licenses';

    public $LicenseFrontImageCollection = 'license_front_image';
    public $LicenseBackImageCollection = 'license_back_image';
    
    protected $fillable = [
        'user_id',
        'license_num',
        'expire_date'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function user(){
        return $this->belongsTo(User::class,'user_id','id')->withTrashed();
    }
   
    
}

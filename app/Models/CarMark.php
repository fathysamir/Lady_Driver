<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
class CarMark extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'car_marks';
    protected $fillable = [
        'en_name',
        'ar_name'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];
    protected $appends = ['name'];
    public function getNameAttribute()
    {
        // Get the language from the 'Accept-Language' header
        $locale = request()->header('Accept-Language');

        // Default to 'en' if no language is provided or it's not 'ar'
        if ($locale == 'ar') {
            return $this->ar_name;
        }

        return $this->en_name;
    }

    public function models()
    {
        return $this->hasMany(CarModel::class,'car_mark_id');
    }
   
    
}

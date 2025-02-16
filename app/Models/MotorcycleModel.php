<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class MotorcycleModel extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'motorcycle_models';
    protected $fillable = [
        'en_name',
        'ar_name',
        'motorcycle_mark_id'
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
    public function mark(){
        return $this->belongsTo(MotorcycleMark::class,'motorcycle_mark_id','id')->withTrashed();
    }
   
    
}

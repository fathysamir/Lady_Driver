<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
class TripCancellingReason extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'trip_cancelling_reasons';
    protected $fillable = [
        'ar_reason',
        'en_reason',
        'type',
        'value_type',
        'value'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];
    protected $appends = ['reason'];
    public function getReasonAttribute()
    {
        // Get the language from the 'Accept-Language' header
        $locale = request()->header('Accept-Language');

        // Default to 'en' if no language is provided or it's not 'ar'
        if ($locale == 'ar') {
            return $this->ar_reason;
        }

        return $this->en_reason;
    }

    
   
    
}

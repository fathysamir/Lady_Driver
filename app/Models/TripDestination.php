<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripDestination extends Model
{
    use HasFactory;
    protected $table = 'trip_destinations';
   
    protected $fillable = [
        'trip_id',
        'address',
        'lat',
        'lng',
       
       
    ];
    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];
    protected $guarded = [];
    

    

    
    public function trip(){
        return $this->belongsTo(Trip::class,'trip_id','id')->withTrashed();
    }


}

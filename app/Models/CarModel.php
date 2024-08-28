<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class CarModel extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'car_models';
    protected $fillable = [
        'name',
        'car_mark_id'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function mark(){
        return $this->belongsTo(CarMark::class,'car_mark_id','id');
    }
   
    
}

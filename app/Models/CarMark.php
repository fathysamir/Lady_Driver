<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class CarMark extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'car_marks';
    protected $fillable = [
        'name'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function models()
    {
        return $this->hasMany(CarModel::class,'car_mark_id');
    }
   
    
}

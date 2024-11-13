<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Complaint extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'complaints';
    protected $fillable = [
        'user_id',
        'trip_id',
        'complaint',
        'seen',
       
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

   
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function trip(){
        return $this->belongsTo(Car::class,'trip_id','id');
    }
  
    
}

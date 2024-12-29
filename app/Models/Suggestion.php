<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class Suggestion extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'complaints';
    protected $fillable = [
        'user_id',
        
        'suggestion',
        'seen',
       
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

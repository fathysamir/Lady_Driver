<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
class Student extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'students';
    public $IDfrontImageCollection = 'id-front-image';
    protected $fillable = [
        'user_id',
        'university_name',
        'graduation_year',
        'status',
        'student_discount_service'
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class FAQ extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'faqs';
    protected $fillable = [
        'question',
        'answer',
        'type',
        'is_active'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

  
   
    
}

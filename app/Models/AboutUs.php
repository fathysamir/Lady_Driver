<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class AboutUs extends Model
{
    use HasFactory;


    public $mediaCollection = 'media';

    protected $table = 'about_us';
    protected $fillable = [
        'key',
        'value'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];
   
   

    
}
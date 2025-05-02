<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Setting extends Model
{
    use HasFactory;


    public $mediaCollection = 'media';


    protected $fillable = [
        'label',
        'key',
        'type',
        'value',
        'unit',
        'category'
    ];

    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];
   
   

    
}
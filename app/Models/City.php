<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;
class City extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'cities';
    protected $fillable = [
       'name'
    ];

    protected $allowedSorts = [

        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];




}

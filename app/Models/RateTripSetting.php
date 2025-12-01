<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateTripSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'label',
        'star_count',
        'category',
    ];

}

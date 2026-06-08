<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\CustomDateTimeCast;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cities';

    protected $fillable = [
        'name'
    ];

    protected $allowedSorts = [
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['deleted_at'];

    public function clients()
    {
        return $this->hasMany(User::class, 'city_id');
    }

    public function drivers()
    {
        return $this->hasMany(User::class, 'city_id');
    }
}
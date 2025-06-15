<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $avatarCollection = 'avatar-image';
    public $IDfrontImageCollection = 'id-front-image';
    public $IDbackImageCollection = 'id-back-image';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'age',
        'status',
        'OTP',
        'is_online',
        'national_id',
        'lat',
        'lng',
        'address',
        'invitation_code',
        'birth_date',
        'device_token',
        'password',
        'password2',
        'mode',
        'theme',
        'wallet',
        'gendor',
        'seen',
        'country_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

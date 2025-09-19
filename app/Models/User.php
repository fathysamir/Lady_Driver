<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $avatarCollection       = 'avatar-image';
    public $IDfrontImageCollection = 'id-front-image';
    public $IDbackImageCollection  = 'id-back-image';
    public $passportImageCollection = 'passport-image';
    protected $fillable            = [
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
        'country_code',
        'student_code',
        'city_id',
        'level',
        'driver_type'
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
        'password'          => 'hashed',
    ];

     public function getImageAttribute()
    {
        return getFirstMediaUrl($this, $this->avatarCollection, true);
    }
    public function getIDFrontImageAttribute()
    {
        return getFirstMediaUrl($this, $this->IDfrontImageCollection, true);
    }
    public function getIDBackImageAttribute()
    {
        return getFirstMediaUrl($this, $this->IDbackImageCollection, true);
    }
    public function getPassportImageAttribute()
    {
        return getFirstMediaUrl($this, $this->passportImageCollection, true);
    }


    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id')->withTrashed();
    }
    public function car()
    {
        return $this->hasOne(Car::class);
    }
    public function scooter()
    {
        return $this->hasOne(Scooter::class);
    }

     public function student()
    {
        return $this->hasOne(Student::class);
    }
}

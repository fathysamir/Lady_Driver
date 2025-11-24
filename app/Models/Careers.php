<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Careers extends Model
{
    use HasFactory;

    protected $table = 'careers';
    
    public $CvCollection = 'career-cv';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'country_code',
        'phone',
        'position',
    ];

    public function getCvAttribute()
    {
        return getFirstMediaUrl($this, $this->CvCollection, true);
    }

    protected $appends = [
        'cv'
    ];
}

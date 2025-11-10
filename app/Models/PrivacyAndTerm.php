<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacyAndTerm extends Model
{
    use HasFactory;

    protected $table = 'privacy_and_terms';
    protected $fillable = ['type', 'lang', 'value'];
}

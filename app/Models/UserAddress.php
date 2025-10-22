<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table    = 'user_addresses';
    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'title',
    ];

    protected $allowedSorts = [

        'created_at',
        'updated_at',
    ];
    protected $hidden = ['deleted_at'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

}

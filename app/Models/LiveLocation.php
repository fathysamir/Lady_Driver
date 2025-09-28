<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class LiveLocation extends Model
{
    use HasFactory;
    protected $table    = 'live_locations';
    protected $fillable = [
        'user_id', 'token', 'lat', 'lng', 'expires_at',
    ];

    protected $allowedSorts = [

        'created_at',
        'updated_at',
    ];
    protected $casts = ['expires_at' => 'datetime'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

}

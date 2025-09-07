<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FawryTransaction extends Model
{
    use HasFactory, SoftDeletes;
    protected $table    = 'fawry_transactions';
    protected $fillable = [
        'user_id',
        'merchant_ref',
        'reference_number',
        'amount',
        'payment_method',
        'status',
        'response',
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

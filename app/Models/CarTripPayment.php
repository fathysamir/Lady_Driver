<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarTripPayment extends Model
{
    use HasFactory, SoftDeletes;
    protected $table    = 'car_trip_payments';
    protected $fillable = [
        'trip_id',
        'amount',
        'method',
        'payment_date',
        'transaction_id',
        'status',
        'note',
    ];

    protected $allowedSorts = [

        'created_at',
        'updated_at',
    ];

    protected $hidden = ['deleted_at'];
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id')->withTrashed();
    }

}

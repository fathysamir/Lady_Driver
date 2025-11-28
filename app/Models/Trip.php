<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;
    public $barcodeImageCollection = 'barcode-image';
    protected $table    = 'trips';
    protected $fillable = [
        'user_id',
        'code',
        'barcode',
        'car_id',
        'scooter_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'start_lat',
        'start_lng',
        'address1',
        'total_price',
        'discount',
        'app_rate',
        'driver_rate',
        'paid_amount',
        'remaining_amount',
        'distance',
        'duration',
        'client_stare_rate',
        'client_comment',
        'driver_stare_rate',
        'driver_comment',
        'status',
        'cancelled_by_id',
        'trip_cancelling_reason_id',
        'payment_status',
        'student_trip',
        'air_conditioned',
        'animals',
        'bags',
        'driver_arrived',
        'type',
        'scheduled',
        'payment_method',
        'seen_count'
    ];

    protected $allowedSorts = [

        'created_at',
        'updated_at',
    ];

    protected $hidden = ['deleted_at'];

    public function offers()
    {
        return $this->hasMany(Offer::class, 'trip_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id', 'id')->withTrashed();
    }
     public function scooter()
    {
        return $this->belongsTo(Scooter::class, 'scooter_id', 'id')->withTrashed();
    }
    public function cancelled_by()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id', 'id')->withTrashed();
    }
    public function cancelling_reason()
    {
        return $this->belongsTo(TripCancellingReason::class, 'trip_cancelling_reason_id', 'id')->withTrashed();
    }

    public function payments()
    {
        return $this->hasMany(CarTripPayment::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

     public function finalDestination()
    {
        return $this->hasMany(TripDestination::class, 'trip_id')->orderBy('id');
    }


}

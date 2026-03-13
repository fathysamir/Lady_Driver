<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class TripChat extends Model
{
    use HasFactory;
    protected $table         = 'trip_chats';
    public $imageCollection  = 'image';
    public $recordCollection = 'record';
    protected $fillable      = [
        'sender_id',
        'trip_id',
        'message',
        'location',
        'seen',

    ];
    protected $allowedSorts = [

        'created_at',
        'updated_at',
    ];
    protected $guarded = [];
    protected $appends = [
        'image',
        'record'

    ];
    protected function serializeDate(\DateTimeInterface $date)
{
    return Carbon::instance($date)->timezone('Africa/Cairo')->format('Y-m-d\TH:i:s.000000\Z');
}
    public function getMessageAttribute($value)
    {
        if ($value != null) {
            return Crypt::decryptString($value);
        } else {
            return null;
        }

    }

    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = Crypt::encryptString($value);
    }

    public function getLocationAttribute($value)
    {
        if ($value != null) {
            return Crypt::decryptString($value);
        } else {
            return null;
        }

    }

    public function setLocationAttribute($value)
    {
        $this->attributes['location'] = Crypt::encryptString($value);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id')->withTrashed();
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id')->withTrashed();
    }

    public function getImageAttribute()
    {
        return getFirstMediaUrl($this, $this->imageCollection);
    }
    public function getRecordAttribute()
    {
        return getFirstMediaUrl($this, $this->recordCollection);
    }

}

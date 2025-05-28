<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class TripMessage extends Model
{
    use HasFactory;
    protected $table = 'trip_messages';
    public $imageCollection = 'image';
    public $videoCollection = 'video';
    public $recordCollection = 'record';
    protected $fillable = [
        'sender_id',
        'trip_id',
        'message',
        'location',
        'seen'
       
    ];
    protected $allowedSorts = [
       
        'created_at',
        'updated_at'
    ];
    protected $guarded = [];
    public function getMessageAttribute($value)
    {   
        if($value!=null){
        return Crypt::decryptString($value);
        }else{
            return null;
        }
        
    }

    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = Crypt::encryptString($value);
    }
    public function getLocationAttribute($value)
    {   
        if($value!=null){
        return Crypt::decryptString($value);
        }else{
            return null;
        }
        
    }

    public function setLocationAttribute($value)
    {
        $this->attributes['location'] = Crypt::encryptString($value);
    }
    public function sender(){
        return $this->belongsTo(User::class,'sender_id','id')->withTrashed();
    }
    public function trip(){
        return $this->belongsTo(Trip::class,'trip_id','id')->withTrashed();
    }


}

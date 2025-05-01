<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class TripMessage extends Model
{
    use HasFactory;
    protected $table = 'trip_messages';
    protected $fillable = [
        'sender_id',
        'trip_id',
        'message',
       
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

    public function sender(){
        return $this->belongsTo(User::class,'sender_id','id')->withTrashed();
    }
    public function receiver(){
        return $this->belongsTo(User::class,'receiver_id','id')->withTrashed();
    }


}

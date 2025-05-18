<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class DashboardMessage extends Model
{
    use HasFactory;
    protected $table = 'dashboard_messages';
    protected $fillable = [
        'receiver_id',
        'seen',
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

   
    public function receiver(){
        return $this->belongsTo(User::class,'receiver_id','id')->withTrashed();
    }


}

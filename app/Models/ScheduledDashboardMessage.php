<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class ScheduledDashboardMessage extends Model
{
    use HasFactory;
    protected $table = 'scheduled_dashboard_messages';
   
    protected $fillable = [
        'receivers',
        'users',
        'message',
        'sending_date',
        'image_path',
        'video_path'
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

   
   


}

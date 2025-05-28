<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
class ChatController extends ApiController
{
    public function send_message_view(){
        return view('dashboard.chats.send_message');
    }
}
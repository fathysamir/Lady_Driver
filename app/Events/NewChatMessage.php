<?php
namespace App\Events;

use App\Models\TripChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $receiverId;

    public function __construct(TripChat $chat, $receiverId)
    {
        $this->chat       = $chat;
        $this->receiverId = $receiverId;

    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }
    public function broadcastAs()
    {
        return 'new_message';
    }

    public function broadcastWith()
    {
        return [
            'id'       => $this->chat->id,
            'trip_id'  => $this->chat->trip_id,
            'sender'   => $this->chat->sender_id,
            'message'  => $this->chat->message,
            'location' => $this->chat->location,
            'image'    => $this->chat->image,
            'record'   => $this->chat->record,
            'created'  => $this->chat->created_at->toDateTimeString(),
        ];
    }
}

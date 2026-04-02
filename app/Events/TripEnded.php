<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripEnded  implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    public $receiverId;

    public function __construct($trip, $receiverId)
    {
        $this->trip       = $trip;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'ended_trip';
    }

    public function broadcastWith()
    {
        return [

            [
                'trip_id'     => $this->trip->id,
                'trip_status' => $this->trip->status,
            ],
            'message'     => 'trip ended now',
    ];
    }
}
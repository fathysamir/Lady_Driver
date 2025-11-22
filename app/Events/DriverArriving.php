<?php
namespace App\Events;

use App\Models\TripChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverArriving implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver_arriving;
    public $receiverId;

    public function __construct($driver_arriving, $receiverId)
    {
        $this->driver_arriving       = $driver_arriving;
        $this->receiverId = $receiverId;

    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }
    public function broadcastAs()
    {
        return 'driver_arriving';
    }

    public function broadcastWith()
    {
        return [
            'trip_id'  => $this->driver_arriving['trip_id'],
            'message'  => $this->driver_arriving['message']
        ];
    }
}

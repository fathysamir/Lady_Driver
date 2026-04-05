<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $receiverId;

    public function __construct($data, $receiverId)
    {
        $this->data       = $data;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'driver_reached';
    }

    public function broadcastWith()
    {
        return [
            'type'     => 'driver_reached',
            'trip_id'  => $this->data['trip_id'],
            'message'  => $this->data['message'],
            'distance' => $this->data['distance'],
            'trip'     => $this->data['trip'],
        ];
    }
}
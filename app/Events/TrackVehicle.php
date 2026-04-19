<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackVehicle implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $distance;
    public $duration;
    public $eta;
    public $receiverId;

    public function __construct($distance, $duration, $eta, $receiverId)
    {
        $this->distance   = $distance;
        $this->duration   = $duration;
        $this->eta        = $eta;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'track_vehicle';
    }

    public function broadcastWith()
    {
        return [
            'type' => 'track_vehicle',
            'data' => [
                'distance' => $this->distance,
                'duration' => $this->duration,
                'eta'      => $this->eta,
            ],
        ];
    }
}
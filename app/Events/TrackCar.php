<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackCar implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lat;
    public $lng;
    public $heading;
    public $speed;
    public $receiverId;

    public function __construct($lat, $lng, $heading, $speed, $receiverId)
    {
        $this->lat        = $lat;
        $this->lng        = $lng;
        $this->heading    = $heading;
        $this->speed      = $speed;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'track_car';
    }

    public function broadcastWith()
    {
        return [
            'type' => 'track_car',
            'data' => [
                'lat'     => $this->lat,
                'lng'     => $this->lng,
                'heading' => $this->heading,
                'speed'   => $this->speed,
            ],
        ];
    }
}
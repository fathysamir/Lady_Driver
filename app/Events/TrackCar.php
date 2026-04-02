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

    $this->lat        = floatval($lat);
    $this->lng        = floatval($lng);
    $this->heading    = $heading !== null ? floatval($heading) : 0;
    $this->speed      = $speed !== null ? floatval($speed) : 0;
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
            'data' => [
                'lat'     => $this->lat,
                'lng'     => $this->lng,
                'heading' => $this->heading,
                'speed'   => $this->speed,
            ],
        ];
    }
}
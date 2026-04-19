<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrackCar implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lat;
    public $lng;
    public $heading;
    public $speed;

    public $distance;
    public $duration;
    public $eta;

    public $message;
    public $status;

    public $receiverId;

    public function __construct(
        $lat,
        $lng,
        $heading,
        $speed,
        $distance = null,
        $duration = null,
        $eta = null,
        $message = null,
        $status = 'on_the_way',
        $receiverId = null
    ) {
        $this->lat        = floatval($lat);
        $this->lng        = floatval($lng);
        $this->heading    = $heading !== null ? floatval($heading) : 0;
        $this->speed      = $speed !== null ? floatval($speed) : 0;

        $this->distance   = $distance;
        $this->duration   = $duration;
        $this->eta        = $eta;

        $this->message    = $message;
        $this->status     = $status;

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
            'lat'       => $this->lat,
            'lng'       => $this->lng,
            'heading'   => $this->heading,
            'speed'     => $this->speed,

            'distance'  => $this->distance,
            'duration'  => $this->duration,
            'eta'       => $this->eta,

            'message'   => $this->message,
            'status'    => $this->status,
        ];
    }
}
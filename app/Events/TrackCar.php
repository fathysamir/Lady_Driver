<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class TrackCar implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lat;
    public $lng;
    public $heading;
    public $speed;
    public $distanceToPickup;
    public $durationToPickup;
    public $distanceToDestination;
    public $durationToDestination;
    public $receiverId;

    public function __construct(
        $lat,
        $lng,
        $heading,
        $speed,
        $distanceToPickup,
        $durationToPickup,
        $distanceToDestination,
        $durationToDestination,
        $receiverId
    ) {
        $this->lat                   = $lat;
        $this->lng                   = $lng;
        $this->heading               = $heading;
        $this->speed                 = $speed;
        $this->distanceToPickup      = $distanceToPickup;
        $this->durationToPickup      = $durationToPickup;
        $this->distanceToDestination = $distanceToDestination;
        $this->durationToDestination = $durationToDestination;
        $this->receiverId            = $receiverId;
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
                'lat'                     => $this->lat,
                'lng'                     => $this->lng,
                'heading'                 => $this->heading,
                'speed'                   => $this->speed,
                'distance_to_pickup'      => $this->distanceToPickup,
                'duration_to_pickup'      => $this->durationToPickup,
                'distance_to_destination' => $this->distanceToDestination,
                'duration_to_destination' => $this->durationToDestination,
            ],
        ];
    }
}

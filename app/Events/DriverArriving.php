<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Trip;

class DriverArriving implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver_arriving;
    public $receiverId;
    private $trip;

    public function __construct($driver_arriving, $receiverId)
    {
        $this->driver_arriving = $driver_arriving;
        $this->receiverId      = $receiverId;

        // Pre-load trip once in constructor to avoid double DB query
        $this->trip = Trip::with([
            'user:id,name,country_code,phone',
            'car' => function ($query) {
                $query->select('id', 'user_id', 'car_mark_id', 'car_model_id', 'year', 'lat', 'lng', 'color', 'car_plate')
                    ->with([
                        'mark:id,en_name,ar_name',
                        'model:id,en_name,ar_name',
                        'owner:id,name,country_code,phone,level',
                    ]);
            },
            'scooter' => function ($query) {
                $query->select('id', 'user_id', 'motorcycle_mark_id', 'motorcycle_model_id', 'year', 'lat', 'lng', 'color', 'scooter_plate')
                    ->with([
                        'motorcycleMark:id,en_name,ar_name',
                        'motorcycleModel:id,en_name,ar_name',
                        'owner:id,name,country_code,phone,level',
                    ]);
            },
            'finalDestination:id,trip_id,lat,lng,address',
        ])->find($driver_arriving['trip_id']);

        // Rename finalDestination -> final_destination to match TripByID structure
        if ($this->trip && $this->trip->finalDestination) {
            $this->trip->final_destination = $this->trip->finalDestination;
            unset($this->trip->finalDestination);
        }
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->receiverId);
    }

    public function broadcastAs(): string
    {
        return 'driver_arriving';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id'    => $this->driver_arriving['trip_id'],
            'message'    => $this->driver_arriving['message'],
            'distance'   => $this->driver_arriving['distance'],
            'arrived_at' => $this->driver_arriving['arrived_at'],
            'trip'       => $this->trip,
        ];
    }
}
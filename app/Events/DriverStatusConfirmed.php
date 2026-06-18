<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverStatusConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user) {}

    public function broadcastOn(): array
    {
        // Only broadcast if mode is driver (not client)
        if ($this->user->mode !== 'driver') {
            return [];
        }

        return [
            new PrivateChannel('driver.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'status'      => 'confirmed',
            'driver_type' => $this->user->driver_type, // car or scooter
            'user_id'     => $this->user->id,
        ];
    }
}
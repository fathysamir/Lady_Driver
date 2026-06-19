<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * يُبَث للراكب عندما يكتشف النظام أن نقطة الالتقاء الحالية تستدعي U-turn طويل
 * أو تقع على "الجانب الآخر من الطريق"، ويقترح نقطة بديلة أفضل.
 * القرار النهائي (قبول/رفض) يبقى مع الراكب عبر REST endpoint مخصص.
 */
class PickupPointSuggested implements ShouldBroadcast
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
        return 'pickup_point_suggested';
    }

    public function broadcastWith()
    {
        return [
            'type'            => 'pickup_point_suggested',
            'trip_id'         => $this->data['trip_id'],
            'lat'             => $this->data['lat'],
            'lng'             => $this->data['lng'],
            'driver_eta_sec'  => $this->data['driver_eta_sec'],
            'walk_distance_m' => $this->data['walk_distance_m'],
            'reason'          => $this->data['reason'],
        ];
    }
}
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoFrameReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $devicePublicId;
    public string $frame;
    public float $timestamp;

    public function __construct(string $devicePublicId, string $frame, float $timestamp)
    {
        $this->devicePublicId = $devicePublicId;
        $this->frame = $frame;
        $this->timestamp = $timestamp;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video.' . $this->devicePublicId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'video.frame';
    }

    public function broadcastWith(): array
    {
        return [
            'device' => $this->devicePublicId,
            'frame' => $this->frame,
            'timestamp' => $this->timestamp,
        ];
    }
}

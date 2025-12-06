<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Generic Device Event for real-time WebSocket broadcasting.
 * 
 * Replaces CommandStatusUpdated, DeviceCapabilitiesUpdated, DeviceLogReceived
 * with a single, flexible event class.
 */
class DeviceEventBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public string $eventType,  // 'log', 'command', 
        public array $payload      // Event-specific data
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('device.' . $this->device->id);
    }

    /**
     * Broadcast with snake_case event type for Pusher compatibility.
     */
    public function broadcastAs(): string
    {
        return 'device.' . $this->eventType;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return array_merge(
            ['device_id' => $this->device->id],
            $this->payload,
            ['timestamp' => now()->toIso8601String()]
        );
    }

    /**
     * Helper: Broadcast a device log event.
     */
    public static function log(Device $device, string $level, string $message, ?string $agentTimestamp = null): void
    {
        self::dispatch($device, 'log.received', [
            'level' => $level,
            'message' => $message,
            'agent_timestamp' => $agentTimestamp ?? now()->toIso8601String(),
        ]);
    }

    /**
     * Helper: Broadcast a command status update event.
     */
    public static function commandStatus($command): void
    {
        self::dispatch($command->device, 'command.status.updated', [
            'command_id' => $command->id,
            'type' => $command->type,
            'status' => $command->status,
            'result_message' => $command->result_message,
            'completed_at' => $command->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Helper: Broadcast a capability update event.
     */
    public static function capabilitiesUpdated(Device $device): void
    {
        self::dispatch($device, 'capabilities.updated', [
            'public_id' => $device->public_id,
            'capabilities' => $device->capabilities,
        ]);
    }
}

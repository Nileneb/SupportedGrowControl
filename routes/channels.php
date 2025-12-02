<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Device;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Device status updates - user can subscribe to their own devices
Broadcast::channel('device.{deviceId}', function ($user, $deviceId) {
    $device = Device::find($deviceId);
    
    // User can listen to their own device's channel
    return $device && $device->user_id === $user->id;
});

// Command status updates - user can subscribe to commands for their devices
Broadcast::channel('command.{commandId}', function ($user, $commandId) {
    $command = \App\Models\Command::find($commandId);
    
    if (!$command) {
        return false;
    }
    
    // User can listen if they own the device the command is for
    return $command->device && $command->device->user_id === $user->id;
});

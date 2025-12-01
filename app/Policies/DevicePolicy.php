<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DevicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Alle authentifizierten User können ihre Devices sehen
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Device $device): bool
    {
        // User kann nur eigene Devices sehen
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Alle authentifizierten User können Devices erstellen
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Device $device): bool
    {
        // User kann nur eigene Devices updaten
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Device $device): bool
    {
        // User kann nur eigene Devices löschen
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Device $device): bool
    {
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Device $device): bool
    {
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can control the device (spray, fill, etc.).
     */
    public function control(User $user, Device $device): bool
    {
        // Nur Owner kann Device steuern
        return $user->id === $device->user_id;
    }
}


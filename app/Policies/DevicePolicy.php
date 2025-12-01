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
     * Allows access for owner OR shared users via pivot.
     */
    public function view(User $user, Device $device): bool
    {
        // Direct owner
        if ($user->id === $device->user_id) {
            return true;
        }

        // Shared via users_devices pivot
        return $device->sharedUsers()->where('users.id', $user->id)->exists();
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
     * Allows owner OR shared users with 'editor' or 'owner' role.
     */
    public function update(User $user, Device $device): bool
    {
        // Direct owner
        if ($user->id === $device->user_id) {
            return true;
        }

        // Shared with editor/owner role
        $pivot = $device->sharedUsers()->where('users.id', $user->id)->first();
        return $pivot && in_array($pivot->pivot->role, ['editor', 'owner']);
    }

    /**
     * Determine whether the user can delete the model.
     * Only direct owner can delete.
     */
    public function delete(User $user, Device $device): bool
    {
        // Only direct owner can delete
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
     * Allows owner OR shared users with 'editor' or 'owner' role.
     */
    public function control(User $user, Device $device): bool
    {
        // Direct owner
        if ($user->id === $device->user_id) {
            return true;
        }

        // Shared with editor/owner role
        $pivot = $device->sharedUsers()->where('users.id', $user->id)->first();
        return $pivot && in_array($pivot->pivot->role, ['editor', 'owner']);
    }
}


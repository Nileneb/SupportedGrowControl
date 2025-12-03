<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        if ($event->user_id === $user->id) return true;
        // via participants
        if ($event->participants()->where('users.id', $user->id)->exists()) return true;
        // via device shared pivot (assumes Device::sharedUsers relationship)
        if ($event->device && $event->device->sharedUsers->contains($user->id)) return true;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Event $event): bool
    {
        if ($event->user_id === $user->id) return true;
        return $event->participants()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['owner', 'editor'])
            ->exists();
    }

    public function delete(User $user, Event $event): bool
    {
        if ($event->user_id === $user->id) return true;
        return $event->participants()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function reorder(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}

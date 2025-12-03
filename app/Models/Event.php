<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'device_id', 'calendar_id', 'title', 'description',
        'start_at', 'end_at', 'all_day', 'status', 'color', 'meta', 'rrule', 'last_executed_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'meta' => 'array',
        'last_executed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_participants')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->start_at || !$this->end_at) return null;
        return $this->end_at->diffInMinutes($this->start_at);
    }

    public function getIsPastAttribute(): bool
    {
        return $this->end_at ? $this->end_at->isPast() : $this->start_at->isPast();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->start_at->isToday();
    }

    public function getIsFutureAttribute(): bool
    {
        return $this->start_at->isFuture();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprayEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'start_time',
        'end_time',
        'duration_seconds',
        'manual',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_seconds' => 'integer',
            'manual' => 'boolean',
        ];
    }

    /**
     * Get the device that owns this spray event.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

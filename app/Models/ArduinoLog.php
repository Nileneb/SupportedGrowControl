<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArduinoLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'logged_at',
        'level',
        'message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }

    /**
     * Get the device that owns this log entry.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

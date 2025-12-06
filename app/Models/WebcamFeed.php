<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebcamFeed extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_path',
        'name',
        'stream_url',
        'snapshot_url',
        'type',
        'is_active',
        'refresh_interval',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceScript extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'name', 'language', 'description', 'code', 'status', 'compile_log', 'flash_log', 'compiled_at', 'flashed_at'
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

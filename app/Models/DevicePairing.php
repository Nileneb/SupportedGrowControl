<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class DevicePairing extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'pairing_code',
        'status',
        'agent_token',
        'user_id',
        'device_info',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'device_info' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

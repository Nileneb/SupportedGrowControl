<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterLevel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'measured_at',
        'level_percent',
        'liters',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
            'level_percent' => 'float',
            'liters' => 'float',
        ];
    }

    /**
     * Get the device that owns this water level reading.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

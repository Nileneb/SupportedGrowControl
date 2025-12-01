<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemStatus extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'measured_at',
        'water_level',
        'water_liters',
        'spray_active',
        'filling_active',
        'last_tds',
        'last_temperature',
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
            'water_level' => 'float',
            'water_liters' => 'float',
            'spray_active' => 'boolean',
            'filling_active' => 'boolean',
            'last_tds' => 'float',
            'last_temperature' => 'float',
        ];
    }

    /**
     * Get the device that owns this system status.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

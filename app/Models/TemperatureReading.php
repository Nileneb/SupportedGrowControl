<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemperatureReading extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'measured_at',
        'value_c',
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
            'value_c' => 'float',
        ];
    }

    /**
     * Get the device that owns this temperature reading.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

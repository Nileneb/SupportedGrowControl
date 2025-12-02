<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'sensor_type_id',
        'pin',
        'channel_key',
        'min_interval',
        'critical',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'critical' => 'boolean',
            'min_interval' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function sensorType(): BelongsTo
    {
        return $this->belongsTo(SensorType::class, 'sensor_type_id', 'id');
    }

    /**
     * Get full sensor configuration for agent consumption
     */
    public function toAgentFormat(): array
    {
        return [
            'channel' => $this->channel_key,
            'pin' => $this->pin,
            'type' => $this->sensor_type_id,
            'display_name' => $this->sensorType->display_name ?? $this->channel_key,
            'unit' => $this->sensorType->default_unit ?? '',
            'value_type' => $this->sensorType->value_type ?? 'float',
            'min_interval' => $this->min_interval ?? $this->sensorType->meta['min_interval'] ?? 10,
            'critical' => $this->critical,
            'config' => $this->config ?? [],
        ];
    }
}

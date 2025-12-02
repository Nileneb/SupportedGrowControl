<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceActuator extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'actuator_type_id',
        'pin',
        'channel_key',
        'min_interval',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'min_interval' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function actuatorType(): BelongsTo
    {
        return $this->belongsTo(ActuatorType::class, 'actuator_type_id', 'id');
    }

    /**
     * Get full actuator configuration for agent consumption
     */
    public function toAgentFormat(): array
    {
        return [
            'channel' => $this->channel_key,
            'pin' => $this->pin,
            'type' => $this->actuator_type_id,
            'display_name' => $this->actuatorType->display_name ?? $this->channel_key,
            'command_type' => $this->actuatorType->command_type ?? 'toggle',
            'params_schema' => $this->actuatorType->params_schema ?? [],
            'min_interval' => $this->min_interval ?? $this->actuatorType->min_interval ?? 0,
            'config' => $this->config ?? [],
        ];
    }
}

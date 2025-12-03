<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'vendor',
        'mcu',
        'architecture',
        'digital_pins',
        'analog_pins',
        'pwm_pins',
        'available_pins',
        'reserved_pins',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'available_pins' => 'array',
        'reserved_pins' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'digital_pins' => 'integer',
        'analog_pins' => 'integer',
        'pwm_pins' => 'integer',
    ];
}

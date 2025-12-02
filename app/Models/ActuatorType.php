<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActuatorType extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'display_name',
        'category',
        'command_type',
        'params_schema',
        'min_interval',
        'critical',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'params_schema' => 'array',
            'min_interval' => 'integer',
            'critical' => 'boolean',
            'meta' => 'array',
        ];
    }
}

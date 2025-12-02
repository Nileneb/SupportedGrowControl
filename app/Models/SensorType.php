<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorType extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'display_name',
        'category',
        'default_unit',
        'value_type',
        'default_range',
        'critical',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'default_range' => 'array',
            'critical' => 'boolean',
            'meta' => 'array',
        ];
    }
}

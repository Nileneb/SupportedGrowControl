<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsReading extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'measured_at',
        'value_ppm',
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
            'value_ppm' => 'float',
        ];
    }

    /**
     * Get the device that owns this TDS reading.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

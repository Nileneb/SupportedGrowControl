<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fqbn',
        'vendor',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * Get all devices using this board type
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'board_type', 'name');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowroomElement extends Model
{
    protected $fillable = [
        'growroom_layout_id',
        'device_id',
        'type',
        'label',
        'x_position',
        'y_position',
        'width',
        'height',
        'rotation',
        'color',
        'icon',
        'properties',
        'z_index',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(GrowroomLayout::class, 'growroom_layout_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowroomLayout extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'width',
        'height',
        'background_color',
        'background_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(GrowroomElement::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'source_type',
    'source_id',
    'title',
    'cuisine',
    'ingredients',
    'recipe_content',
    'extra_payload',
])]
class Favorite extends Model
{
    protected $casts = [
        'ingredients' => 'array',
        'extra_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

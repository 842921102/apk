<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'status_date',
    'mood',
    'appetite_state',
    'body_state',
    'wanted_food_style',
    'flavor_tags',
    'taboo_tags',
    'period_status',
    'note',
])]
class UserDailyStatus extends Model
{
    protected function casts(): array
    {
        return [
            'status_date' => 'date',
            'flavor_tags' => 'array',
            'taboo_tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

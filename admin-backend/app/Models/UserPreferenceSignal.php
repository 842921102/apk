<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'signal_type',
    'signal_key',
    'signal_value',
    'weight_score',
    'source',
    'last_triggered_at',
])]
class UserPreferenceSignal extends Model
{
    protected function casts(): array
    {
        return [
            'weight_score' => 'float',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

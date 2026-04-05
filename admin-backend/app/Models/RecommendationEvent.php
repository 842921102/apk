<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $recommendation_record_id
 * @property string $event_type
 * @property string|null $event_value
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class RecommendationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recommendation_record_id',
        'event_type',
        'event_value',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendationRecord(): BelongsTo
    {
        return $this->belongsTo(RecommendationRecord::class);
    }
}

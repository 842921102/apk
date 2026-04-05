<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $recommendation_record_id
 * @property string $feedback_type
 * @property string|null $feedback_reason
 * @property string $feedback_target
 * @property Carbon $created_at
 */
class RecommendationFeedbackRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recommendation_record_id',
        'feedback_type',
        'feedback_reason',
        'feedback_target',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendationRecord(): BelongsTo
    {
        return $this->belongsTo(RecommendationRecord::class, 'recommendation_record_id');
    }
}

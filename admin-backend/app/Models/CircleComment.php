<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'post_id',
    'user_id',
    'content',
    'status',
])]
class CircleComment extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<CirclePost, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(CirclePost::class, 'post_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Support\CirclePostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'title',
    'description',
    'content',
    'images',
    'cover_image',
    'source_type',
    'publish_source',
    'topic',
    'like_count',
    'favorite_count',
    'comment_count',
    'status',
    'is_recommended',
    'is_pinned',
    'sort',
    'related_product_id',
    'published_at',
])]
class CirclePost extends Model
{
    use SoftDeletes;

    protected $casts = [
        'images' => 'array',
        'is_recommended' => 'boolean',
        'is_pinned' => 'boolean',
        'related_product_id' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    /**
     * @return HasMany<CircleComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CircleComment::class, 'post_id');
    }

    /**
     * @return HasMany<CirclePostLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CirclePostLike::class, 'post_id');
    }

    /**
     * @return HasMany<CirclePostCollection, $this>
     */
    public function collections(): HasMany
    {
        return $this->hasMany(CirclePostCollection::class, 'post_id');
    }

    /**
     * 小程序 / 前台可见帖子
     *
     * @param  Builder<CirclePost>  $query
     * @return Builder<CirclePost>
     */
    public function scopePublishedVisible(Builder $query): Builder
    {
        return $query
            ->where('status', CirclePostStatus::Normal)
            ->whereNotNull('published_at');
    }

    public function firstImageUrl(): ?string
    {
        $imgs = $this->images ?? [];
        if (! is_array($imgs) || $imgs === []) {
            return null;
        }

        if (is_string($this->cover_image) && $this->cover_image !== '') {
            return $this->cover_image;
        }

        $first = $imgs[0] ?? null;

        return is_string($first) && $first !== '' ? $first : null;
    }
}

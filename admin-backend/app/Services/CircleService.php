<?php

namespace App\Services;

use App\Models\CircleComment;
use App\Models\CirclePost;
use App\Models\CirclePostCollection;
use App\Models\CirclePostLike;
use App\Models\Product;
use App\Models\User;
use App\Support\CircleCommentStatus;
use App\Support\CirclePostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CircleService
{
    private function hasProductsTable(): bool
    {
        return Schema::hasTable('products');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{items: array<int, array<string, mixed>>, has_more: bool}
     */
    public function paginateForPublic(array $validated, ?User $viewer): array
    {
        $tab = (string) ($validated['tab'] ?? 'latest');
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($validated['per_page'] ?? 15)));
        $keyword = isset($validated['keyword']) ? trim((string) $validated['keyword']) : '';

        $query = CirclePost::query()
            ->publishedVisible()
            ->with('user');

        if ($this->hasProductsTable()) {
            $query->with('relatedProduct');
        }

        if ($keyword !== '') {
            $kw = '%'.$keyword.'%';
            $query->where(function (Builder $q) use ($kw): void {
                $q->where('title', 'like', $kw)
                    ->orWhere('content', 'like', $kw)
                    ->orWhere('topic', 'like', $kw)
                    ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', $kw));
            });
        }

        if ($tab === 'recommend') {
            $query->orderByDesc('is_pinned')
                ->orderByDesc('is_recommended')
                ->orderByDesc('published_at');
        } elseif ($tab === 'ai_generated') {
            $query->where('source_type', 'ai_generated')->orderByDesc('published_at');
        } elseif ($tab === 'user_uploaded') {
            $query->where('source_type', 'user_uploaded')->orderByDesc('published_at');
        } elseif ($tab === 'following') {
            // 一期：未接关注表时，关注流为空（小程序可后续对接）
            return ['items' => [], 'has_more' => false];
        } else {
            $query->orderByDesc('published_at');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $viewerId = $viewer?->id;

        $items = $paginator->getCollection()
            ->map(fn (CirclePost $p) => $this->postToApiArray($p, $viewerId))
            ->all();

        return [
            'items' => $items,
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function postToApiArray(CirclePost $post, ?int $viewerUserId): array
    {
        $post->loadMissing('user');
        if ($this->hasProductsTable()) {
            $post->loadMissing('relatedProduct');
        }
        $u = $post->user;
        $relatedProduct = $post->relatedProduct;
        $relatedImages = $relatedProduct instanceof Product && is_array($relatedProduct->images) ? $relatedProduct->images : [];
        $canExposeRelatedProduct = $relatedProduct instanceof Product && $relatedProduct->status === 'online';

        $images = is_array($post->images) ? array_values(array_filter($post->images, fn ($v) => is_string($v) && $v !== '')) : [];

        $isLiked = false;
        $isCollected = false;
        if ($viewerUserId) {
            $isLiked = CirclePostLike::query()
                ->where('post_id', $post->id)
                ->where('user_id', $viewerUserId)
                ->exists();
            $isCollected = CirclePostCollection::query()
                ->where('post_id', $post->id)
                ->where('user_id', $viewerUserId)
                ->exists();
        }

        return [
            'id' => (string) $post->id,
            'userId' => (string) $post->user_id,
            'nickname' => (string) ($u?->name ?? ''),
            'avatar' => (string) ($u?->avatar_url ?? ''),
            'title' => (string) ($post->title ?? ''),
            'description' => (string) ($post->description ?? ''),
            'content' => (string) $post->content,
            'images' => $images,
            'coverImage' => (string) ($post->cover_image ?? ($images[0] ?? '')),
            'sourceType' => (string) ($post->source_type ?? 'user_uploaded'),
            'publishSource' => (string) ($post->publish_source ?? 'manual_upload'),
            'topic' => (string) $post->topic,
            'favoriteCount' => (int) $post->favorite_count,
            'likeCount' => (int) $post->like_count,
            'commentCount' => (int) $post->comment_count,
            'isLiked' => $isLiked,
            'isCollected' => $isCollected,
            'isFavorited' => $isCollected,
            'relatedProductId' => $post->related_product_id ? (string) $post->related_product_id : null,
            'relatedProduct' => $canExposeRelatedProduct ? [
                'id' => (string) $relatedProduct->id,
                'name' => (string) $relatedProduct->name,
                'priceText' => '¥'.number_format(((int) $relatedProduct->price) / 100, 2, '.', ''),
                'image' => (string) ($relatedProduct->cover_image ?: (($relatedImages[0] ?? '') ?: '')),
            ] : null,
            'createdAt' => $post->published_at?->toIso8601String() ?? $post->created_at->toIso8601String(),
        ];
    }

    public function findVisibleForPublic(int $id, ?User $viewer): ?CirclePost
    {
        $post = CirclePost::query()->publishedVisible()->whereKey($id)->first();
        if ($post) {
            return $post;
        }
        // 自己的下架帖仍可读
        if ($viewer) {
            return CirclePost::query()
                ->whereKey($id)
                ->where('user_id', $viewer->id)
                ->where('status', '!=', CirclePostStatus::Deleted)
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }

    /**
     * @param  array{title?: string, description?: string, content?: string, topic?: string, images: array<int, string>, sourceType?: string, publishSource?: string, relatedProductId?: int|string|null}  $data
     */
    public function createByUser(User $user, array $data): CirclePost
    {
        return CirclePost::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'content' => $data['content'] ?? ($data['description'] ?? ''),
            'topic' => $data['topic'] ?? '',
            'images' => $data['images'],
            'cover_image' => $data['images'][0] ?? null,
            'source_type' => $data['sourceType'] ?? 'user_uploaded',
            'publish_source' => $data['publishSource'] ?? 'manual_upload',
            'related_product_id' => $data['relatedProductId'] ?? null,
            'status' => CirclePostStatus::Normal,
            'like_count' => 0,
            'favorite_count' => 0,
            'comment_count' => 0,
            'is_recommended' => false,
            'is_pinned' => false,
            'published_at' => now(),
        ]);
    }

    public function toggleLike(User $user, CirclePost $post): CirclePost
    {
        return DB::transaction(function () use ($user, $post): CirclePost {
            $exists = CirclePostLike::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                CirclePostLike::query()
                    ->where('post_id', $post->id)
                    ->where('user_id', $user->id)
                    ->delete();
                $post->decrement('like_count');
            } else {
                CirclePostLike::query()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
                $post->increment('like_count');
            }
            $post->refresh();

            return $post;
        });
    }

    public function toggleCollect(User $user, CirclePost $post): CirclePost
    {
        return DB::transaction(function () use ($user, $post): CirclePost {
            $exists = CirclePostCollection::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                CirclePostCollection::query()
                    ->where('post_id', $post->id)
                    ->where('user_id', $user->id)
                    ->delete();
                $post->decrement('favorite_count');
            } else {
                CirclePostCollection::query()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
                $post->increment('favorite_count');
            }
            $post->refresh();

            return $post;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function commentsForApi(CirclePost $post): array
    {
        return CircleComment::query()
            ->where('post_id', $post->id)
            ->where('status', CircleCommentStatus::Normal)
            ->orderBy('id')
            ->with('user')
            ->get()
            ->map(fn (CircleComment $c) => $this->commentToApiArray($c))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function commentToApiArray(CircleComment $c): array
    {
        $c->loadMissing('user');
        $u = $c->user;

        return [
            'id' => (string) $c->id,
            'postId' => (string) $c->post_id,
            'userId' => (string) $c->user_id,
            'nickname' => (string) ($u?->name ?? ''),
            'avatar' => (string) ($u?->avatar_url ?? ''),
            'content' => (string) $c->content,
            'createdAt' => $c->created_at->toIso8601String(),
        ];
    }

    public function addComment(User $user, CirclePost $post, string $content): CircleComment
    {
        return DB::transaction(function () use ($user, $post, $content): CircleComment {
            $comment = CircleComment::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => $content,
                'status' => CircleCommentStatus::Normal,
            ]);
            $post->increment('comment_count');

            return $comment;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function myPosts(User $user): array
    {
        return CirclePost::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CirclePost $p) => $this->postToApiArray($p, (int) $user->id))
            ->all();
    }
}

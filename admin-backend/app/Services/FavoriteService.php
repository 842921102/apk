<?php

namespace App\Services;

use App\Models\Favorite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class FavoriteService
{
    /**
     * @param  array{keyword?: string|null, source_type?: string|null, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, mixed>}
     */
    public function paginateForUser(int $userId, array $filters): array
    {
        $query = Favorite::query()->where('user_id', $userId);

        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        if ($keyword !== '') {
            $query->where('title', 'like', '%'.$keyword.'%');
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', (string) $filters['source_type']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $perPage = max(1, min(100, (int) $perPage));

        /** @var LengthAwarePaginator<int, Favorite> $page */
        $page = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $filters['page'] ?? null);

        return [
            'items' => array_map(fn (Favorite $f) => $this->toApiArray($f), $page->items()),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(int $userId, array $data): Favorite
    {
        $sourceType = (string) $data['source_type'];
        $sourceId = isset($data['source_id']) && $data['source_id'] !== null && $data['source_id'] !== ''
            ? (string) $data['source_id']
            : null;

        $attributes = [
            'title' => (string) $data['title'],
            'cuisine' => $data['cuisine'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'recipe_content' => (string) $data['recipe_content'],
            'extra_payload' => $data['extra_payload'] ?? null,
        ];

        if ($sourceId !== null) {
            return Favorite::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ],
                $attributes,
            );
        }

        $favorite = new Favorite($attributes + [
            'user_id' => $userId,
            'source_type' => $sourceType,
            'source_id' => null,
        ]);
        $favorite->save();

        return $favorite;
    }

    public function isFavorited(int $userId, string $sourceType, ?string $sourceId): ?Favorite
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return Favorite::query()
            ->where('user_id', $userId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    public function deleteIfOwnedByUser(Favorite $favorite, int $userId): bool
    {
        if ((int) $favorite->user_id !== $userId) {
            return false;
        }
        $favorite->delete();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(Favorite $favorite): array
    {
        return [
            'id' => $favorite->id,
            'user_id' => (string) $favorite->user_id,
            'source_type' => $favorite->source_type,
            'source_id' => $favorite->source_id,
            'title' => $favorite->title,
            'cuisine' => $favorite->cuisine,
            'ingredients' => $favorite->ingredients,
            'recipe_content' => $favorite->recipe_content,
            'extra_payload' => $favorite->extra_payload,
            'created_at' => $favorite->created_at?->toIso8601String(),
            'updated_at' => $favorite->updated_at?->toIso8601String(),
        ];
    }
}

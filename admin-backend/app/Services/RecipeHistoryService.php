<?php

namespace App\Services;

use App\Models\RecipeHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class RecipeHistoryService
{
    /**
     * @param  array{keyword?: string|null, source_type?: string|null, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, mixed>}
     */
    public function paginateForUser(int $userId, array $filters): array
    {
        $query = RecipeHistory::query()->where('user_id', $userId);

        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        if ($keyword !== '') {
            $query->where('title', 'like', '%'.$keyword.'%');
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', (string) $filters['source_type']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(1, min(100, $perPage));

        /** @var LengthAwarePaginator<int, RecipeHistory> $pageModel */
        $pageModel = $query->orderByDesc('id')->paginate(
            $perPage,
            ['*'],
            'page',
            $filters['page'] ?? null,
        );

        return [
            'items' => array_map(fn (RecipeHistory $h): array => $this->toApiArray($h), $pageModel->items()),
            'pagination' => [
                'current_page' => $pageModel->currentPage(),
                'per_page' => $pageModel->perPage(),
                'total' => $pageModel->total(),
                'last_page' => $pageModel->lastPage(),
            ],
        ];
    }

    /**
     * 管理端：可查看全部历史。
     *
     * @param  array{keyword?: string|null, source_type?: string|null, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, mixed>}
     */
    public function paginateAll(array $filters): array
    {
        $query = RecipeHistory::query();

        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        if ($keyword !== '') {
            $query->where('title', 'like', '%'.$keyword.'%');
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', (string) $filters['source_type']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(1, min(100, $perPage));

        /** @var LengthAwarePaginator<int, RecipeHistory> $pageModel */
        $pageModel = $query->orderByDesc('id')->paginate(
            $perPage,
            ['*'],
            'page',
            $filters['page'] ?? null,
        );

        return [
            'items' => array_map(fn (RecipeHistory $h): array => $this->toApiArray($h), $pageModel->items()),
            'pagination' => [
                'current_page' => $pageModel->currentPage(),
                'per_page' => $pageModel->perPage(),
                'total' => $pageModel->total(),
                'last_page' => $pageModel->lastPage(),
            ],
        ];
    }

    public function createForUser(int $userId, array $data): RecipeHistory
    {
        return RecipeHistory::query()->create([
            'user_id' => $userId,
            'source_type' => (string) $data['source_type'],
            'source_id' => isset($data['source_id']) && $data['source_id'] !== '' ? (string) $data['source_id'] : null,
            'title' => (string) $data['title'],
            'cuisine' => $data['cuisine'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'request_payload' => $data['request_payload'] ?? null,
            'response_content' => (string) $data['response_content'],
            'extra_payload' => $data['extra_payload'] ?? null,
        ]);
    }

    public function toApiArray(RecipeHistory $h): array
    {
        return [
            'id' => $h->id,
            'user_id' => (string) $h->user_id,
            'source_type' => $h->source_type,
            'source_id' => $h->source_id,
            'title' => $h->title,
            'cuisine' => $h->cuisine,
            'ingredients' => $h->ingredients,
            'request_payload' => $h->request_payload,
            'response_content' => $h->response_content,
            'extra_payload' => $h->extra_payload,
            'created_at' => $h->created_at?->toIso8601String(),
            'updated_at' => $h->updated_at?->toIso8601String(),
        ];
    }
}


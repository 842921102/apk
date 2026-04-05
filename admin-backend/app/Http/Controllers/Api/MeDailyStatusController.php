<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertUserDailyStatusRequest;
use App\Models\UserDailyStatus;
use App\Services\RecommendationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeDailyStatusController extends Controller
{
    public function __construct(
        private readonly RecommendationContextService $recommendationContext,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        $date = Carbon::today()->toDateString();
        $daily = UserDailyStatus::query()
            ->where('user_id', $user->id)
            ->whereDate('status_date', $date)
            ->first();

        return response()->json([
            'today_status' => $daily ? $this->serializeDaily($daily) : null,
            'recommendation_context_tags' => $this->recommendationContext->buildTagsForUser($user),
        ]);
    }

    public function upsertToday(UpsertUserDailyStatusRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        $user->ensureProfile();

        $data = $request->validated();
        $dateStr = isset($data['status_date'])
            ? Carbon::parse((string) $data['status_date'])->toDateString()
            : Carbon::today()->toDateString();
        unset($data['status_date']);

        $daily = UserDailyStatus::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'status_date' => $dateStr,
            ],
            array_merge($data, [
                'status_date' => $dateStr,
            ]),
        );

        return response()->json([
            'today_status' => $this->serializeDaily($daily->fresh()),
            'recommendation_context_tags' => $this->recommendationContext->buildTagsForUser($user->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDaily(UserDailyStatus $d): array
    {
        return [
            'status_date' => $d->status_date->format('Y-m-d'),
            'mood' => $d->mood,
            'appetite_state' => $d->appetite_state,
            'body_state' => $d->body_state,
            'wanted_food_style' => $d->wanted_food_style,
            'flavor_tags' => array_values($d->flavor_tags ?? []),
            'taboo_tags' => array_values($d->taboo_tags ?? []),
            'period_status' => $d->period_status,
            'note' => $d->note,
        ];
    }
}

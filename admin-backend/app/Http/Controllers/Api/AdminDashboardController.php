<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboard,
    ) {}

    public function overview(): JsonResponse
    {
        $now = Carbon::now();
        $raw = $this->dashboard->overview($now);

        return response()->json([
            'recommend_count' => $raw['recommend_count'],
            'active_users' => $raw['active_users'],
            'favorites_count' => $raw['favorites_count'],
            'recipe_view_count' => $raw['recipe_view_count'],
            'reroll_count' => $raw['reroll_count'],
            'feedback_count' => $raw['feedback_count'],
            'yesterday_same_window' => [
                'recommend_count' => $raw['recommend_count_yesterday_same_window'],
                'active_users' => $raw['active_users_yesterday_same_window'],
                'favorites_count' => $raw['favorites_count_yesterday_same_window'],
                'recipe_view_count' => $raw['recipe_view_count_yesterday_same_window'],
                'reroll_count' => $raw['reroll_count_yesterday_same_window'],
                'feedback_count' => $raw['feedback_count_yesterday_same_window'],
            ],
        ]);
    }

    public function trends(): JsonResponse
    {
        return response()->json($this->dashboard->trends(Carbon::now()));
    }

    public function rankings(): JsonResponse
    {
        return response()->json($this->dashboard->rankings(Carbon::now()));
    }

    public function health(): JsonResponse
    {
        return response()->json($this->dashboard->health(Carbon::now()));
    }
}

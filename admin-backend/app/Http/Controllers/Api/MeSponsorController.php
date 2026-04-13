<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 小程序端赞助身份：取消后个人中心恢复「普通用户」，不涉及退款。
 */
class MeSponsorController extends Controller
{
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        if ($user->is_sponsor || $user->sponsor_until !== null) {
            $user->is_sponsor = false;
            $user->sponsor_until = null;
            $user->save();
        }

        return response()->json([
            'is_sponsor' => false,
            'sponsor_until' => null,
        ]);
    }
}

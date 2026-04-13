<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitOnboardingProfileRequest;
use App\Http\Requests\Api\UpdateUserProfileRequest;
use App\Models\UserDailyStatus;
use App\Models\UserProfile;
use App\Services\RecommendationContextService;
use App\Services\RecommendationEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeProfileController extends Controller
{
    public function __construct(
        private readonly RecommendationContextService $recommendationContext,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        $profile = $user->ensureProfile();
        $today = Carbon::today()->toDateString();
        $daily = UserDailyStatus::query()
            ->where('user_id', $user->id)
            ->whereDate('status_date', $today)
            ->first();

        return response()->json([
            'profile' => $this->serializeProfile($profile),
            'today_status' => $daily ? $this->serializeDaily($daily) : null,
            'recommendation_context_tags' => $this->recommendationContext->buildTagsForUser($user),
            'needs_onboarding' => $profile->onboarding_completed_at === null,
            'is_sponsor' => $user->isSponsorEffective(),
            'sponsor_until' => $user->sponsor_until?->toIso8601String(),
            'nickname' => (string) $user->name,
        ]);
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        $profile = $user->ensureProfile();
        $data = $request->validated();

        if (array_key_exists('nickname', $data)) {
            $rawNick = $data['nickname'];
            unset($data['nickname']);
            $trimmed = is_string($rawNick) ? trim($rawNick) : '';
            $user->name = $trimmed !== '' ? mb_substr($trimmed, 0, 64) : '微信用户';
            $user->save();
        }

        if (! empty($data['complete_onboarding'])) {
            $profile->onboarding_completed_at = $profile->onboarding_completed_at ?? now();
            unset($data['complete_onboarding']);
        } else {
            unset($data['complete_onboarding']);
        }

        $onboardingVersion = $data['onboarding_version'] ?? null;
        unset($data['onboarding_version']);

        $profile->fill($data);
        if ($onboardingVersion !== null) {
            $profile->onboarding_version = (int) $onboardingVersion;
        }
        $profile->save();

        $changedKeys = array_keys($data);
        if ($changedKeys !== []) {
            $this->recommendationEventRecorder->profilePreferenceUpdated($user, $changedKeys);
        }

        $user->refresh();

        return response()->json([
            'profile' => $this->serializeProfile($profile->fresh()),
            'recommendation_context_tags' => $this->recommendationContext->buildTagsForUser($user),
            'needs_onboarding' => $profile->fresh()->onboarding_completed_at === null,
            'is_sponsor' => $user->isSponsorEffective(),
            'sponsor_until' => $user->sponsor_until?->toIso8601String(),
            'nickname' => (string) $user->fresh()->name,
        ]);
    }

    /**
     * 首次问卷提交：写入完整画像并标记 onboarding 完成时间 / 版本。
     */
    public function submitOnboarding(SubmitOnboardingProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['message' => 'unauthenticated']], 401);
        }

        $profile = $user->ensureProfile();
        $data = $request->validated();
        $version = isset($data['onboarding_version']) ? (int) $data['onboarding_version'] : 1;
        unset($data['onboarding_version']);

        $dietGoal = isset($data['diet_goal']) && is_array($data['diet_goal']) ? array_values($data['diet_goal']) : [];

        $profile->fill($data);
        $profile->diet_preferences = $dietGoal;
        $profile->health_goal = $dietGoal[0] ?? null;
        $profile->onboarding_completed_at = now();
        $profile->onboarding_version = $version;
        $profile->save();

        $this->recommendationEventRecorder->onboardingCompleted($user, $version);

        $user->refresh();

        return response()->json([
            'profile' => $this->serializeProfile($profile->fresh()),
            'recommendation_context_tags' => $this->recommendationContext->buildTagsForUser($user),
            'needs_onboarding' => false,
            'is_sponsor' => $user->isSponsorEffective(),
            'sponsor_until' => $user->sponsor_until?->toIso8601String(),
            'nickname' => (string) $user->fresh()->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(UserProfile $p): array
    {
        $dietGoal = is_array($p->diet_goal) ? $p->diet_goal : [];
        $dietPreferences = is_array($p->diet_preferences) ? $p->diet_preferences : [];

        return [
            'user_id' => (int) $p->user_id,
            'birthday' => $p->birthday?->format('Y-m-d'),
            'gender' => $p->gender,
            'height_cm' => $p->height_cm,
            'weight_kg' => $p->weight_kg,
            'target_weight_kg' => $p->target_weight_kg,
            'flavor_preferences' => $p->flavor_preferences ?? [],
            'cuisine_preferences' => $p->cuisine_preferences ?? [],
            'dislike_ingredients' => $p->dislike_ingredients ?? [],
            'allergy_ingredients' => $p->allergy_ingredients ?? [],
            'taboo_ingredients' => $p->taboo_ingredients ?? [],
            'diet_preferences' => $dietPreferences !== [] ? $dietPreferences : $dietGoal,
            'diet_goal' => $dietGoal,
            'health_goal' => $p->health_goal,
            'cooking_frequency' => $p->cooking_frequency,
            'meal_pattern' => $p->meal_pattern,
            'family_size' => $p->family_size,
            'lifestyle_tags' => $p->lifestyle_tags ?? [],
            'religious_restrictions' => $p->religious_restrictions ?? [],
            'period_tracking' => is_array($p->period_tracking) ? $p->period_tracking : [],
            'recommendation_style' => $p->recommendation_style,
            'destiny_mode_enabled' => (bool) $p->destiny_mode_enabled,
            'period_feature_enabled' => (bool) $p->period_feature_enabled,
            'accepts_product_recommendation' => (bool) $p->accepts_product_recommendation,
            'onboarding_completed_at' => $p->onboarding_completed_at?->toIso8601String(),
            'onboarding_version' => $p->onboarding_version,
        ];
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

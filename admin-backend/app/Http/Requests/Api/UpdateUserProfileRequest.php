<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['birthday', 'meal_pattern', 'recommendation_style'] as $k) {
            $v = $this->input($k);
            if ($v === '') {
                $this->merge([$k => null]);
            }
        }

        $pt = $this->input('period_tracking');
        if (is_array($pt)) {
            $lps = $pt['last_period_start'] ?? null;
            if ($lps === '') {
                $pt['last_period_start'] = null;
            }
            $this->merge(['period_tracking' => $pt]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['unknown', 'male', 'female', 'undisclosed'])],
            'height_cm' => ['nullable', 'integer', 'min:50', 'max:260'],
            'weight_kg' => ['nullable', 'numeric', 'min:15', 'max:400'],
            'target_weight_kg' => ['nullable', 'numeric', 'min:15', 'max:400'],
            'flavor_preferences' => ['nullable', 'array', 'max:48'],
            'flavor_preferences.*' => ['string', 'max:64'],
            'taboo_ingredients' => ['nullable', 'array', 'max:96'],
            'taboo_ingredients.*' => ['string', 'max:64'],
            'diet_preferences' => ['nullable', 'array', 'max:24'],
            'diet_preferences.*' => ['string', 'max:64'],
            'diet_goal' => ['nullable', 'array', 'max:48'],
            'diet_goal.*' => ['string', 'max:128'],
            'cuisine_preferences' => ['nullable', 'array', 'max:48'],
            'cuisine_preferences.*' => ['string', 'max:64'],
            'dislike_ingredients' => ['nullable', 'array', 'max:96'],
            'dislike_ingredients.*' => ['string', 'max:64'],
            'allergy_ingredients' => ['nullable', 'array', 'max:96'],
            'allergy_ingredients.*' => ['string', 'max:64'],
            'cooking_frequency' => ['nullable', 'string', Rule::in(['often', 'sometimes', 'rarely', 'takeout'])],
            'meal_pattern' => ['nullable', 'string', 'max:64'],
            'family_size' => ['nullable', 'string', Rule::in(['single', 'couple', 'family3', 'family5'])],
            'lifestyle_tags' => ['nullable', 'array', 'max:48'],
            'lifestyle_tags.*' => ['string', 'max:64'],
            'religious_restrictions' => ['nullable', 'array', 'max:16'],
            'religious_restrictions.*' => ['string', 'max:64'],
            'period_tracking' => ['nullable', 'array'],
            'period_tracking.last_period_start' => ['nullable', 'date'],
            'period_tracking.cycle_days' => ['nullable', 'integer', 'min:18', 'max:50'],
            'health_goal' => ['nullable', 'string', 'max:255'],
            'recommendation_style' => ['nullable', 'string', 'max:64'],
            'destiny_mode_enabled' => ['nullable', 'boolean'],
            'period_feature_enabled' => ['nullable', 'boolean'],
            'accepts_product_recommendation' => ['nullable', 'boolean'],
            'complete_onboarding' => ['nullable', 'boolean'],
            'onboarding_version' => ['nullable', 'integer', 'min:1', 'max:65535'],
            /** 同步至 `users.name`（展示昵称），与 user_profiles 无关 */
            'nickname' => ['nullable', 'string', 'max:64'],
        ];
    }
}

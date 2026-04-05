<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitOnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $b = $this->input('birthday');
        if ($b === '' || $b === null) {
            $this->merge(['birthday' => null]);
        }
        $mp = $this->input('meal_pattern');
        if ($mp === '') {
            $this->merge(['meal_pattern' => null]);
        }
        $rs = $this->input('recommendation_style');
        if ($rs === '') {
            $this->merge(['recommendation_style' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'birthday' => ['nullable', 'date'],
            'gender' => ['required', 'string', Rule::in(['unknown', 'male', 'female', 'undisclosed'])],
            'height_cm' => ['required', 'integer', 'min:80', 'max:250'],
            'weight_kg' => ['required', 'numeric', 'min:20', 'max:300'],
            'target_weight_kg' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'diet_goal' => ['nullable', 'array', 'max:48'],
            'diet_goal.*' => ['string', 'max:128'],
            'flavor_preferences' => ['nullable', 'array', 'max:48'],
            'flavor_preferences.*' => ['string', 'max:64'],
            'cuisine_preferences' => ['nullable', 'array', 'max:48'],
            'cuisine_preferences.*' => ['string', 'max:64'],
            'dislike_ingredients' => ['nullable', 'array', 'max:96'],
            'dislike_ingredients.*' => ['string', 'max:64'],
            'allergy_ingredients' => ['nullable', 'array', 'max:96'],
            'allergy_ingredients.*' => ['string', 'max:64'],
            'taboo_ingredients' => ['nullable', 'array', 'max:96'],
            'taboo_ingredients.*' => ['string', 'max:64'],
            'cooking_frequency' => ['required', 'string', Rule::in(['often', 'sometimes', 'rarely', 'takeout'])],
            'meal_pattern' => ['nullable', 'string', 'max:64'],
            'family_size' => ['required', 'string', Rule::in(['single', 'couple', 'family3', 'family5'])],
            'lifestyle_tags' => ['nullable', 'array', 'max:48'],
            'lifestyle_tags.*' => ['string', 'max:64'],
            'recommendation_style' => ['nullable', 'string', 'max:64'],
            'destiny_mode_enabled' => ['required', 'boolean'],
            'period_feature_enabled' => ['required', 'boolean'],
            'accepts_product_recommendation' => ['required', 'boolean'],
            'onboarding_version' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];
    }
}

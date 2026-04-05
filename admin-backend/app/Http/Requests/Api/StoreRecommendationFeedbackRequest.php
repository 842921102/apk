<?php

namespace App\Http\Requests\Api;

use App\Support\RecommendationFeedbackReason;
use App\Support\RecommendationFeedbackTarget;
use App\Support\RecommendationFeedbackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreRecommendationFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feedback_type' => ['required', 'string', Rule::enum(RecommendationFeedbackType::class)],
            'feedback_reason' => ['nullable', 'string', Rule::enum(RecommendationFeedbackReason::class)],
            'feedback_target' => ['nullable', 'string', Rule::enum(RecommendationFeedbackTarget::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $typeRaw = $this->input('feedback_type');
            try {
                $type = RecommendationFeedbackType::from((string) $typeRaw);
            } catch (\ValueError) {
                return;
            }

            $reason = $this->input('feedback_reason');

            if ($type->requiresSecondaryReason()) {
                if ($reason === null || $reason === '') {
                    $validator->errors()->add('feedback_reason', __('validation.required', ['attribute' => 'feedback_reason']));
                }
            } elseif ($reason !== null && $reason !== '') {
                $validator->errors()->add('feedback_reason', 'must_be_empty_for_this_feedback_type');
            }
        });
    }
}

<?php

namespace App\Http\Requests\Api;

use App\Support\UserDailyStatusMvp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertUserDailyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_date' => ['nullable', 'date'],
            'mood' => ['nullable', 'string', Rule::in(UserDailyStatusMvp::moods())],
            'appetite_state' => ['nullable', 'string', 'max:64'],
            'body_state' => ['nullable', 'string', Rule::in(UserDailyStatusMvp::bodyStates())],
            'wanted_food_style' => ['nullable', 'string', Rule::in(UserDailyStatusMvp::wantedFoodStyles())],
            'flavor_tags' => ['nullable', 'array', 'max:16'],
            'flavor_tags.*' => ['string', Rule::in(UserDailyStatusMvp::flavorTagKeys())],
            'taboo_tags' => ['nullable', 'array', 'max:16'],
            'taboo_tags.*' => ['string', Rule::in(UserDailyStatusMvp::tabooTagKeys())],
            'period_status' => ['nullable', 'string', Rule::in(UserDailyStatusMvp::periodStatuses())],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function passedValidation(): void
    {
        $taboo = $this->input('taboo_tags');
        if (is_array($taboo) && in_array('none', $taboo, true)) {
            $this->merge(['taboo_tags' => ['none']]);
        }
    }
}

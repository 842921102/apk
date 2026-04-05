<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TodayEatSelectAlternativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recommendation_session_id' => ['required', 'uuid'],
            'selected_dish' => ['required', 'string', 'min:1', 'max:80'],
            'preferences' => ['sometimes', 'array'],
            'preferences.taste' => ['nullable', 'string', 'max:200'],
            'preferences.avoid' => ['nullable', 'string', 'max:400'],
            'preferences.people' => ['nullable', 'integer', 'min:1', 'max:30'],
            'locale' => ['nullable', 'string', 'max:32'],
        ];
    }
}

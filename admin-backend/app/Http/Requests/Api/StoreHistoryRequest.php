<?php

namespace App\Http\Requests\Api;

use App\Support\FavoriteSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_type' => ['required', 'string', Rule::in(FavoriteSourceType::values())],
            'source_id' => ['nullable', 'string', 'max:96'],
            'title' => ['required', 'string', 'max:512'],
            'cuisine' => ['nullable', 'string', 'max:128'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*' => ['string', 'max:255'],
            'request_payload' => ['nullable', 'array'],
            'response_content' => ['required', 'string'],
            'extra_payload' => ['nullable', 'array'],
        ];
    }
}


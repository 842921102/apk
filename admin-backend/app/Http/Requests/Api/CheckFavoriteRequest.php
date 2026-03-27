<?php

namespace App\Http\Requests\Api;

use App\Support\FavoriteSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckFavoriteRequest extends FormRequest
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
            'source_id' => ['required', 'string', 'max:96'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveAiSceneConfigRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'min:1'],
            'provider_id' => ['required', 'integer', 'min:1'],
            'model_id' => ['required', 'integer', 'min:1'],
            'api_key' => ['nullable', 'string', 'max:2048'],
            'base_url_override' => ['nullable', 'string', 'max:512'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'timeout_ms' => ['nullable', 'integer', 'min:1000', 'max:120000'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }
}


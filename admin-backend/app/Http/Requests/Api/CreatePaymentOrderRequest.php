<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreatePaymentOrderRequest extends FormRequest
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
            'business_type' => ['required', 'string', 'max:64'],
            'business_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount_fen' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('business_type', '') !== 'sponsor_love') {
                return;
            }
            $fen = (int) $this->input('amount_fen', 0);
            if ($fen > 300000) {
                $validator->errors()->add('amount_fen', '爱心赞助单笔金额不能超过 3000 元');
            }
        });
    }
}

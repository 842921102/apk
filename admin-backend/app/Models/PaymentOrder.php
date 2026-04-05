<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_no',
    'user_id',
    'business_type',
    'business_id',
    'title',
    'description',
    'amount_fen',
    'currency',
    'pay_channel',
    'status',
    'openid',
    'prepay_id',
    'transaction_id',
    'notify_payload',
    'paid_at',
    'expired_at',
])]
class PaymentOrder extends Model
{
    protected $casts = [
        'amount_fen' => 'integer',
        'business_id' => 'integer',
        'notify_payload' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null && trim($value) !== '' ? $value : null,
            set: fn (?string $value) => $value !== null && trim($value) !== '' ? trim($value) : null,
        );
    }
}

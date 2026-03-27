<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_no',
    'user_id',
    'product_id',
    'product_name',
    'product_image',
    'product_price',
    'quantity',
    'total_amount',
    'pay_amount',
    'order_status',
    'pay_status',
    'remark',
    'logistics_company',
    'logistics_no',
    'paid_at',
    'shipped_at',
    'completed_at',
    'cancelled_at',
])]
class Order extends Model
{
    protected $casts = [
        'product_id' => 'integer',
        'product_price' => 'integer',
        'quantity' => 'integer',
        'total_amount' => 'integer',
        'pay_amount' => 'integer',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }
}

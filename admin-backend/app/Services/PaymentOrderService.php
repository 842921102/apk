<?php

namespace App\Services;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class PaymentOrderService
{
    public function createOrderForUser(User $user, array $payload): PaymentOrder
    {
        $openid = (string) ($user->wechat_openid ?? '');
        if ($openid === '') {
            throw new RuntimeException('当前账号缺少微信 openid，请先重新登录微信。');
        }

        return PaymentOrder::query()->create([
            'order_no' => $this->generateOrderNo(),
            'user_id' => $user->id,
            'business_type' => (string) $payload['business_type'],
            'business_id' => isset($payload['business_id']) ? (int) $payload['business_id'] : null,
            'title' => (string) $payload['title'],
            'description' => isset($payload['description']) ? (string) $payload['description'] : null,
            'amount_fen' => (int) $payload['amount_fen'],
            'currency' => 'CNY',
            'pay_channel' => 'wechat_mini',
            'status' => 'pending',
            'openid' => $openid,
            'expired_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * @param  array<string, mixed>  $notifyPayload
     */
    public function markPaidFromNotify(array $notifyPayload): ?PaymentOrder
    {
        $resourceData = $notifyPayload['resource_data'] ?? null;
        if (! is_array($resourceData)) {
            return null;
        }

        $orderNo = (string) ($resourceData['out_trade_no'] ?? '');
        if ($orderNo === '') {
            return null;
        }

        return DB::transaction(function () use ($orderNo, $notifyPayload, $resourceData): ?PaymentOrder {
            $order = PaymentOrder::query()
                ->where('order_no', $orderNo)
                ->lockForUpdate()
                ->first();
            if (! $order) {
                return null;
            }

            $order->notify_payload = $notifyPayload;
            $tradeState = (string) ($resourceData['trade_state'] ?? '');
            $transactionId = (string) ($resourceData['transaction_id'] ?? '');

            if ($tradeState === 'SUCCESS') {
                if ($order->status !== 'paid') {
                    $order->status = 'paid';
                    $order->paid_at = now();
                }
                $order->transaction_id = $transactionId !== '' ? $transactionId : $order->transaction_id;
            } elseif (in_array($tradeState, ['CLOSED', 'REVOKED', 'PAYERROR'], true)) {
                if ($order->status === 'pending') {
                    $order->status = $tradeState === 'CLOSED' ? 'closed' : 'failed';
                }
            }

            $order->save();

            // 预留业务联动：可按 business_type/business_id 在此更新业务状态。
            return $order;
        });
    }

    private function generateOrderNo(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $orderNo = 'PAY'.now()->format('YmdHis').Str::upper(Str::random(8));
            $exists = PaymentOrder::query()->where('order_no', $orderNo)->exists();
            if (! $exists) {
                return $orderNo;
            }
        }

        throw new RuntimeException('生成订单号失败，请稍后重试');
    }
}

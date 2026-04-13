<?php

namespace App\Services;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class PaymentOrderService
{
    /**
     * 将已过期的待支付单标记为 closed（与微信侧未支付/超时一致，避免后台长期堆 pending）。
     */
    public function closeExpiredPendingPaymentOrders(): int
    {
        return PaymentOrder::query()
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->update(['status' => 'closed']);
    }

    public function createOrderForUser(User $user, array $payload): PaymentOrder
    {
        $openid = (string) ($user->wechat_openid ?? '');
        if ($openid === '') {
            throw new RuntimeException('当前账号缺少微信 openid，请先重新登录微信。');
        }

        $this->closeExpiredPendingPaymentOrders();

        $businessType = (string) $payload['business_type'];
        $amountFen = (int) $payload['amount_fen'];
        $reuse = $this->findReusablePendingSponsorOrder($user->id, $businessType, $amountFen);
        if ($reuse !== null) {
            return $reuse;
        }

        return PaymentOrder::query()->create([
            'order_no' => $this->generateOrderNo(),
            'user_id' => $user->id,
            'business_type' => $businessType,
            'business_id' => isset($payload['business_id']) ? (int) $payload['business_id'] : null,
            'title' => (string) $payload['title'],
            'description' => isset($payload['description']) ? (string) $payload['description'] : null,
            'amount_fen' => $amountFen,
            'currency' => 'CNY',
            'pay_channel' => 'wechat_mini',
            'status' => 'pending',
            'openid' => $openid,
            'expired_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * 赞助类订单：同一用户、同一业务类型、同一金额下，若已有未过期 pending，则复用，避免每次点「微信支付」都新建一行流水。
     */
    private function findReusablePendingSponsorOrder(int $userId, string $businessType, int $amountFen): ?PaymentOrder
    {
        $sponsorTypes = ['sponsor_love'];
        if (! in_array($businessType, $sponsorTypes, true)) {
            return null;
        }

        return PaymentOrder::query()
            ->where('user_id', $userId)
            ->where('business_type', $businessType)
            ->where('amount_fen', $amountFen)
            ->where('status', 'pending')
            ->where(function ($q): void {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
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

            if ($order->status === 'paid') {
                $this->grantSponsorFromPaidOrder($order);
            }

            return $order;
        });
    }

    /**
     * 当异步通知不可达或延迟时，通过「查单」接口对齐微信侧支付状态，并复用与 notify 相同的落库与赞助标记逻辑。
     */
    public function trySyncPaidStatusFromWechatQuery(PaymentOrder $order, WechatPayService $wechat): void
    {
        if ($order->status !== 'pending') {
            return;
        }
        if ($order->expired_at !== null && $order->expired_at->isPast()) {
            return;
        }

        $cacheKey = 'wechat_pay_query_throttle:'.$order->id;
        if (! Cache::add($cacheKey, 1, 3)) {
            return;
        }

        $json = $wechat->queryJsapiTransactionByOutTradeNo((string) $order->order_no);
        if ($json === null) {
            return;
        }

        $tradeState = (string) ($json['trade_state'] ?? '');
        if ($tradeState !== 'SUCCESS') {
            return;
        }

        $resourceData = [
            'out_trade_no' => (string) ($json['out_trade_no'] ?? $order->order_no),
            'trade_state' => 'SUCCESS',
            'transaction_id' => (string) ($json['transaction_id'] ?? ''),
        ];

        $this->markPaidFromNotify([
            'resource_data' => $resourceData,
            'sync_source' => 'wechat_query',
        ]);
    }

    /**
     * 幂等：订单已为 paid 时补写赞助身份（应对 notify 已更新订单但 grant 未执行等异常情况）。
     */
    public function ensureSponsorGrantForPaidOrder(PaymentOrder $order): void
    {
        if ($order->status !== 'paid') {
            return;
        }
        $this->grantSponsorFromPaidOrder($order);
    }

    /**
     * 月赞助 / 爱心赞助 支付成功后：标记赞助用户，并在 sponsor_until 上顺延 1 个月（可叠加）。
     *
     * business_type：sponsor_monthly | sponsor_love
     */
    private function grantSponsorFromPaidOrder(PaymentOrder $order): void
    {
        $types = ['sponsor_monthly', 'sponsor_love'];
        if (! in_array($order->business_type, $types, true)) {
            return;
        }

        $user = User::query()->whereKey($order->user_id)->lockForUpdate()->first();
        if ($user === null) {
            return;
        }
        $now = now();
        if ($user->sponsor_until !== null && $user->sponsor_until->isFuture()) {
            $until = $user->sponsor_until->copy()->addMonth();
        } else {
            $until = $now->copy()->addMonth();
        }
        $user->is_sponsor = true;
        $user->sponsor_until = $until;
        $user->save();
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePaymentOrderRequest;
use App\Models\PaymentOrder;
use App\Services\PaymentOrderService;
use App\Services\WechatPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

final class PaymentOrderController extends Controller
{
    public function __construct(
        private PaymentOrderService $paymentOrderService,
        private WechatPayService $wechatPayService,
    ) {}

    /**
     * 当前用户爱心赞助且已支付成功的订单，供小程序赞助页「赞助记录」展示。
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = PaymentOrder::query()
            ->where('user_id', $user->id)
            ->where('business_type', 'sponsor_love')
            ->where('status', 'paid')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        return response()->json([
            'data' => $orders->map(fn (PaymentOrder $o): array => $this->toApiArray($o))->values()->all(),
        ]);
    }

    public function store(CreatePaymentOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $order = $this->paymentOrderService->createOrderForUser($user, $request->validated());

        return response()->json([
            'data' => $this->toApiArray($order),
        ], 201);
    }

    public function wechatPrepay(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = PaymentOrder::query()->findOrFail($id);
        if ((int) $order->user_id !== (int) $user->id) {
            abort(404, 'Not found.');
        }
        if ($order->status !== 'pending') {
            return response()->json(['message' => '仅 pending 订单可发起支付'], 422);
        }

        try {
            $payParams = $this->wechatPayService->createJsapiPrepay($order);
            $order->prepay_id = (string) ($payParams['prepayId'] ?? '');
            $order->save();
        } catch (Throwable $e) {
            return response()->json(['message' => '发起微信支付失败', 'detail' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'timeStamp' => (string) $payParams['timeStamp'],
                'nonceStr' => (string) $payParams['nonceStr'],
                'package' => (string) $payParams['package'],
                'paySign' => (string) $payParams['paySign'],
                'signType' => (string) $payParams['signType'],
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = PaymentOrder::query()->findOrFail($id);
        if ((int) $order->user_id !== (int) $user->id) {
            abort(404, 'Not found.');
        }

        if ($order->status === 'pending') {
            try {
                $this->paymentOrderService->trySyncPaidStatusFromWechatQuery($order, $this->wechatPayService);
            } catch (Throwable) {
                // 查单失败不阻塞读单；轮询会重试
            }
            $order->refresh();
        }

        if ($order->status === 'paid') {
            $this->paymentOrderService->ensureSponsorGrantForPaidOrder($order);
        }

        return response()->json([
            'data' => $this->toApiArray($order->fresh()),
        ]);
    }

    public function notify(Request $request): JsonResponse
    {
        $headers = $request->headers->all();
        $rawBody = (string) $request->getContent();

        try {
            $payload = $this->wechatPayService->parseNotify($headers, $rawBody);
            $this->paymentOrderService->markPaidFromNotify($payload);
        } catch (RuntimeException $e) {
            return response()->json([
                'code' => 'FAIL',
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable) {
            return response()->json([
                'code' => 'FAIL',
                'message' => '服务器处理失败',
            ], 500);
        }

        return response()->json([
            'code' => 'SUCCESS',
            'message' => '成功',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toApiArray(PaymentOrder $order): array
    {
        return [
            'id' => (string) $order->id,
            'order_no' => (string) $order->order_no,
            'status' => (string) $order->status,
            'amount_fen' => (int) $order->amount_fen,
            'title' => (string) $order->title,
            'description' => $order->description !== null ? (string) $order->description : null,
            'business_type' => (string) $order->business_type,
            'business_id' => $order->business_id !== null ? (int) $order->business_id : null,
            'pay_channel' => (string) $order->pay_channel,
            'transaction_id' => $order->transaction_id !== null ? (string) $order->transaction_id : null,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'expired_at' => $order->expired_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}

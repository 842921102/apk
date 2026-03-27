<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = Order::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $order): array => $this->toApiArray($order))
            ->all();

        return response()->json(['items' => $items]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ((int) $order->user_id !== (int) $user->id) {
            abort(404, 'Not found.');
        }

        return response()->json(['data' => $this->toApiArray($order)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'consignee_name' => ['required', 'string', 'max:64'],
            'consignee_phone' => ['required', 'string', 'max:32'],
            'consignee_address' => ['required', 'string', 'max:500'],
        ]);

        $product = Product::query()
            ->whereKey((int) $validated['product_id'])
            ->where('status', 'online')
            ->firstOrFail();

        $quantity = (int) ($validated['quantity'] ?? 1);
        if ((int) $product->stock < $quantity) {
            return response()->json(['message' => '库存不足'], 422);
        }

        $product->stock = max(0, (int) $product->stock - $quantity);
        $product->save();

        $price = (int) $product->price;
        $images = is_array($product->images) ? $product->images : [];

        $order = Order::query()->create([
            'order_no' => 'WTE'.now()->format('YmdHis').Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_image' => (string) ($product->cover_image ?: (($images[0] ?? '') ?: '')),
            'product_price' => $price,
            'quantity' => $quantity,
            'total_amount' => $price * $quantity,
            'order_status' => 'pending',
            'pay_status' => 'unpaid',
            'consignee_name' => (string) $validated['consignee_name'],
            'consignee_phone' => (string) $validated['consignee_phone'],
            'consignee_address' => (string) $validated['consignee_address'],
            'logistics_company' => null,
            'logistics_no' => null,
        ]);

        return response()->json(['data' => $this->toApiArray($order)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function toApiArray(Order $order): array
    {
        return [
            'id' => (string) $order->id,
            'orderNo' => (string) $order->order_no,
            'userId' => (string) $order->user_id,
            'productId' => (string) $order->product_id,
            'productName' => (string) $order->product_name,
            'productImage' => (string) $order->product_image,
            'productPrice' => (int) $order->product_price,
            'quantity' => (int) $order->quantity,
            'totalAmount' => (int) $order->total_amount,
            'orderStatus' => (string) $order->order_status,
            'payStatus' => (string) $order->pay_status,
            'consigneeName' => (string) $order->consignee_name,
            'consigneePhone' => (string) $order->consignee_phone,
            'consigneeAddress' => (string) $order->consignee_address,
            'logisticsCompany' => $order->logistics_company ? (string) $order->logistics_company : null,
            'logisticsNo' => $order->logistics_no ? (string) $order->logistics_no : null,
            'createdAt' => $order->created_at->toIso8601String(),
        ];
    }
}

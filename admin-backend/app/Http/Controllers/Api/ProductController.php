<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        if ($product->status !== 'online') {
            abort(404, 'Not found.');
        }

        $images = is_array($product->images) ? array_values(array_filter($product->images, fn ($v) => is_string($v) && $v !== '')) : [];

        return response()->json([
            'data' => [
                'id' => (string) $product->id,
                'name' => (string) $product->name,
                'coverImage' => (string) $product->cover_image,
                'images' => $images,
                'price' => (int) $product->price,
                'originalPrice' => $product->original_price !== null ? (int) $product->original_price : null,
                'stock' => (int) $product->stock,
                'description' => (string) ($product->description ?? ''),
                'status' => (string) $product->status,
            ],
        ]);
    }
}

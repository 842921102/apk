<?php

use App\Http\Controllers\Api\CircleCommentController;
use App\Http\Controllers\Api\CirclePostController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\InternalAiRuntimeController;
use App\Http\Controllers\Api\InternalEatMemeController;
use App\Http\Controllers\Api\InternalFeatureDataController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Auth\WechatAuthController;
use App\Http\Middleware\AuthenticateLaravelAccessToken;
use Illuminate\Support\Facades\Route;

Route::post('/auth/wechat', [WechatAuthController::class, 'login']);

Route::get('/circle/posts', [CirclePostController::class, 'index']);
Route::get('/circle/posts/{post}', [CirclePostController::class, 'show']);
Route::get('/circle/posts/{post}/comments', [CircleCommentController::class, 'index']);
Route::get('/inspiration/posts', [CirclePostController::class, 'index']);
Route::get('/inspiration/posts/{post}', [CirclePostController::class, 'show']);
Route::get('/inspiration/posts/{post}/comments', [CircleCommentController::class, 'index']);
Route::get('/mall/products/{product}', [ProductController::class, 'show']);
Route::get('/uploads/cos/config', [UploadController::class, 'cosConfig']);

Route::get('/internal/ai-runtime/scenes/{sceneCode}', [InternalAiRuntimeController::class, 'scene']);
Route::get('/internal/eat-meme', [InternalEatMemeController::class, 'index']);
Route::post('/internal/eat-meme', [InternalEatMemeController::class, 'store']);
Route::delete('/internal/eat-meme/{eatMeme}', [InternalEatMemeController::class, 'destroy']);
Route::get('/internal/feature-data', [InternalFeatureDataController::class, 'index']);
Route::post('/internal/feature-data', [InternalFeatureDataController::class, 'store']);

Route::middleware([AuthenticateLaravelAccessToken::class])->group(function (): void {
    Route::post('/circle/posts', [CirclePostController::class, 'store']);
    Route::post('/circle/posts/{post}/like', [CirclePostController::class, 'toggleLike']);
    Route::post('/circle/posts/{post}/collect', [CirclePostController::class, 'toggleCollect']);
    Route::post('/circle/posts/{post}/comments', [CircleCommentController::class, 'store']);
    Route::get('/circle/me/posts', [CirclePostController::class, 'myPosts']);
    Route::get('/circle/my-posts', [CirclePostController::class, 'myPosts']);
    Route::post('/inspiration/posts', [CirclePostController::class, 'store']);
    Route::post('/inspiration/posts/{post}/like', [CirclePostController::class, 'toggleLike']);
    Route::post('/inspiration/posts/{post}/collect', [CirclePostController::class, 'toggleCollect']);
    Route::post('/inspiration/posts/{post}/comments', [CircleCommentController::class, 'store']);
    Route::get('/inspiration/me/posts', [CirclePostController::class, 'myPosts']);
    Route::get('/inspiration/my-posts', [CirclePostController::class, 'myPosts']);
    Route::get('/mall/orders', [OrderController::class, 'index']);
    Route::get('/mall/orders/{order}', [OrderController::class, 'show']);
    Route::post('/mall/orders', [OrderController::class, 'store']);
    Route::post('/uploads/cos', [UploadController::class, 'uploadToCos']);

    Route::get('/favorites/check', [FavoriteController::class, 'check']);
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::get('/favorites/{favorite}', [FavoriteController::class, 'show']);
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy']);

    Route::get('/histories', [HistoryController::class, 'index']);
    Route::post('/histories', [HistoryController::class, 'store']);
    Route::get('/histories/{history}', [HistoryController::class, 'show']);
    Route::delete('/histories/{history}', [HistoryController::class, 'destroy']);
});

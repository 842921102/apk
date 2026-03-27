<?php

use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\InternalEatMemeController;
use App\Http\Controllers\Api\InternalAiRuntimeController;
use App\Http\Controllers\Api\InternalFeatureDataController;
use App\Http\Controllers\Auth\WechatAuthController;
use App\Http\Middleware\AuthenticateLaravelAccessToken;
use Illuminate\Support\Facades\Route;

Route::post('/auth/wechat', [WechatAuthController::class, 'login']);
Route::get('/internal/ai-runtime/scenes/{sceneCode}', [InternalAiRuntimeController::class, 'scene']);
Route::get('/internal/eat-meme', [InternalEatMemeController::class, 'index']);
Route::post('/internal/eat-meme', [InternalEatMemeController::class, 'store']);
Route::delete('/internal/eat-meme/{eatMeme}', [InternalEatMemeController::class, 'destroy']);
Route::get('/internal/feature-data', [InternalFeatureDataController::class, 'index']);
Route::post('/internal/feature-data', [InternalFeatureDataController::class, 'store']);

Route::middleware([AuthenticateLaravelAccessToken::class])->group(function (): void {
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

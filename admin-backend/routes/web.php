<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AiModelConfigController;
use App\Http\Controllers\Auth\WechatAuthController;

Route::get('/', function () {
    return view('welcome');
});

// 兜底：确保在 php artisan serve 场景下也能命中微信登录接口
Route::post('/api/auth/wechat', [WechatAuthController::class, 'login'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ]);

Route::middleware(['web', 'auth'])->prefix('admin-api/ai-model-configs')->group(function (): void {
    Route::get('/', [AiModelConfigController::class, 'index']);
    Route::get('/{sceneCode}/options', [AiModelConfigController::class, 'options']);
    Route::put('/{sceneCode}', [AiModelConfigController::class, 'save']);
    Route::post('/{sceneCode}/test', [AiModelConfigController::class, 'test']);
});

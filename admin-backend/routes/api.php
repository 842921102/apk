<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\CircleCommentController;
use App\Http\Controllers\Api\CirclePostController;
use App\Http\Controllers\Api\DishRecipeController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\InternalAiRuntimeController;
use App\Http\Controllers\Api\InternalEatMemeController;
use App\Http\Controllers\Api\InternalFeatureDataController;
use App\Http\Controllers\Api\InternalMiniappWeatherController;
use App\Http\Controllers\Api\InternalRecommendationRecordController;
use App\Http\Controllers\Api\MeDailyStatusController;
use App\Http\Controllers\Api\MeProfileController;
use App\Http\Controllers\Api\MeSponsorController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentOrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecommendationRecordController;
use App\Http\Controllers\Api\TodayEatRecommendController;
use App\Http\Controllers\Api\TodayEatRerollController;
use App\Http\Controllers\Api\TodayEatSelectAlternativeController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserFeedbackController;
use App\Http\Controllers\Auth\WechatAuthController;
use App\Http\Middleware\AuthenticateLaravelAccessToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin/dashboard')->group(function (): void {
    Route::get('/overview', [AdminDashboardController::class, 'overview']);
    Route::get('/trends', [AdminDashboardController::class, 'trends']);
    Route::get('/rankings', [AdminDashboardController::class, 'rankings']);
    Route::get('/health', [AdminDashboardController::class, 'health']);
});

Route::post('/auth/wechat', [WechatAuthController::class, 'login']);
Route::post('/pay/wechat/notify', [PaymentOrderController::class, 'notify']);

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
Route::get('/internal/miniapp/weather/ambient', [InternalMiniappWeatherController::class, 'ambient']);
Route::get('/internal/recommendation-records/latest', [InternalRecommendationRecordController::class, 'latest']);

Route::middleware([AuthenticateLaravelAccessToken::class])->group(function (): void {
    Route::get('/me/profile', [MeProfileController::class, 'show']);
    Route::put('/me/profile', [MeProfileController::class, 'update']);
    Route::post('/me/profile/onboarding', [MeProfileController::class, 'submitOnboarding']);
    Route::post('/me/sponsor/cancel', [MeSponsorController::class, 'cancel']);
    Route::get('/user/profile', [MeProfileController::class, 'show']);
    Route::put('/user/profile', [MeProfileController::class, 'update']);
    Route::post('/user/profile/onboarding', [MeProfileController::class, 'submitOnboarding']);
    Route::get('/me/daily-status/today', [MeDailyStatusController::class, 'today']);
    Route::put('/me/daily-status/today', [MeDailyStatusController::class, 'upsertToday']);
    Route::post('/me/today-eat', TodayEatRecommendController::class);
    Route::post('/me/today-eat/reroll', TodayEatRerollController::class);
    Route::post('/me/today-eat/select-alternative', TodayEatSelectAlternativeController::class);

    Route::get('/me/dish-recipes/{dishRecipe}', [DishRecipeController::class, 'show']);

    Route::get('/me/recommendation-records', [RecommendationRecordController::class, 'index']);
    Route::get('/me/recommendation-records/{recommendation_record}', [RecommendationRecordController::class, 'show']);
    Route::post('/me/recommendation-records/{recommendation_record}/favorite', [RecommendationRecordController::class, 'favorite']);
    Route::post('/me/recommendation-records/{recommendation_record}/feedback', [RecommendationRecordController::class, 'feedback']);

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
    Route::get('/pay/orders', [PaymentOrderController::class, 'index']);
    Route::post('/pay/orders', [PaymentOrderController::class, 'store']);
    Route::post('/pay/orders/{id}/wechat-prepay', [PaymentOrderController::class, 'wechatPrepay']);
    Route::get('/pay/orders/{id}', [PaymentOrderController::class, 'show']);
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
    Route::get('/me/user-feedbacks', [UserFeedbackController::class, 'index']);
    Route::post('/me/user-feedbacks', [UserFeedbackController::class, 'store']);
});

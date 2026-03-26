<?php

use App\Http\Controllers\Auth\WechatAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/wechat', [WechatAuthController::class, 'login']);

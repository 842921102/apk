<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 将历史问卷页使用的旧枚举值映射到 MVP 枚举（仅影响存量行）。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_daily_statuses')->where('mood', 'anxious')->update(['mood' => 'stressed']);

        DB::table('user_daily_statuses')->where('wanted_food_style', 'warm')->update(['wanted_food_style' => 'hot']);
        DB::table('user_daily_statuses')->where('wanted_food_style', 'indulgent')->update(['wanted_food_style' => 'comforting']);

        DB::table('user_daily_statuses')->where('body_state', 'tired')->update(['body_state' => 'need_energy']);
        DB::table('user_daily_statuses')->where('body_state', 'ok')->update(['body_state' => 'normal']);
        DB::table('user_daily_statuses')->where('body_state', 'unwell')->update(['body_state' => 'low_appetite']);
    }

    public function down(): void
    {
        // 不可逆：不回滚枚举映射
    }
};

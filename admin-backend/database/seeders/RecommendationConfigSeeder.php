<?php

namespace Database\Seeders;

use App\Models\RecommendationConfig;
use Illuminate\Database\Seeder;

class RecommendationConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('recommendation_strategy_defaults', []);
        if (! is_array($defaults)) {
            return;
        }

        $descriptions = [
            'tag_weights' => '标签/画像匹配加分（口味、目标、当日状态等）',
            'feedback_apply_weights' => '短期反馈信号在打分中的加减分',
            'recent_similarity' => '近期吃过相似菜时的惩罚',
            'learned_weights' => '长期行为学习层阈值与缩放',
            'diversity_guard' => '学习偏好过强时的多样性保护',
            'cooking_scene' => '做饭频率与用餐场景匹配',
            'favorites_overlap' => '收藏食材与候选菜重叠加分',
            'diversity_control' => '候选池截断、去重与备选池参数',
            'reroll_strategy' => '换一换 pivot 顺序与文案',
            'feature_switches' => '各子策略总开关',
            'user_preference_signal_decay' => '用户偏好信号表衰减与裁剪',
            'feedback_signal_builder' => '反馈信号构建查询窗口',
            'scoring_core' => '基础分与排除分',
        ];

        foreach ($defaults as $key => $value) {
            RecommendationConfig::query()->updateOrCreate(
                ['config_key' => $key],
                [
                    'config_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'config_type' => 'json',
                    'description' => $descriptions[$key] ?? null,
                ],
            );
        }
    }
}

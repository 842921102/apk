<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $providers = [
            [
                'provider_code' => 'zhipu',
                'provider_name' => '智谱',
                'provider_type' => 'multi',
                'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'is_enabled' => true,
                'description' => '智谱模型供应商',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider_code' => 'openai',
                'provider_name' => 'OpenAI',
                'provider_type' => 'multi',
                'base_url' => 'https://api.openai.com/v1',
                'is_enabled' => false,
                'description' => 'OpenAI 模型供应商',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider_code' => 'deepseek',
                'provider_name' => 'DeepSeek',
                'provider_type' => 'text',
                'base_url' => 'https://api.deepseek.com/v1',
                'is_enabled' => false,
                'description' => 'DeepSeek 文本模型供应商',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($providers as $provider) {
            DB::table('ai_providers')->updateOrInsert(
                ['provider_code' => $provider['provider_code']],
                $provider,
            );
        }

        $providerIds = DB::table('ai_providers')->pluck('id', 'provider_code');

        $models = [
            [
                'provider_code' => 'zhipu',
                'model_code' => 'glm-4-flash',
                'model_name' => 'GLM-4-Flash',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => true,
                'is_default' => true,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => '菜谱文本推荐默认模型',
            ],
            [
                'provider_code' => 'zhipu',
                'model_code' => 'cogview-3-flash',
                'model_name' => 'CogView-3-Flash',
                'model_type' => 'image',
                'api_path' => '/images/generations',
                'is_enabled' => true,
                'is_default' => true,
                'supports_temperature' => false,
                'supports_timeout' => true,
                'description' => '图片生成默认模型',
            ],
            [
                'provider_code' => 'openai',
                'model_code' => 'gpt-4o-mini',
                'model_name' => 'GPT-4o-mini',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'OpenAI 文本模型（预留）',
            ],
        ];

        foreach ($models as $model) {
            $providerId = $providerIds[$model['provider_code']] ?? null;
            if (! $providerId) {
                continue;
            }

            DB::table('ai_models')->updateOrInsert(
                ['provider_id' => $providerId, 'model_code' => $model['model_code']],
                [
                    'provider_id' => $providerId,
                    'model_code' => $model['model_code'],
                    'model_name' => $model['model_name'],
                    'model_type' => $model['model_type'],
                    'api_path' => $model['api_path'],
                    'is_enabled' => $model['is_enabled'],
                    'is_default' => $model['is_default'],
                    'supports_temperature' => $model['supports_temperature'],
                    'supports_timeout' => $model['supports_timeout'],
                    'description' => $model['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('ai_models')->whereIn('model_code', [
            'glm-4-flash',
            'cogview-3-flash',
            'gpt-4o-mini',
        ])->delete();

        DB::table('ai_providers')->whereIn('provider_code', [
            'zhipu',
            'openai',
            'deepseek',
        ])->delete();
    }
};


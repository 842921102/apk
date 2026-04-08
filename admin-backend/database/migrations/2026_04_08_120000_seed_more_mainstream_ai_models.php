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
                'provider_code' => 'qwen',
                'provider_name' => '通义千问',
                'provider_type' => 'multi',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'is_enabled' => false,
                'description' => '阿里云百炼（OpenAI 兼容）',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider_code' => 'moonshot',
                'provider_name' => 'Moonshot',
                'provider_type' => 'multi',
                'base_url' => 'https://api.moonshot.cn/v1',
                'is_enabled' => false,
                'description' => 'Kimi（OpenAI 兼容）',
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
                'provider_code' => 'openai',
                'model_code' => 'gpt-5',
                'model_name' => 'GPT-5',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'OpenAI 主力模型',
            ],
            [
                'provider_code' => 'openai',
                'model_code' => 'gpt-5-mini',
                'model_name' => 'GPT-5-mini',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'OpenAI 轻量模型',
            ],
            [
                'provider_code' => 'openai',
                'model_code' => 'gpt-5-nano',
                'model_name' => 'GPT-5-nano',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'OpenAI 超轻量模型',
            ],
            [
                'provider_code' => 'deepseek',
                'model_code' => 'deepseek-chat',
                'model_name' => 'DeepSeek-Chat',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'DeepSeek 通用对话模型',
            ],
            [
                'provider_code' => 'deepseek',
                'model_code' => 'deepseek-reasoner',
                'model_name' => 'DeepSeek-Reasoner',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'DeepSeek 推理模型',
            ],
            [
                'provider_code' => 'qwen',
                'model_code' => 'qwen-turbo',
                'model_name' => 'Qwen-Turbo',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => '通义千问快速模型',
            ],
            [
                'provider_code' => 'qwen',
                'model_code' => 'qwen-plus',
                'model_name' => 'Qwen-Plus',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => '通义千问平衡模型',
            ],
            [
                'provider_code' => 'qwen',
                'model_code' => 'qwen-max',
                'model_name' => 'Qwen-Max',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => '通义千问高性能模型',
            ],
            [
                'provider_code' => 'moonshot',
                'model_code' => 'moonshot-v1-8k',
                'model_name' => 'Moonshot-v1-8k',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'Kimi 短上下文模型',
            ],
            [
                'provider_code' => 'moonshot',
                'model_code' => 'moonshot-v1-32k',
                'model_name' => 'Moonshot-v1-32k',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'Kimi 中长上下文模型',
            ],
            [
                'provider_code' => 'moonshot',
                'model_code' => 'moonshot-v1-128k',
                'model_name' => 'Moonshot-v1-128k',
                'model_type' => 'text',
                'api_path' => '/chat/completions',
                'is_enabled' => false,
                'is_default' => false,
                'supports_temperature' => true,
                'supports_timeout' => true,
                'description' => 'Kimi 长上下文模型',
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
            'gpt-5',
            'gpt-5-mini',
            'gpt-5-nano',
            'deepseek-chat',
            'deepseek-reasoner',
            'qwen-turbo',
            'qwen-plus',
            'qwen-max',
            'moonshot-v1-8k',
            'moonshot-v1-32k',
            'moonshot-v1-128k',
        ])->delete();

        DB::table('ai_providers')->whereIn('provider_code', [
            'qwen',
            'moonshot',
        ])->delete();
    }
};


<?php

use App\Support\AiScene;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('ai_providers')
            ->where('provider_code', 'openai')
            ->update([
                'is_enabled' => true,
                'updated_at' => $now,
            ]);

        $openaiId = DB::table('ai_providers')->where('provider_code', 'openai')->value('id');
        if (! $openaiId) {
            return;
        }

        DB::table('ai_models')->updateOrInsert(
            ['provider_id' => $openaiId, 'model_code' => 'gpt-image-1'],
            [
                'provider_id' => $openaiId,
                'model_code' => 'gpt-image-1',
                'model_name' => 'GPT-Image-1',
                'model_type' => 'image',
                'api_path' => '/images/generations',
                'is_enabled' => true,
                'is_default' => true,
                'supports_temperature' => false,
                'supports_timeout' => true,
                'description' => 'OpenAI 图片生成模型',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('ai_models')
            ->where('provider_id', $openaiId)
            ->where('model_code', 'gpt-5-mini')
            ->update([
                'is_enabled' => true,
                'is_default' => true,
                'updated_at' => $now,
            ]);

        $openaiTextModelId = DB::table('ai_models')
            ->where('provider_id', $openaiId)
            ->where('model_code', 'gpt-5-mini')
            ->value('id');

        $openaiImageModelId = DB::table('ai_models')
            ->where('provider_id', $openaiId)
            ->where('model_code', 'gpt-image-1')
            ->value('id');

        if ($openaiTextModelId) {
            DB::table('ai_model_configs')
                ->where('scene_code', AiScene::RecipeTextGeneration->value)
                ->update([
                    'provider_id' => $openaiId,
                    'model_id' => $openaiTextModelId,
                    'updated_at' => $now,
                ]);
        }

        if ($openaiImageModelId) {
            DB::table('ai_model_configs')
                ->where('scene_code', AiScene::RecipeImageGeneration->value)
                ->update([
                    'provider_id' => $openaiId,
                    'model_id' => $openaiImageModelId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // 回滚时不自动改回历史场景配置，避免覆盖人工调整。
    }
};


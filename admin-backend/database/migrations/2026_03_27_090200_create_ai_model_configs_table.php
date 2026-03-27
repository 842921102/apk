<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('scene_code', 96);
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->foreignId('model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->text('api_key');
            $table->string('base_url_override', 512)->nullable();
            $table->decimal('temperature', 3, 2)->nullable();
            $table->unsignedInteger('timeout_ms')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(true);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('scene_code');
            $table->index(['scene_code', 'is_enabled', 'is_default']);
            $table->index(['provider_id', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_configs');
    }
};


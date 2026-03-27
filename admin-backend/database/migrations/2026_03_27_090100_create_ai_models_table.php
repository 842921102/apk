<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('model_code', 96);
            $table->string('model_name', 160);
            $table->string('model_type', 16)->default('text'); // text / image
            $table->string('api_path', 255)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('supports_temperature')->default(true);
            $table->boolean('supports_timeout')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'model_code']);
            $table->index(['provider_id', 'model_type', 'is_enabled']);
            $table->index(['model_type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};


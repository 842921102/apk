<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_connection_test_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('scene_code', 96);
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('request_url', 1024)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 16); // success / failed
            $table->text('error_message')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['scene_code', 'status', 'created_at']);
            $table->index(['provider_id', 'model_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_connection_test_logs');
    }
};


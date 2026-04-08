<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_model_configs', function (Blueprint $table): void {
            $table->text('fallback_model_codes')
                ->nullable()
                ->after('timeout_ms')
                ->comment('模型降级链，逗号/换行分隔');
        });
    }

    public function down(): void
    {
        Schema::table('ai_model_configs', function (Blueprint $table): void {
            $table->dropColumn('fallback_model_codes');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_data_records', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_type', 32)->comment('table_menu|fortune_cooking|sauce_design|gallery|custom_cuisine');
            $table->string('channel', 32)->default('mini_program');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('status', 16)->default('success')->index()->comment('success|failed');
            $table->string('title')->nullable();
            $table->string('sub_type', 64)->nullable()->comment('细分动作，如 recommend/list/detail');
            $table->json('input_payload')->nullable();
            $table->json('result_payload')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->string('source_ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['feature_type', 'created_at']);
            $table->index(['feature_type', 'status', 'created_at']);
            $table->index(['feature_type', 'sub_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_data_records');
    }
};


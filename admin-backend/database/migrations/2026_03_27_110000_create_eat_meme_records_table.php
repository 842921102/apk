<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eat_meme_records', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32)->default('android_app');
            $table->string('taste', 128)->nullable();
            $table->string('avoid', 128)->nullable();
            $table->unsignedInteger('people')->nullable();
            $table->string('result_title', 255)->nullable();
            $table->string('result_cuisine', 128)->nullable();
            $table->json('result_ingredients')->nullable();
            $table->longText('result_content')->nullable();
            $table->string('status', 16)->default('success'); // success / failed
            $table->text('error_message')->nullable();
            $table->string('source_ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eat_meme_records');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('session_id')->comment('recommendation_sessions.id');
            $table->string('recommendation_source', 32);
            $table->date('recommendation_date');
            $table->string('meal_type', 24)->default('dinner');
            $table->string('recommended_dish', 160);
            $table->json('tags')->nullable();
            $table->text('reason_text');
            $table->text('destiny_text')->nullable();
            $table->json('alternatives')->nullable();
            $table->string('cuisine', 64)->nullable();
            $table->json('ingredients')->nullable();
            $table->text('recipe_content');
            $table->json('weather_snapshot')->nullable();
            $table->json('festival_snapshot')->nullable();
            $table->json('user_profile_snapshot')->nullable();
            $table->json('daily_status_snapshot')->nullable();
            $table->boolean('is_favorited')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'recommendation_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_records');
    }
};

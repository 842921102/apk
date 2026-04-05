<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birthday')->nullable();
            $table->string('gender', 32)->default('unknown');
            $table->json('flavor_preferences')->nullable();
            $table->json('taboo_ingredients')->nullable();
            $table->json('diet_preferences')->nullable();
            $table->string('health_goal', 255)->nullable();
            $table->string('recommendation_style', 64)->nullable();
            $table->boolean('destiny_mode_enabled')->default(false);
            $table->boolean('period_feature_enabled')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('target_weight_kg', 5, 1)->nullable();
            $table->json('diet_goal')->nullable();
            $table->json('cuisine_preferences')->nullable();
            $table->json('dislike_ingredients')->nullable();
            $table->json('allergy_ingredients')->nullable();
            $table->string('cooking_frequency', 32)->nullable();
            $table->string('meal_pattern', 64)->nullable();
            $table->string('family_size', 32)->nullable();
            $table->json('lifestyle_tags')->nullable();
            $table->boolean('accepts_product_recommendation')->default(false);
            $table->unsignedSmallInteger('onboarding_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'height_cm',
                'weight_kg',
                'target_weight_kg',
                'diet_goal',
                'cuisine_preferences',
                'dislike_ingredients',
                'allergy_ingredients',
                'cooking_frequency',
                'meal_pattern',
                'family_size',
                'lifestyle_tags',
                'accepts_product_recommendation',
                'onboarding_version',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('dish_key', 128)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('seasonings')->nullable();
            $table->json('steps')->nullable();
            $table->string('cooking_time', 64)->nullable();
            $table->string('difficulty', 32)->nullable();
            $table->json('tips')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_recipes');
    }
};

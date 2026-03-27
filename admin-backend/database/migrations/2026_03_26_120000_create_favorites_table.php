<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_type', 48);
            $table->string('source_id', 96)->nullable();
            $table->string('title', 512);
            $table->string('cuisine', 128)->nullable();
            $table->json('ingredients')->nullable();
            $table->longText('recipe_content');
            $table->json('extra_payload')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('source_type');
            $table->index(['user_id', 'source_type', 'created_at']);
            $table->unique(['user_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};

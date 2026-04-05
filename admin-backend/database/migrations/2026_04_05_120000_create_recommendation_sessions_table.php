<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('status_date');
            /** @var list<string>|null */
            $table->json('excluded_dishes')->nullable();
            $table->string('last_pivot', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_sessions');
    }
};

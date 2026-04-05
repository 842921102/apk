<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preference_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('signal_type', 48);
            $table->string('signal_key', 191);
            $table->string('signal_value', 255)->nullable();
            $table->decimal('weight_score', 8, 3)->default(0);
            $table->string('source', 32)->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'signal_type', 'signal_key'], 'ups_user_type_key_unique');
            $table->index(['user_id', 'last_triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preference_signals');
    }
};

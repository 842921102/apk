<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_feedback_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recommendation_record_id')->constrained('recommendation_records')->cascadeOnDelete();
            $table->string('feedback_type', 32);
            $table->string('feedback_reason', 32)->nullable();
            $table->string('feedback_target', 24);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['recommendation_record_id', 'feedback_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_feedback_records');
    }
};

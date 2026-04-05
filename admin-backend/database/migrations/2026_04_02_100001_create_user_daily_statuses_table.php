<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('status_date');
            $table->string('mood', 64)->nullable();
            $table->string('appetite_state', 64)->nullable();
            $table->string('body_state', 64)->nullable();
            $table->string('wanted_food_style', 64)->nullable();
            $table->string('period_status', 32)->default('none');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'status_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_statuses');
    }
};

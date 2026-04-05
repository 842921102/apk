<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 64)->unique();
            $table->text('config_value');
            $table->string('config_type', 24)->default('json');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_configs');
    }
};

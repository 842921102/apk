<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_code', 64)->unique();
            $table->string('provider_name', 128);
            $table->string('provider_type', 16)->default('multi'); // text / image / multi
            $table->string('base_url', 512);
            $table->boolean('is_enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['provider_type', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};


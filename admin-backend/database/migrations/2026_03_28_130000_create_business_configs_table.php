<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('config_key', 64)->unique();
            $table->string('config_name', 120)->default('');
            $table->string('config_group', 64)->default('default')->index();
            $table->json('config_value')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort')->default(0)->index();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_configs');
    }
};

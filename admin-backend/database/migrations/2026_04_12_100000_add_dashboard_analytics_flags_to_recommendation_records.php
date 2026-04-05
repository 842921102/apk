<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_records', function (Blueprint $table) {
            $table->boolean('recommendation_fallback')->default(false)->after('is_favorited');
            $table->boolean('used_default_profile')->default(false)->after('recommendation_fallback');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_records', function (Blueprint $table) {
            $table->dropColumn(['recommendation_fallback', 'used_default_profile']);
        });
    }
};

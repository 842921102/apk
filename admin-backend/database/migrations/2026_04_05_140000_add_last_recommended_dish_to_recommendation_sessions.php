<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_sessions', function (Blueprint $table) {
            $table->string('last_recommended_dish', 120)->nullable()->after('last_pivot');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_sessions', function (Blueprint $table) {
            $table->dropColumn('last_recommended_dish');
        });
    }
};

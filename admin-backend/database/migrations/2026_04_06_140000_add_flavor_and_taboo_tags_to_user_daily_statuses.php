<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_daily_statuses', function (Blueprint $table) {
            $table->json('flavor_tags')->nullable()->after('wanted_food_style');
            $table->json('taboo_tags')->nullable()->after('flavor_tags');
        });
    }

    public function down(): void
    {
        Schema::table('user_daily_statuses', function (Blueprint $table) {
            $table->dropColumn(['flavor_tags', 'taboo_tags']);
        });
    }
};

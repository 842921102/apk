<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('religious_restrictions')->nullable()->after('lifestyle_tags');
            $table->json('period_tracking')->nullable()->after('period_feature_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['religious_restrictions', 'period_tracking']);
        });
    }
};

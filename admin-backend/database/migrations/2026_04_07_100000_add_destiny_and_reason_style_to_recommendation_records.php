<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_records', function (Blueprint $table) {
            $table->string('destiny_style', 32)->nullable()->after('destiny_text');
            $table->string('reason_style', 48)->nullable()->after('destiny_style');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_records', function (Blueprint $table) {
            $table->dropColumn(['destiny_style', 'reason_style']);
        });
    }
};

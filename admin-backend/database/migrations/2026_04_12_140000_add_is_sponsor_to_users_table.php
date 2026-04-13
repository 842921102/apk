<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_sponsor')->default(false)->after('is_active')->comment('赞助用户：小程序展示「赞助用户」标签');
            $table->index('is_sponsor');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_sponsor']);
            $table->dropColumn('is_sponsor');
        });
    }
};

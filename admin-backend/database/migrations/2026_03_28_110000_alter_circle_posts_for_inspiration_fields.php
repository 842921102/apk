<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circle_posts', function (Blueprint $table): void {
            $table->string('description', 1000)->nullable()->after('title');
            $table->string('cover_image', 2048)->nullable()->after('images');
            $table->string('source_type', 24)->default('user_uploaded')->index()->after('cover_image');
            $table->string('publish_source', 24)->default('manual_upload')->after('source_type');
            $table->unsignedInteger('favorite_count')->default(0)->after('comment_count');
            $table->unsignedInteger('sort')->default(0)->after('is_pinned');
            $table->unsignedBigInteger('related_product_id')->nullable()->after('sort');
        });
    }

    public function down(): void
    {
        Schema::table('circle_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'description',
                'cover_image',
                'source_type',
                'publish_source',
                'favorite_count',
                'sort',
                'related_product_id',
            ]);
        });
    }
};

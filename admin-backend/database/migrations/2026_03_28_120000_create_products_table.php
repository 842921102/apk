<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->string('cover_image', 2048)->default('');
            $table->json('images')->nullable();
            $table->unsignedBigInteger('price')->default(0)->comment('单位：分');
            $table->unsignedBigInteger('original_price')->nullable()->comment('单位：分');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->text('description')->nullable();
            $table->longText('detail_content')->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

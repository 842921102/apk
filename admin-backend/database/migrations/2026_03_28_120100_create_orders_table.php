<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_name', 200);
            $table->string('product_image', 2048)->default('');
            $table->unsignedBigInteger('product_price')->comment('单位：分');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('total_amount')->comment('单位：分');
            $table->unsignedBigInteger('pay_amount')->default(0)->comment('单位：分');
            $table->string('order_status', 20)->default('pending_payment')->index();
            $table->string('pay_status', 20)->default('unpaid')->index();
            $table->text('remark')->nullable();
            $table->string('logistics_company', 64)->nullable();
            $table->string('logistics_no', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

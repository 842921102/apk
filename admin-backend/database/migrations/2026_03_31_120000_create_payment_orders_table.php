<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no', 64)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('business_type', 64)->index();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('title', 128);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('amount_fen');
            $table->string('currency', 8)->default('CNY');
            $table->string('pay_channel', 32)->default('wechat_mini');
            $table->string('status', 32)->default('pending')->index();
            $table->string('openid', 128);
            $table->string('prepay_id', 128)->nullable();
            $table->string('transaction_id', 128)->nullable();
            $table->json('notify_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['business_type', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('consignee_name', 64);
            $table->string('consignee_phone', 32);
            $table->string('province', 64)->default('');
            $table->string('city', 64)->default('');
            $table->string('district', 64)->default('');
            $table->string('detail_address', 255)->default('');
            $table->string('full_address', 500)->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};

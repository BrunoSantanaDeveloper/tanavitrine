<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('currency', 3);
            $table->integer('trial_days')->nullable();
            $table->json('features');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

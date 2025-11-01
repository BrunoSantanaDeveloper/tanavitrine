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
            $table->boolean('show_on_map')->default(false)->comment('Se o plano permite que a loja apareça no mapa');
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // New user automatic discount fields
            $table->enum('new_user_discount_type', ['none', 'percentage', 'fixed', 'trial'])->default('none');
            $table->decimal('new_user_discount_value', 10, 2)->nullable();
            $table->integer('new_user_discount_duration_value')->nullable();
            $table->enum('new_user_discount_duration_unit', ['days', 'months', 'years'])->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

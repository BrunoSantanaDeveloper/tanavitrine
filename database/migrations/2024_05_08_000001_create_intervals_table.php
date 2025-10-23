<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('interval_id')->constrained('intervals')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('stripe_price_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['plan_id', 'interval_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_intervals');
        Schema::dropIfExists('intervals');
    }
};

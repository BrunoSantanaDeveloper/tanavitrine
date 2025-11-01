<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2); // Percentage (0-100) or fixed amount
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('uses_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            // Duration fields (alternative to manual dates)
            $table->integer('duration_value')->nullable(); // e.g., 3
            $table->enum('duration_unit', ['days', 'months', 'years'])->nullable(); // e.g., months

            $table->boolean('is_active')->default(true);
            $table->boolean('is_exit_intent')->default(false); // Show on exit intent popup
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

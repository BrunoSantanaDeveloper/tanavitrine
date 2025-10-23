<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'media_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_media');
    }
};

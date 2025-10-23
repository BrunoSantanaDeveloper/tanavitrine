<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->enum('type', ['video', 'image', 'document']);
            $table->float('size'); // Tamanho em MB
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // em segundos, para vídeos e imagens
            $table->boolean('is_generic')->default(false);
            $table->string('category')->nullable();
            $table->string('business_type')->nullable();
            $table->json('metadata')->nullable(); // Para metadados adicionais
            $table->timestamps();

            // Índices para melhor performance
            $table->index(['team_id', 'type']);
            $table->index(['is_generic', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

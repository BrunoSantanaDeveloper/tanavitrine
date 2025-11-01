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
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('personal_team');
            $table->unsignedBigInteger('plan_id')->nullable();

            // Informações Básicas da Loja
            $table->text('description')->nullable();
            $table->string('video_url')->nullable(); // URL do vídeo (YouTube, Vimeo, etc) ou caminho do upload
            $table->enum('sale_type', ['atacado', 'varejo', 'ambos'])->default('atacado');
            $table->enum('store_type', ['fisica', 'virtual', 'ambos'])->default('ambos');

            // Categorização
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->json('subcategory')->nullable(); // Array de subcategorias (Camisetas, Calças, etc)
            $table->string('gender')->nullable(); // Masculino, Feminino, Unissex

            // Logo da Loja
            $table->string('logo_path')->nullable();

            // Informações Comerciais
            $table->string('min_order')->nullable(); // "50 peças", "Sem pedido mínimo"
            $table->string('whatsapp')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();

            // Localização
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Destaque e Status
            $table->boolean('featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->enum('status', ['pendente', 'ativo', 'inativo', 'suspenso'])->default('pendente');

            // Métricas
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('whatsapp_clicks')->default(0);
            $table->unsignedBigInteger('website_clicks')->default(0);
            $table->unsignedBigInteger('phone_clicks')->default(0);
            $table->unsignedBigInteger('map_clicks')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->unsignedBigInteger('instagram_clicks')->default(0);
            $table->unsignedBigInteger('facebook_clicks')->default(0);
            $table->unsignedBigInteger('tiktok_clicks')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
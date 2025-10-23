<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('module'); // Módulo ao qual o limite pertence
            $table->string('resource'); // Nome do recurso (ex: 'displays', 'media-storage')
            $table->string('limit_type'); // Tipo do limite (ex: 'count', 'storage', 'bandwidth')
            $table->integer('limit_value'); // Valor do limite
            $table->string('period')->default('month'); // Período do limite (day, week, month, year)
            $table->boolean('is_hard_limit')->default(true); // Se é um limite rígido ou flexível
            $table->integer('grace_period_days')->nullable(); // Período de carência para excedentes
            $table->boolean('notify_on_limit')->default(true); // Se deve notificar ao atingir limite
            $table->integer('notification_threshold')->default(80); // Porcentagem para notificação
            $table->json('metadata')->nullable(); // Dados adicionais específicos do módulo
            $table->timestamps();

            $table->unique(['plan_id', 'resource', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};

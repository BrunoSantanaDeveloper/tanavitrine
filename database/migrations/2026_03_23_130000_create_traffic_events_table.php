<?php

declare(strict_types=1);

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
        Schema::create('traffic_events', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_id', 64)->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('device_type', 20)->default('unknown')->index();
            $table->string('path', 255)->index();
            $table->string('page_type', 40)->default('other')->index();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamp('occurred_at')->index();

            $table->index(['visitor_id', 'path', 'occurred_at']);
            $table->index(['page_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_events');
    }
};

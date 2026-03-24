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
        Schema::create('traffic_presence', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_id', 64)->unique();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('device_type', 20)->default('unknown')->index();
            $table->string('current_path', 255)->default('/');
            $table->string('page_type', 40)->default('other')->index();
            $table->foreignId('current_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();

            $table->index(['last_seen_at', 'device_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_presence');
    }
};

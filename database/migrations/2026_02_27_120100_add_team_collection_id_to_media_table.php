<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('team_collection_id')
                ->nullable()
                ->after('team_id')
                ->constrained('team_collections')
                ->nullOnDelete();

            $table->index(['team_id', 'team_collection_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_collection_id');
        });
    }
};

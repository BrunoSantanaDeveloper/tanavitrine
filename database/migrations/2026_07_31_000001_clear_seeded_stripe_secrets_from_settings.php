<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'stripe_key',
                'stripe_secret',
                'stripe_webhook_secret',
            ])
            ->update(['value' => null]);
    }

    public function down(): void
    {
        //
    }
};
